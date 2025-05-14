<?php
session_start();
require('main/connection.php');
require('main/navbar.php');

// Define variables to store messages
$successMessage = "";
$errorMessage = "";

// Fetch the case data if case_id is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];

    // Fetch the case data
    $sql = "SELECT id, year, morbidity_week, cases FROM morbidity_data WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $case = $result->fetch_assoc();
    } else {
        $errorMessage = "Case not found.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save'])) {
    $case_id = $_POST['case_id'];
    $year = $_POST['year'];
    $week = $_POST['morbidity_week'];
    $cases = $_POST['cases'];
    $barangay = isset($_POST['barangay']) ? $_POST['barangay'] : ''; // Ensure barangay is set

    // Update the case
    $sql = "UPDATE morbidity_data SET year = ?, morbidity_week = ?, cases = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siii", $year, $week, $cases, $case_id);

    if ($stmt->execute()) {
        $successMessage = "Case updated successfully.";

        // Ensure barangay is not empty before redirecting
        if (!empty($barangay)) {
            echo "<script>
            setTimeout(function() {
                window.location.href = 'barangay_data.php?barangay=" . urlencode($barangay) . "';
            }, 3000); // 3 seconds delay for the message to show
            </script>";
        } else {
            // Fallback to the heatmap management page if barangay is missing
            echo "<script>
            setTimeout(function() {
                window.location.href = 'heatmap_management.php';
            }, 3000);
            </script>";
        }
    } else {
        $errorMessage = "Error updating case: " . $stmt->error;
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <title>Edit Case</title>
    <style>
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body class="eh-body">
    <div class="eh-container">
        <div class="eh-label">
            <h2>Edit Case</h2>
        </div>

        <!-- Display success/error messages immediately -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="case_id" value="<?php echo isset($case['id']) ? $case['id'] : ''; ?>">
            <div class="field-group">
                <label for="cases">Cases:</label>
                <input type="number" id="cases" name="cases" value="<?php echo isset($case['cases']) ? $case['cases'] : ''; ?>" required>
            </div>
            <div class="field-group">
                <button type="submit" name="save" class="update-btn">Save</button>
                <a href="heatmap_management.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>

<?php
require('main/footer.php');
?>