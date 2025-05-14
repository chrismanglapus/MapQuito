<?php
session_start();
require('main/connection.php');
require('main/navbar.php');

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
        return "Data updated successfully!";
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
        /* Success message overlay styles */
        #success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 128, 0, 0.8);
            /* Darker green */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }

        #success-overlay .message-content {
            text-align: center;
            color: white;
            transform: scale(0.8);
            animation: scaleUp 0.6s forwards, bounceIcon 1s ease-out infinite;
        }

        /* Scale-up animation for the entire success message */
        @keyframes scaleUp {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Bounce animation for the success icon */
        @keyframes bounceIcon {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        #success-overlay i {
            font-size: 4rem;
            margin-bottom: 10px;
        }

        #success-overlay p {
            font-size: 1.25rem;
            margin-top: 10px;
        }

        /* Error message overlay styles */
        #error-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 0, 0, 0.8);
            /* Darker red */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }

        #error-overlay .message-content {
            text-align: center;
            color: white;
            transform: scale(0.8);
            animation: scaleUp 0.6s forwards, shakeIcon 0.8s ease-out;
        }

        /* Shake animation for the error icon */
        @keyframes shakeIcon {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            50% {
                transform: translateX(10px);
            }

            75% {
                transform: translateX(-10px);
            }
        }

        #error-overlay i {
            font-size: 4rem;
            margin-bottom: 10px;
        }

        #error-overlay p {
            font-size: 1.25rem;
            margin-top: 10px;
        }
    </style>
</head>

<body class="eh-body">
    <div class="eh-container">
        <div class="eh-label">
            <h2>EDIT HEATMAP DATA</h2>
        </div>

        <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" class="heatmap-form">
            <input type="hidden" name="data_id" value="<?php echo isset($dataToEdit["id"]) ? $dataToEdit["id"] : ''; ?>">

            <div class="field-group">
                <label for="new_barangay">Barangay:</label>
                <input type="text" name="new_barangay" value="<?php echo isset($dataToEdit["barangay"]) ? $dataToEdit["barangay"] : ''; ?>" required>
            </div>
            <div class="field-group">
                <label for="new_cases">Cases:</label>
                <input type="number" name="new_cases" value="<?php echo isset($dataToEdit["cases"]) ? $dataToEdit["cases"] : ''; ?>" required>
            </div>
            <div class="field-group">
                <label for="new_date_added">Date Added:</label>
                <input type="date" name="new_date_added" value="<?php echo isset($dataToEdit["date_added"]) ? $dataToEdit["date_added"] : ''; ?>" required>
            </div>
            <div class="btn-container">
                <button type="submit" name="update_data" class="update-btn">SAVE</button>
                <a href="heatmap_management.php" class="cancel-btn">CANCEL</a>
            </div>
        </form>

        <!-- Display the success or error overlay -->
        <?php
        if (!empty($updateMessage)) {
            $isError = (strpos($updateMessage, "Error") !== false);

            if ($isError) {
                echo "<div id='error-overlay'>
                        <div class='message-content'>
                            <i class='fas fa-times-circle'></i>
                            <p>$updateMessage</p>
                        </div>
                      </div>";
            } else {
                echo "<div id='success-overlay'>
                        <div class='message-content'>
                            <i class='fas fa-check-circle'></i>
                            <p>$updateMessage</p>
                        </div>
                      </div>";
            }

            // Show message overlay with smooth fade-in
            echo "<script>
                document.getElementById('" . ($isError ? 'error-overlay' : 'success-overlay') . "').style.opacity = '1'; 
                setTimeout(function() {
                    document.getElementById('" . ($isError ? 'error-overlay' : 'success-overlay') . "').style.opacity = '0'; 
                    setTimeout(function() {
                        document.getElementById('" . ($isError ? 'error-overlay' : 'success-overlay') . "').remove(); 
                    }, 1000); 
                }, 3000); 
            </script>";

            // If it's a success message, redirect after 3 seconds
            if (!$isError) {
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'heatmap_management.php';
                    }, 3000); 
                </script>";
            }
        }
        ?>
    </div>
</body>

</html>

<?php
require('main/footer.php');
?>