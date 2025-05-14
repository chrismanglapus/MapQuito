<?php
session_start();
date_default_timezone_set('Asia/Manila');
require('main/connection.php');
require('main/header.php');
require 'vendor/autoload.php'; // Include PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\IOFactory;

function convertDateFormat($originalDate)
{
    $timestamp = strtotime($originalDate);
    return date("Y-m-d H:i:s", $timestamp);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errorMessage = '';
    $successMessage = '';

    // Check if a file is uploaded
    if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] == 0) {
        $fileType = mime_content_type($_FILES['excelFile']['tmp_name']);
        $fileExtension = pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION);

        if ($fileType != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' && $fileExtension != 'xlsx') {
            $errorMessage = "Invalid file format. Please upload an Excel file (xlsx).";
        } else {
            // Load the Excel file
            $excelFile = $_FILES['excelFile']['tmp_name'];
            $spreadsheet = IOFactory::load($excelFile);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // Check if the file is empty
            if (empty($data)) {
                $errorMessage = "Uploaded Excel file is empty.";
            } else {
                // Detect if the file is in Format 1 or Format 2
                $headers = $data[0];

                // Format 1: Check if the first column is "Barangay"
                if (strtoupper(trim($headers[0])) == 'BARANGAY' && strpos($headers[1], 'WEEK') !== false) {
                    // Process Format 1 (Barangay and Week columns)
                    foreach ($data as $index => $entry) {
                        if ($index == 0) continue; // Skip header row

                        $barangay_name = strtoupper(trim($entry[0]));  // Ensure Barangay is uppercase

                        // Loop through weeks 1 to 52
                        for ($week = 1; $week <= 52; $week++) {
                            $cases = intval(trim($entry[$week])) ?: 0;

                            // Validate and save data to the database
                            if (!empty($barangay_name) && is_numeric($cases)) {
                                // Save data to MySQL database
                                $stmt = $conn->prepare("INSERT INTO morbidity_data (year, morbidity_week, barangay_name, cases) VALUES (?, ?, ?, ?)");
                                $stmt->bind_param("iisi", $_POST['dataYear'], $week, $barangay_name, $cases);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                    $successMessage = "Data successfully uploaded!";
                } elseif (strtoupper(trim($headers[0])) == 'MORBIDITYWEEK' && is_numeric($headers[1])) {
                    // Format 2 Processing (MorbidityWeek, Year with cases)
                    $year = $_POST['dataYear']; // Get the year from the form

                    foreach ($data as $index => $entry) {
                        if ($index == 0) continue; // Skip header row

                        $morbidity_week = intval(trim($entry[0] ?? ''));
                        $cases = intval(trim($entry[1] ?? '')) ?: 0;

                        // Validate and save data to the database
                        if (is_numeric($morbidity_week) && is_numeric($cases)) {
                            $stmt = $conn->prepare("INSERT INTO morbidity_data (year, morbidity_week, cases) VALUES (?, ?, ?)");
                            $stmt->bind_param("iii", $year, $morbidity_week, $cases);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                    $successMessage = "Data successfully uploaded!";
                } else {
                    $errorMessage = "The Excel file does not match the expected formats.";
                }
            }
        }
    } else {
        $errorMessage = "No Excel file attached.";
    }

    // If there's an error, display it and stop further operations
    if (!empty($errorMessage)) {
        echo "<script>alert('$errorMessage');</script>";
        echo '<script>window.location.href = "heatmap_management.php";</script>';
        exit;  // Stop further execution to avoid adding erroneous data
    }

    // If successful, display success message and redirect
    if (!empty($successMessage)) {
        echo "<script>alert('$successMessage');</script>";
        echo '<script>window.location.href = "heatmap_management.php";</script>';
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <title>Morbidity Form</title>
</head>

<body class="hf-body">
    <div class="hf-container">
        <h2>Input Data using Excel</h2>
        <div class="excel-form">
            <form method="post" enctype="multipart/form-data" onsubmit="return confirmSubmit()">
                <label for="dataYear">Year of Data:</label>
                <input type="number" name="dataYear" id="dataYear" min="2000" max="2100" value="<?php echo date("Y"); ?>" required>
                <br><br>
                <label for="excelFile">Upload an Excel file:</label>
                <input type="file" name="excelFile" id="excelFile" required>
                <br>
                <input type="submit" name="submitExcel" value="Submit Excel" class="submit-btn">
                <a href="heatmap_management.php" class="cancel-btn">CANCEL</a>
            </form>
            <p>NOTE: The Excel file should have columns:<br>
                Format 1: Barangay Name, Week 1, Week 2, ..., Week 52<br>
                Format 2: MorbidityWeek, Year (e.g., 2019, 2020, etc.)</p>
            <?php if (isset($errorMessage)) {
                echo "<p style='color: red;'>$errorMessage</p>";
            } ?>
            <?php if (isset($successMessage)) {
                echo "<p style='color: green;'>$successMessage</p>";
            } ?>
        </div>
    </div>

    <script>
        function confirmSubmit() {
            return confirm("Are you sure you want to add this data?");
        }
    </script>

</body>

</html>

<?php
require('main/footer.php');
?>
