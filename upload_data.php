<?php
session_start();
date_default_timezone_set('Asia/Manila');
require('main/connection.php');
require 'vendor/autoload.php'; // Include PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json'); // Set JSON response header

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== 0) {
        echo json_encode(["status" => "error", "message" => "No file uploaded or an error occurred."]);
        exit;
    }

    $fileType = mime_content_type($_FILES['excelFile']['tmp_name']);
    $fileExtension = pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION);

    if ($fileType != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $fileExtension != 'xlsx') {
        echo json_encode(["status" => "error", "message" => "Invalid file format. Please upload an Excel file (xlsx)."]);
        exit;
    }

    $excelFile = $_FILES['excelFile']['tmp_name'];
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();

    if (empty($data)) {
        echo json_encode(["status" => "error", "message" => "Uploaded Excel file is empty."]);
        exit;
    }

    $headers = array_map('strtolower', array_map('trim', $data[0])); // Convert headers to lowercase for case-insensitive comparison
    $year = intval($_POST['dataYear']); // Ensure it's an integer

    // Check if data for the year already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM morbidity_data WHERE year = ?");
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $stmt->bind_result($existingRecords);
    $stmt->fetch();
    $stmt->close();

    if ($existingRecords > 0) {
        echo json_encode(["status" => "error", "message" => "Data for year $year already exists. Please delete existing data first."]);
        exit;
    }

    // Validate headers dynamically
    if (in_array('barangay', $headers) && preg_match('/week/i', $headers[1])) {
        foreach ($data as $index => $entry) {
            if ($index == 0) continue; // Skip header row

            $barangay_name = strtoupper(trim($entry[0])); // Normalize barangay name

            $maxWeeks = count($entry) - 1; // Count columns minus the barangay column

            for ($week = 1; $week <= $maxWeeks; $week++) {
                $cases = intval(trim($entry[$week])) ?: 0;

                if (!empty($barangay_name) && is_numeric($cases)) {
                    $stmt = $conn->prepare("INSERT INTO morbidity_data (year, morbidity_week, barangay_name, cases) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iisi", $year, $week, $barangay_name, $cases);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        echo json_encode(["status" => "success", "message" => "Data successfully uploaded!", "year" => $year]);
    } else {
        echo json_encode(["status" => "error", "message" => "The Excel file format is incorrect."]);
    }

    $conn->close();
}
