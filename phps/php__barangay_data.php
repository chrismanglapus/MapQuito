<?php
// Get the year and barangay from GET
$year = isset($_GET['year']) ? $_GET['year'] : "";
$barangay = isset($_GET['barangay']) ? $_GET['barangay'] : "";

// Check if the year is provided
if (!$year) {
    die("<p style='color:red;'>Error: No year selected. Please go back and try again.</p>");
}

// Function to retrieve cases for a specific year or barangay
function getCases($year, $barangay = null)
{
    global $conn;
    $cases = array();
    $sql = "SELECT id, year, morbidity_week, cases, barangay_name FROM morbidity_data WHERE year = '$year'";

    if ($barangay) {
        $sql .= " AND barangay_name = '$barangay'";
    }

    $sql .= " ORDER BY morbidity_week DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cases[] = $row;
        }
    }

    return $cases;
}

// Function to retrieve total cases for a specific year or barangay
function getTotalCases($year, $barangay = null)
{
    global $conn;
    $sql = "SELECT SUM(cases) AS total_cases FROM morbidity_data WHERE year = '$year'";

    if ($barangay) {
        $sql .= " AND barangay_name = '$barangay'";
    }

    $result = $conn->query($sql);
    $total_cases = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total_cases = $row['total_cases'];
    }

    return $total_cases;
}

$cases = getCases($year, $barangay);
$total_cases = getTotalCases($year, $barangay);
