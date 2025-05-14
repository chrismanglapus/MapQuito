<?php
$year = isset($_GET['year']) ? $_GET['year'] : "";

if (!$year) {
    die("<p style='color:red;'>Error: No year selected. Please go back and try again.</p>");
}

function getBarangaysByYear($year)
{
    global $conn;
    $barangays = [];
    $sql = "SELECT DISTINCT barangay_name FROM morbidity_data WHERE year = '$year' ORDER BY barangay_name ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $barangays[] = $row['barangay_name'];
        }
    }

    return $barangays;
}

$barangays = getBarangaysByYear($year);
