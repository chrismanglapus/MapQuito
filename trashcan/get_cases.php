<?php
require('main/connection.php');

// Check if the barangay is set in the request
if (isset($_GET['barangay'])) {
    $barangay = $_GET['barangay'];

    // Fetch the total cases and date_added data from the database for the specified barangay
    $sql = "SELECT SUM(cases) AS total_cases, MAX(date_added) AS last_added_date FROM heatmap_data WHERE barangay = '$barangay'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $totalCases = isset($row['total_cases']) ? $row['total_cases'] : 0;
        $lastAddedDate = isset($row['last_added_date']) ? $row['last_added_date'] : null;

        // Format the last added date as 'F j, Y - \W\e\e\k W' if it exists
        $formattedLastAddedDate = $lastAddedDate ? date('F j, Y - \W\e\e\k W', strtotime($lastAddedDate)) : '';

        // Return the total cases and formatted last added date as JSON
        echo json_encode(['total_cases' => $totalCases, 'last_added_date' => $formattedLastAddedDate]);
    } else {
        // Handle the error if the query fails
        echo json_encode(['error' => 'Failed to fetch cases']);
    }
} else {
    // Handle the case where barangay is not set in the request
    echo json_encode(['error' => 'Barangay not specified']);
}
?>
