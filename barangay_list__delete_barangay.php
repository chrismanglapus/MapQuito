<?php
session_start();
require('main/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['year']) || empty($_POST['year'])) {
        die("Error: No year provided.");
    }

    $year = $_POST['year'];

    // ✅ Delete Selected Barangays
    if (isset($_POST['deleteSelected']) && !empty($_POST['selected_barangays'])) {
        $selectedBarangays = $_POST['selected_barangays'];

        $stmt = $conn->prepare("DELETE FROM morbidity_data WHERE barangay_name = ? AND year = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        foreach ($selectedBarangays as $barangay) {
            $stmt->bind_param("ss", $barangay, $year);
            $stmt->execute();
            if ($stmt->affected_rows == 0) {
                die("Error: No rows deleted for barangay " . htmlspecialchars($barangay, ENT_QUOTES));
            }
        }

        $stmt->close();
        header("Location: manage_data__barangay_list.php?year=" . urlencode($year));
        exit();
    }

    // ✅ Delete All Barangays
    if (isset($_POST['deleteAll'])) {
        $stmt = $conn->prepare("DELETE FROM morbidity_data WHERE year = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $year);
        $stmt->execute();

        // Check if rows were actually deleted
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            header("Location: manage_data.php");
            exit();
        } else {
            $stmt->close();
            header("Location: manage_data__barangay_list.php?year=$year&error=no_rows_deleted");
            exit();
        }
    }
}
