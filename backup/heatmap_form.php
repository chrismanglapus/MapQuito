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
    // Check if a file is uploaded
    if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] == 0) {
        // Validate if the file is an Excel file
        $fileType = mime_content_type($_FILES['excelFile']['tmp_name']);
        $fileExtension = pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION);
        
        if ($fileType != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' && $fileExtension != 'xlsx') {
            $errorMessage = "Invalid file format. Please upload an Excel file (xlsx).";
        } else {
            // If Excel file is uploaded
            $excelFile = $_FILES['excelFile']['tmp_name'];
            $spreadsheet = IOFactory::load($excelFile);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // Validate that the file is not empty
            if (empty($data)) {
                $errorMessage = "Uploaded Excel file is empty.";
            } else {
                // Get the year from the input field
                $year = isset($_POST['dataYear']) ? intval($_POST['dataYear']) : date("Y");

                // Validate the year input
                if ($year < 2000 || $year > 2100) {
                    $errorMessage = "Invalid year. Please enter a valid year between 2000 and 2100.";
                } else {
                    // Process Excel data and save to MySQL database
                    foreach ($data as $index => $entry) {
                        if ($index == 0) continue; // Skip header row

                        $barangay_name = strtoupper(trim($entry[0]));  // Ensure Barangay is uppercase

                        // Loop through weeks 1 to 52
                        for ($week = 1; $week <= 52; $week++) {
                            $cases = intval(trim($entry[$week]));

                            // Validate the data before saving to the database
                            if (!empty($barangay_name) && is_numeric($cases)) {
                                // Save data to MySQL database
                                $stmt = $conn->prepare("INSERT INTO morbidity_data (year, morbidity_week, barangay_name, cases) VALUES (?, ?, ?, ?)");
                                $stmt->bind_param("iisi", $year, $week, $barangay_name, $cases);
                                $stmt->execute();
                                $stmt->close();
                            } else {
                                // Skip invalid data and continue to the next entry
                                continue;
                            }
                        }
                    }

                    // Success message
                    $successMessage = "Data submitted successfully for the year $year!";
                }
            }
        }
    } else {
        // If no file is uploaded
        $errorMessage = "No Excel file attached.";
    }

    // If there's an error, display it and stop further operations
    if (isset($errorMessage)) {
        echo "<script>alert('$errorMessage');</script>";
        echo '<script>window.location.href = "heatmap_management.php";</script>';
        exit;  // Stop further execution to avoid adding erroneous data
    }

    // If successful, display success message and redirect
    if (isset($successMessage)) {
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
            Barangay Name (UPPERCASED), Week 1, Week 2, ..., Week 52</p>
            <?php if (isset($errorMessage)) { echo "<p style='color: red;'>$errorMessage</p>"; } ?>
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