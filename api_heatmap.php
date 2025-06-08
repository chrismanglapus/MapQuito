<?php
ob_start();

include('main/connection.php');
include('main/zones/rural_zones.php');
include('main/zones/urban_zones.php');
include('main/barangay_population.php');

$zones = array_merge($rural_zones, $urban_zones);

$query = "SELECT DISTINCT YEAR FROM morbidity_data WHERE barangay_name IS NOT NULL AND barangay_name != '' ORDER BY YEAR DESC";
$result = mysqli_query($conn, $query);
$uniqueYears = [];
while ($row = mysqli_fetch_assoc($result)) {
    $uniqueYears[] = $row['YEAR'];
}
$selectedYear = isset($_GET['selected_year']) ? (int)$_GET['selected_year'] : (isset($uniqueYears[0]) ? (int)$uniqueYears[0] : null);

$weeksQuery = "SELECT DISTINCT morbidity_week FROM morbidity_data WHERE YEAR = ?";
$stmt = $conn->prepare($weeksQuery);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$weeksResult = $stmt->get_result();
$uniqueWeeks = [];
while ($row = $weeksResult->fetch_assoc()) {
    $uniqueWeeks[] = $row['morbidity_week'];
}
$selectedWeek = isset($_GET['selected_week']) ? (int)$_GET['selected_week'] : (isset($uniqueWeeks[0]) ? (int)$uniqueWeeks[0] : 1);

$min_year = $selectedYear - 5;
$max_year = $selectedYear - 1;

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
    $population = isset($barangay_population[$barangay]) ? (int)$barangay_population[$barangay] : 0;
    if ($population <= 0) continue;

    // Historical data
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
        $case_count = (int)$historyRow['total_cases'];
        $rate_per_1000 = ($case_count / $population) * 1000;
        $historical_rates[] = $rate_per_1000;
    }

    $mean_rate = count($historical_rates) > 0 ? array_sum($historical_rates) / count($historical_rates) : 0;

    $std_dev = 0;
    if (count($historical_rates) > 1) {
        $sum_squared_diff = 0;
        foreach ($historical_rates as $rate) {
            $sum_squared_diff += pow($rate - $mean_rate, 2);
        }
        $std_dev = sqrt($sum_squared_diff / (count($historical_rates) - 1));
    }

    // Ensure minimum std deviation
    $min_std_dev = max(0.5, $mean_rate * 0.3);
    if ($std_dev < $min_std_dev) {
        $std_dev = $min_std_dev;
    }

    // Calculate thresholds exactly like in first snippet
    $alert_threshold = round($mean_rate + $std_dev, 2);
    $epidemic_threshold = round($mean_rate + 2 * $std_dev, 2);

    if ($epidemic_threshold <= $alert_threshold) {
        $epidemic_threshold = $alert_threshold + 1;
    }

    $current_rate = ($cases / $population) * 1000;

    // Determine status based on thresholds
    $status = 'Below Threshold';
    if ($current_rate >= $epidemic_threshold) {
        $status = 'Epidemic';
    } elseif ($current_rate >= $alert_threshold) {
        $status = 'Alert';
    }

    $casesData[$barangay] = [
        'total_cases' => $cases,
        'population' => $population,
        'rate' => round($current_rate, 2),
        'mean_rate_per_1000' => round($mean_rate, 2),
        'std_dev' => round($std_dev, 2),
        'alert_threshold_rate' => $alert_threshold,
        'epidemic_threshold_rate' => $epidemic_threshold,
        'status' => $status
    ];
}

$jsonCasesData = json_encode(['casesData' => $casesData], JSON_PRETTY_PRINT);
ob_clean();
header('Content-Type: application/json');
echo $jsonCasesData;
exit;
