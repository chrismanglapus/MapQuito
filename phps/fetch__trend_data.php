<?php
include __DIR__ . '/../main/connection.php';

// Function to fetch distinct years from morbidity_data
function fetchYears($conn)
{
    $sql = "SELECT DISTINCT year FROM morbidity_data ORDER BY year DESC";
    $result = $conn->query($sql);

    $years = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $years[] = (int) $row['year']; // Ensure it's an integer
        }
    } else {
        error_log("Error fetching years: " . $conn->error);
    }

    return $years;
}

// Function to get the maximum week for a given year
function getMaxWeek($conn, $year)
{
    $sql = "SELECT MAX(morbidity_week) AS max_week FROM morbidity_data WHERE year = $year";
    $result = $conn->query($sql);

    if ($result) {
        $row = $result->fetch_assoc();
        return (int) ($row['max_week'] ?? 52); // Default to 52 if no data
    } else {
        error_log("Error fetching max week for year $year: " . $conn->error);
        return 52; // Fallback to 52
    }
}

// Function to fetch weekly data for each year
function fetchWeeklyData($conn, $years)
{
    $data = [];

    foreach ($years as $year) {
        $maxWeek = getMaxWeek($conn, $year); // Dynamically get the max week
        $weeklyData = array_fill(0, $maxWeek, ['week' => 0, 'total_cases' => 0.0]);

        for ($week = 1; $week <= $maxWeek; $week++) {
            $sql = "SELECT COALESCE(SUM(cases), 0) AS total_cases 
                    FROM morbidity_data 
                    WHERE year = $year AND morbidity_week = $week";
            $result = $conn->query($sql);

            if ($result) {
                $row = $result->fetch_assoc();
                $total_cases = (float) $row['total_cases'];
                $weeklyData[$week - 1] = [
                    'week' => $week,
                    'total_cases' => $total_cases
                ];
            } else {
                error_log("Error executing SQL query: " . $conn->error);
            }
        }

        $data[$year] = $weeklyData;
    }

    return $data;
}

// Fetch years and weekly data
$years = fetchYears($conn);
$weeklyData = fetchWeeklyData($conn, $years);

// Convert data to a simpler structure
$data = [];
foreach ($years as $year) {
    $weeklyDataFormatted = [];
    foreach ($weeklyData[$year] as $entry) {
        $weeklyDataFormatted[] = [
            'week' => $entry['week'],
            'total_cases' => (float) $entry['total_cases'] // Ensure it's a float
        ];
    }
    $data[$year] = $weeklyDataFormatted;
}

$response = [
    'years' => $years,
    'weekly_data' => $data
];

header('Content-Type: application/json');
echo json_encode($response, JSON_NUMERIC_CHECK);

// Close connection
$conn->close();
