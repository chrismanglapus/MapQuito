<?php
include '../main/connection.php';

// Function to fetch years from the database
function fetchYears($conn)
{
    $sql = "SELECT DISTINCT year FROM morbidity_data ORDER BY year DESC";
    $result = $conn->query($sql);

    $years = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['year'];
        }
    } else {
        echo "Error fetching years: " . $conn->error;
    }

    return $years;
}

// Function to fetch data for each year (cases per barangay)
function fetchBarangayData($conn, $years)
{
    $data = [];
    $barangays = [];

    foreach ($years as $year) {
        $yearData = [];

        // Fetch cases by barangay for this year
        $sql = "SELECT barangay_name, SUM(cases) AS total_cases FROM morbidity_data WHERE year = $year GROUP BY barangay_name";
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $barangays[] = $row['barangay_name'];
                $yearData[] = [
                    'barangay' => $row['barangay_name'],
                    'total_cases' => $row['total_cases']
                ];
            }
        } else {
            echo "Error fetching data: " . $conn->error;
        }

        $data[] = [
            'year' => $year,
            'data' => $yearData
        ];
    }

    return [
        'years' => array_values(array_unique($years)),  // Ensure years are unique and ordered
        'barangays' => array_values(array_unique($barangays)),  // Ensure barangays are unique
        'datasets' => $data
    ];
}

// Fetch years and dataset
$years = fetchYears($conn);
$data = fetchBarangayData($conn, $years);

// Convert data to JSON
echo json_encode($data);

// Close connection
$conn->close();
