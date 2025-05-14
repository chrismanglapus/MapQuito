<?php
require('main/connection.php');
require 'vendor/autoload.php'; // Load PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_GET['year']) || empty($_GET['year'])) {
    die("No year selected.");
}

$year = intval($_GET['year']); // Sanitize input

// Fetch data from morbidity_data
$sql = "SELECT barangay_name, morbidity_week, SUM(cases) AS cases
        FROM morbidity_data 
        WHERE year = $year 
        GROUP BY barangay_name, morbidity_week
        ORDER BY barangay_name, morbidity_week";

$result = $conn->query($sql);

// Organize data into an associative array
$data = [];
$barangays = [];
$maxWeek = 0;

while ($row = $result->fetch_assoc()) {
    $barangay = $row['barangay_name'];
    $week = intval($row['morbidity_week']);
    $cases = intval($row['cases']);

    // Track the highest week (in case of week 53)
    $maxWeek = max($maxWeek, $week);

    if (!isset($data[$barangay])) {
        $data[$barangay] = [];
    }
    $data[$barangay][$week] = $cases;
    $barangays[$barangay] = true;
}

// Create Excel file
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Morbidity Data $year");

// Set headers dynamically
$headers = ["Barangay"];
for ($i = 1; $i <= $maxWeek; $i++) {
    $headers[] = "Week $i";
}
$sheet->fromArray([$headers], NULL, 'A1');

// Fill in data
$rowNumber = 2;
foreach ($barangays as $barangay => $_) {
    $rowData = [$barangay];
    for ($i = 1; $i <= $maxWeek; $i++) {
        $rowData[] = isset($data[$barangay][$i]) ? (string)$data[$barangay][$i] : "0";
    }
    $sheet->fromArray([$rowData], NULL, "A$rowNumber");
    $rowNumber++;
}

// Force empty cells to "0"
foreach ($sheet->getColumnIterator() as $column) {
    foreach ($sheet->getRowIterator() as $row) {
        $cell = $sheet->getCell($column->getColumnIndex() . $row->getRowIndex());
        if ($cell->getValue() === null || $cell->getValue() === "") {
            $cell->setValueExplicit("0", \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"morbidity_data_$year.xlsx\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
