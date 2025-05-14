<?php
// Function to retrieve distinct years
function getDistinctYears()
{
    global $conn;
    $years = array();
    $sql = "SELECT DISTINCT year FROM morbidity_data ORDER BY year DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['year'];
        }
    }
    return $years;
}
// Function to check if a year has barangay data
function hasBarangayData($year)
{
    global $conn;
    $sql = "SELECT DISTINCT barangay_name FROM morbidity_data WHERE year = '$year'";
    $result = $conn->query($sql);
    return $result->num_rows > 0; // Returns true if there are barangays, false otherwise
}
$years = getDistinctYears();
