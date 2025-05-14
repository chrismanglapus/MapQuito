<?php
// Include necessary files without generating HTML output
include('main/connection.php');
include('main/zones/rural_zones.php');
include('main/zones/urban_zones.php');
include('main/barangay_population.php');

// Combine both urban and rural zones
$zones = array_merge($rural_zones, $urban_zones);

// Fetch available years from the database
$query = "SELECT DISTINCT YEAR FROM morbidity_data WHERE barangay_name IS NOT NULL AND barangay_name != '' ORDER BY YEAR DESC";
$result = mysqli_query($conn, $query);

$uniqueYears = [];
while ($row = mysqli_fetch_assoc($result)) {
    $uniqueYears[] = $row['YEAR'];
}

// Set default selected year
$selectedYear = isset($_GET['selected_year'])
    ? (int)$_GET['selected_year']
    : (isset($uniqueYears[0]) ? (int)$uniqueYears[0] : null);

// Fetch available morbidity weeks for the selected year
$weeksQuery = "SELECT DISTINCT morbidity_week FROM morbidity_data WHERE YEAR = ?";
$stmt = $conn->prepare($weeksQuery);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$weeksResult = $stmt->get_result();

$uniqueWeeks = [];
while ($row = $weeksResult->fetch_assoc()) {
    $uniqueWeeks[] = $row['morbidity_week'];
}

// Set default morbidity week
$selectedWeek = isset($_GET['selected_week'])
    ? (int)$_GET['selected_week']
    : (isset($uniqueWeeks[0]) ? (int)$uniqueWeeks[0] : 1);

// Define historical period (past 5 years)
$min_year = $selectedYear - 5;
$max_year = $selectedYear - 1;

// Fetch morbidity data for the selected year and week
$casesData = [];
$casesQuery = "
    SELECT barangay_name, SUM(cases) AS total_cases
    FROM morbidity_data
    WHERE YEAR = ? AND morbidity_week = ?
    GROUP BY barangay_name";
$stmt = $conn->prepare($casesQuery);
$stmt->bind_param("ii", $selectedYear, $selectedWeek);
$stmt->execute();
$casesResult = $stmt->get_result();

while ($row = mysqli_fetch_assoc($casesResult)) {
    $barangay = $row['barangay_name'];
    $cases = (int)$row['total_cases'];

    // Get population
    $population = isset($barangay_population[$barangay]) ? (int)$barangay_population[$barangay] : 0;
    if ($population <= 0) {
        continue; // Skip if no valid population
    }

    // Fetch historical data for the same week in past 5 years
    $historyQuery = "
        SELECT SUM(cases) AS total_cases
        FROM morbidity_data 
        WHERE barangay_name = ? AND morbidity_week = ? AND YEAR BETWEEN ? AND ?
        GROUP BY YEAR";
    $stmt_history = $conn->prepare($historyQuery);
    $stmt_history->bind_param("siii", $barangay, $selectedWeek, $min_year, $max_year);
    $stmt_history->execute();
    $historyResult = $stmt_history->get_result();

    $historical_rates = [];
    while ($historyRow = mysqli_fetch_assoc($historyResult)) {
        // Calculate rate per 1,000 population
        $historical_rates[] = ($historyRow['total_cases'] / $population) * 1000;
    }

    // Compute mean rate (μ)
    $mean_rate = count($historical_rates) > 0 ? array_sum($historical_rates) / count($historical_rates) : 0;

    // Compute standard deviation (σ)
    $std_dev = 0;
    if (count($historical_rates) > 1) {
        $sum_squared_diff = 0;
        foreach ($historical_rates as $rate) {
            $sum_squared_diff += pow($rate - $mean_rate, 2);
        }
        $std_dev = sqrt($sum_squared_diff / (count($historical_rates) - 1));
    }

    // Set a minimum standard deviation to prevent extremely low thresholds
    $min_std_dev = max(0.5, $mean_rate * 0.3);
    if ($std_dev < $min_std_dev) {
        $std_dev = $min_std_dev;
    }

    // Convert fallback thresholds into rate units (cases per 1,000 population)
    $alert_fallback_rate = ($population > 0)
        ? (ceil(0.001 * $population) / $population) * 1000
        : 0;
    $epidemic_fallback_rate = ($population > 0)
        ? (ceil(0.002 * $population) / $population) * 1000
        : 0;

    // Compute thresholds dynamically in rate units
    $alert_threshold = round($mean_rate + $std_dev);
    $alert_threshold = max($alert_threshold, $alert_fallback_rate);

    $epidemic_threshold = round($mean_rate + 2 * $std_dev);
    $epidemic_threshold = max($epidemic_threshold, $epidemic_fallback_rate);

    // Ensure epidemic threshold is at least one unit (in rate) higher than alert threshold:
    if ($epidemic_threshold <= $alert_threshold) {
        $epidemic_threshold = $alert_threshold + 1;
    }

    // Convert thresholds (in rate) into actual case counts (per week) for display in preventive measures:
    $alert_threshold_count = round(($alert_threshold / 1000) * $population);
    $epidemic_threshold_count = round(($epidemic_threshold / 1000) * $population);

    // Compute current rate:
    $current_rate = ($cases / $population) * 1000;

    // Determine status using the rate
    $status = 'Below Threshold';
    if ($current_rate >= $epidemic_threshold) {
        $status = 'Epidemic';
    } elseif ($current_rate >= $alert_threshold) {
        $status = 'Alert';
    }

    // Store data, including both rate thresholds and count thresholds:
    $casesData[$barangay] = [
        'total_cases' => $cases,
        'population' => $population,
        'rate' => round($current_rate, 2),
        'alert_threshold' => $alert_threshold,             // threshold in rate (cases per 1,000)
        'epidemic_threshold' => $epidemic_threshold,         // threshold in rate (cases per 1,000)
        'alert_threshold_count' => $alert_threshold_count,   // threshold in actual cases
        'epidemic_threshold_count' => $epidemic_threshold_count, // threshold in actual cases
        'mean_rate_per_1000' => round($mean_rate, 2),
        'std_dev' => round($std_dev, 2),
        'status' => $status
    ];
}

// Calculate total cases for the selected year and week
$totalCases = 0;
foreach ($casesData as $barangay => $data) {
    $totalCases += $data['total_cases'];
}

// Fetch total cases for the entire year for JS usage
$totalCasesYearQuery = "
    SELECT barangay_name, SUM(cases) AS total_cases_year
    FROM morbidity_data
    WHERE YEAR = ?
    GROUP BY barangay_name";
$stmt = $conn->prepare($totalCasesYearQuery);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$totalCasesYearResult = $stmt->get_result();

$totalCasesYearData = [];
while ($row = mysqli_fetch_assoc($totalCasesYearResult)) {
    $barangayName = $row['barangay_name'];
    $totalCasesYearData[$barangayName] = $row['total_cases_year'];
}

// Convert data to JavaScript objects
$totalCasesYearDataJS = json_encode($totalCasesYearData);

// Convert data to JSON
$jsonCasesData = json_encode(['casesData' => $casesData], JSON_PRETTY_PRINT);