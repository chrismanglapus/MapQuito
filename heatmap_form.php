<?php
session_start();
date_default_timezone_set('Asia/Manila');
require('connection.php');
require('header.php');
require('menu.php');

function convertDateFormat($originalDate)
{
    // Convert mm/dd/yy to YYYY-mm-dd
    $timestamp = strtotime($originalDate);
    return date("Y-m-d H:i:s", $timestamp);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission

    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == 0) {
        // If CSV file is uploaded
        $csvFile = $_FILES['csvFile']['tmp_name'];
        $data = array_map('str_getcsv', file($csvFile));

        // Process CSV data and save to MySQL database
        foreach ($data as $entry) {
            // Check if the entry has at least three elements (date, barangay, and cases)
            if (count($entry) >= 3) {
                $barangay = trim($entry[0]);
                $cases = intval(trim($entry[1]));
                $originalDate = trim($entry[2]);

                // Convert date format
                $currentDate = convertDateFormat($originalDate);

                // Validate the data before saving to the database
                if (!empty($barangay) && is_numeric($cases)) {
                    // Assign severity based on the number of cases
                    if ($cases < 50) {
                        $severity = 'Blue';
                    } elseif ($cases < 100) {
                        $severity = 'Green';
                    } elseif ($cases < 500) {
                        $severity = 'Yellow';
                    } else {
                        $severity = 'Red';
                    }

                    // Save data to MySQL database with date_added
                    $stmt = $conn->prepare("INSERT INTO heatmap_data (barangay, cases, severity, date_added) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("siss", $barangay, $cases, $severity, $currentDate);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    } elseif (isset($_POST['manualSubmit'])) {
        // If manually inputting data
        $barangay = $_POST['barangay'];
        $cases = $_POST['cases'];
        $dates = $_POST['date'];

        // Process manual input data and save to MySQL database
        foreach ($barangay as $index => $value) {
            $barangayValue = $value;
            $casesValue = intval($cases[$index]);
            $originalDateValue = $dates[$index];

            // Convert date format
            $currentDateValue = convertDateFormat($originalDateValue);

            // Assign severity based on the number of cases
            if ($casesValue < 50) {
                $severityValue = 'Blue';
            } elseif ($casesValue < 100) {
                $severityValue = 'Green';
            } elseif ($casesValue < 500) {
                $severityValue = 'Yellow';
            } else {
                $severityValue = 'Red';
            }

            // Save data to MySQL database with date_added
            $stmt = $conn->prepare("INSERT INTO heatmap_data (barangay, cases, severity, date_added) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $barangayValue, $casesValue, $severityValue, $currentDateValue);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Redirect to the index.php
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <title>Heatmap Management</title>
    <style>
        h2 {
            font-size: 30px;
            color: #3498db;
            outline: auto;
            text-align: center;
            background-color: white;
        }
    </style>
</head>

<body>
    <div class="table-container">
        <h2>Heatmap Data Input Form</h2>
        <form method="post" enctype="multipart/form-data">
            <label for="csvFile">Upload CSV file:</label>
            <input type="file" name="csvFile" id="csvFile">
            <br>
            <input type="submit" name="submitCSV" value="Submit CSV" class="create-account-btn">
        </form>

        <form method="post">
            <p>OR</p>
            <label>Enter data manually:</label>
            <table>
                <thead>
                    <tr>
                        <th>Barangay</th>
                        <th>Number of Cases</th>
                        <th>Date (mm/dd/yy)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="barangay[]" required>
                                <option value="">Select Barangay</option>
                                <option value="Catbangen">Catbangen</option>
                                <option value="Catbangen">Catbangen</option>
                                <option value="San Francisco">San Francisco</option>
                                <option value="Canaoay">Canaoay</option>
                                <option value="Madayegdeg">Madayegdeg</option>
                                <option value="Parian">Parian</option>
                                <option value="San Vicente">San Vicente</option>
                                <option value="Pagudpud">Pagudpud</option>
                                <option value="San Agustin">San Agustin</option>
                                <option value="Lingsat">Lingsat</option>
                                <option value="Dalumpinas Oeste">Dalumpinas Oeste</option>
                                <option value="Cabaroan">Cabaroan</option>
                                <option value="Santiago Norte">Santiago Norte</option>
                                <option value="Carlatan">Carlatan</option>
                                <option value="Biday">Biday</option>
                                <option value="Pagdaraoan">Pagdaraoan</option>
                                <option value="Tanqui">Tanqui</option>
                                <option value="Barangay I">Barangay I</option>
                                <option value="Barangay II">Barangay II</option>
                                <option value="Barangay III">Barangay III</option>
                                <option value="Barangay IV">Barangay IV</option>
                                <option value="Ilocanos Norte">Ilocanos Norte</option>
                                <option value="Ilocanos Sur">Ilocanos Sur</option>
                                <option value="Sevilla">Sevilla</option>
                                <option value="Pagdalagan">Pagdalagan</option>
                                <option value="Poro">Poro</option>
                            </select>
                        </td>
                        <td><input type="number" name="cases[]" required></td>
                        <td><input type="text" name="date[]" required></td>
                    </tr>
                </tbody>
            </table>
            <br>
            <input type="submit" name="manualSubmit" value="Submit Manual Data" class="create-account-btn">
        </form>
    </div>
</body>

</html>

<?php
require_once 'footer.php';
?>