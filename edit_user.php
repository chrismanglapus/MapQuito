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

// Function to update user account
function updateUser($userId, $newFname, $newMi, $newLname, $newUsername, $newPassword, $newMobile, $newEmail)
{
    global $conn;

    // Check if the new username is already in use
    $checkUsernameQuery = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
    $checkUsernameQuery->bind_param("si", $newUsername, $userId);
    $checkUsernameQuery->execute();
    $checkUsernameResult = $checkUsernameQuery->get_result();

    // Check if the new email is already in use
    $checkEmailQuery = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $checkEmailQuery->bind_param("si", $newEmail, $userId);
    $checkEmailQuery->execute();
    $checkEmailResult = $checkEmailQuery->get_result();

    if ($checkUsernameResult->num_rows > 0) {
        // New username is already in use
        return "Error: Username is already used";
    }

    if ($checkEmailResult->num_rows > 0) {
        // New email is already in use
        return "Error: Email is already used";
    }

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("UPDATE admin_users SET fname = ?, mi = ?, lname = ?, username = ?, password = ?, mobile = ?, email = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $newFname, $newMi, $newLname, $newUsername, $newPassword, $newMobile, $newEmail, $userId);

    if ($stmt->execute()) {
        return "User updated successfully";
    } else {
        return "Error: " . $stmt->error;
    }

    $stmt->close();
}



$userToEdit = array();
$updateMessage = ""; // Initialize update message variable

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_user"])) {
        // Handle update button
        $userIdToUpdate = $_POST["user_id"];
        $newFname = $_POST["new_fname"];
        $newMi = $_POST["new_mi"];
        $newLname = $_POST["new_lname"];
        $newUsername = $_POST["new_username"];
        $newPassword = $_POST["new_password"];
        $newMobile = $_POST["new_mobile"];
        $newEmail = $_POST["new_email"];

        // Implement your update logic here
        $updateMessage = updateUser($userIdToUpdate, $newFname, $newMi, $newLname, $newUsername, $newPassword, $newMobile, $newEmail);

        // Fetch updated user data
        $sql = "SELECT id, fname, mi, lname, username, password, mobile, email, status FROM admin_users WHERE id = $userIdToUpdate";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $userToEdit = $result->fetch_assoc();
        }
    }
}

// Fetch user data if user_id is provided
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["user_id"])) {
    $userIdToEdit = $_GET["user_id"];
    // Fetch user data based on the ID
    $sql = "SELECT id, fname, mi, lname, username, password, mobile, email, status FROM admin_users WHERE id = $userIdToEdit";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $userToEdit = $result->fetch_assoc();
    } else {
        echo "User not found.";
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
    <title>Edit User</title>
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
            <h2>EDIT USER</h2>
            <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" class="account-form">
                <input type="hidden" name="user_id" value="<?php echo isset($userToEdit["id"]) ? $userToEdit["id"] : ''; ?>">

                <div class="form-group">
                    <label for="new_fname">First Name:</label>
                    <input type="text" name="new_fname" value="<?php echo isset($userToEdit["fname"]) ? $userToEdit["fname"] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_mi">Middle Initial:</label>
                    <input type="text" name="new_mi" value="<?php echo isset($userToEdit["mi"]) ? $userToEdit["mi"] : ''; ?>" maxlength="1" required>
                </div>
                <div class="form-group">
                    <label for="new_lname">Last Name:</label>
                    <input type="text" name="new_lname" value="<?php echo isset($userToEdit["lname"]) ? $userToEdit["lname"] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_username">Username:</label>
                    <input type="text" name="new_username" value="<?php echo isset($userToEdit["username"]) ? $userToEdit["username"] : ''; ?>" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password:</label>
                    <input type="text" name="new_password" value="<?php echo isset($userToEdit["password"]) ? $userToEdit["password"] : ''; ?>" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>
                <div class="form-group">
                    <label for="new_mobile">Mobile Number:</label>
                    <input type="text" name="new_mobile" value="<?php echo isset($userToEdit["mobile"]) ? $userToEdit["mobile"] : ''; ?>" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>
                <div class="form-group">
                    <label for="new_email">Email:</label>
                    <input type="email" name="new_email" value="<?php echo isset($userToEdit["email"]) ? $userToEdit["email"] : ''; ?>" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>
                <div class="update-user">
                    <button type="submit" name="update_user" class="update-user-btn">SAVE</button>
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
                    window.location.href = 'account_management.php';
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