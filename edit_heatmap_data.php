<?php
session_start();
require('connection.php');
require('header.php');
require('menu.php');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mapquitodb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to update heatmap data
function updateHeatmapData($dataId, $newBarangay, $newCases, $newDateAdded)
{
    global $conn;

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("UPDATE heatmap_data SET barangay = ?, cases = ?, date_added = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $newBarangay, $newCases, $newDateAdded, $dataId);

    if ($stmt->execute()) {
        return "Data updated successfully";
    } else {
        return "Error: " . $stmt->error;
    }

    $stmt->close();
}

$dataToEdit = array();
$updateMessage = ""; // Initialize update message variable

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_data"])) {
        // Handle update button
        $dataIdToUpdate = $_POST["data_id"];
        $newBarangay = $_POST["new_barangay"];
        $newCases = $_POST["new_cases"];
        $newDateAdded = $_POST["new_date_added"];

        // Implement your update logic here
        $updateMessage = updateHeatmapData($dataIdToUpdate, $newBarangay, $newCases, $newDateAdded);

        // Fetch updated heatmap data
        $sql = "SELECT id, barangay, cases, date_added FROM heatmap_data WHERE id = $dataIdToUpdate";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $dataToEdit = $result->fetch_assoc();
        }
    }
}

// Fetch heatmap data if data_id is provided
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["data_id"])) {
    $dataIdToEdit = $_GET["data_id"];
    // Fetch heatmap data based on the ID
    $sql = "SELECT id, barangay, cases, date_added FROM heatmap_data WHERE id = $dataIdToEdit";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $dataToEdit = $result->fetch_assoc();
    } else {
        echo "Data not found.";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Edit Heatmap Data</title>
    <style>
        h2 {
            font-size: 30px;
            color: #3498db;
            outline: auto;
            text-align: center;
            background-color: white;
        }

        .error-message {
            color: red;
        }

        .success-message {
            color: green;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-content">
            <h2>EDIT HEATMAP DATA</h2>
            <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" class="heatmap-form">
                <input type="hidden" name="data_id" value="<?php echo isset($dataToEdit["id"]) ? $dataToEdit["id"] : ''; ?>">

                <div class="form-group">
                    <label for="new_barangay">Barangay:</label>
                    <input type="text" name="new_barangay" value="<?php echo isset($dataToEdit["barangay"]) ? $dataToEdit["barangay"] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_cases">Cases:</label>
                    <input type="number" name="new_cases" value="<?php echo isset($dataToEdit["cases"]) ? $dataToEdit["cases"] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_date_added">Date Added:</label>
                    <input type="date" name="new_date_added" value="<?php echo isset($dataToEdit["date_added"]) ? $dataToEdit["date_added"] : ''; ?>" required>
                </div>
                <div class="update-data">
                    <button type="submit" name="update_data" class="update-data-btn">SAVE</button>
                </div>
        </div>
        </form>
        <?php
        // Display update message and optionally redirect after a short delay
        if (!empty($updateMessage)) {
            $isError = (strpos($updateMessage, "Error") !== false);
            $messageStyle = $isError ? "color: red;" : "color: green;";

            echo "<div id='update-container' style='text-align: center; margin-top: 15px;'>";
            echo "<p style='$messageStyle' id='update-message'>$updateMessage</p>";

            if (!$isError) { // Display the GIF only for success messages
                // Create a new image for the loading icon
                echo "<img src='assets/check.gif' id='loading-icon' alt='Check Icon' style='width: 50px;'>";

                echo "<script>
                setTimeout(function() {
                    document.getElementById('update-container').style.display = 'none';
                    window.location.href = 'heatmap_management.php';
                }, 5000); // 5000 milliseconds (5 seconds), adjust as needed
                </script>";
            } else {
                echo "<script>
                setTimeout(function() {
                    document.getElementById('update-container').style.display = 'none';
                }, 5000); // 5000 milliseconds (5 seconds), adjust as needed
                </script>";
            }

            echo "</div>";
        }

        ?>

    </div>
</body>

</html>

<?php
require('footer.php');
?>
