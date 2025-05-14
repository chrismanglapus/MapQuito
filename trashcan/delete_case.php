<?php
session_start();
require('main/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];
    $barangay = isset($_POST['barangay']) ? $_POST['barangay'] : "";
    $year = isset($_POST['year']) ? $_POST['year'] : "";

    // Delete the case
    $sql = "DELETE FROM morbidity_data WHERE id = $case_id";
    if ($conn->query($sql) === TRUE) {
        echo "Case deleted successfully.";
    } else {
        echo "Error deleting case: " . $conn->error;
    }

    // Redirect back with correct parameters
    header("Location: barangay_data.php?barangay=" . urlencode($barangay) . "&year=" . urlencode($year) . "&showData=");
    exit();
}
?>
