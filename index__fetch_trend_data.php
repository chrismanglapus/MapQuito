<?php
require('main/connection.php');
require('main/barangay_population.php'); // Include population data

header('Content-Type: application/json');

if (isset($_GET['barangay']) && isset($_GET['year'])) {
    $barangay = $_GET['barangay'];
    $selectedYear = (int) $_GET['year'];

    // Define the 5 years: selected year plus the previous 4 years
    $years = [
        $selectedYear,
        $selectedYear - 1,
        $selectedYear - 2,
        $selectedYear - 3,
        $selectedYear - 4
    ];

    // Query: Fetch case data for the specified barangay and years, grouped by YEAR and morbidity_week.
    $query = "SELECT YEAR, morbidity_week, SUM(cases) as total_cases 
              FROM morbidity_data 
              WHERE barangay_name = ? AND YEAR IN (?, ?, ?, ?, ?)
              GROUP BY YEAR, morbidity_week 
              ORDER BY morbidity_week ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiiii", $barangay, $years[0], $years[1], $years[2], $years[3], $years[4]);
    $stmt->execute();
    $result = $stmt->get_result();

    $dataByYear = [];
    $allWeeks = [];

    // Collect data per year and build a master list of weeks
    while ($row = $result->fetch_assoc()) {
        $year = (int)$row['YEAR'];
        $week = $row['morbidity_week'];
        $cases = (int)$row['total_cases'];

        $dataByYear[$year][$week] = $cases;
        if (!in_array($week, $allWeeks)) {
            $allWeeks[] = $week;
        }
    }

    // Sort the weeks in ascending order
    sort($allWeeks, SORT_NUMERIC);

    // Build datasets: for each year, for every week in $allWeeks, use the case count or 0 if missing.
    $datasets = [];
    foreach ($years as $year) {
        $casesArray = [];
        foreach ($allWeeks as $week) {
            $casesArray[] = isset($dataByYear[$year][$week]) ? $dataByYear[$year][$week] : 0;
        }
        $datasets[] = [
            'label' => (string)$year,
            'data'  => $casesArray
        ];
    }

    $output = [
        'labels' => $allWeeks,
        'datasets' => $datasets
    ];

    echo json_encode($output, JSON_PRETTY_PRINT);
} else {
    echo json_encode(["error" => "Missing parameters"]);
}
?>
