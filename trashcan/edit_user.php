<?php
session_start();
require('main/connection.php');
require('main/navbar.php');

// Function to update user account
function updateUser($userId, $newName, $newUsername, $newPassword, $newMobile, $newEmail)
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

    // Hash the new password only if a new password is provided
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        // Update the password along with other details
        $stmt = $conn->prepare("UPDATE admin_users SET name = ?, username = ?, password = ?, mobile = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $newName, $newUsername, $hashedPassword, $newMobile, $newEmail, $userId);
    } else {
        // If no password is provided, don't update the password field
        $stmt = $conn->prepare("UPDATE admin_users SET name = ?, username = ?, mobile = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $newName, $newUsername, $newMobile, $newEmail, $userId);
    }

    if ($stmt->execute()) {
        return "User updated successfully";
    } else {
        return "Error: " . $stmt->error;
    }

    $stmt->close();
}

$userToEdit = array();
$updateMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_user"])) {
        // Handle update button
        $userIdToUpdate = $_POST["user_id"];
        $newName = $_POST["new_name"];
        $newUsername = $_POST["new_username"];
        $newPassword = $_POST["new_password"];
        $newMobile = $_POST["new_mobile"];
        $newEmail = $_POST["new_email"];

        // Implement your update logic here
        $updateMessage = updateUser($userIdToUpdate, $newName, $newUsername, $newPassword, $newMobile, $newEmail);

        // Fetch updated user data
        $sql = "SELECT id, name, username, password, mobile, email, status FROM admin_users WHERE id = $userIdToUpdate";
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
    $sql = "SELECT id, name, username, password, mobile, email, status FROM admin_users WHERE id = $userIdToEdit";
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
        /* Add custom styles for the overlay */
        #message-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 1s ease-out;
        }

        #message-overlay .content {
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            padding: 20px;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #message-overlay.success .content {
            background-color: rgba(0, 128, 0, 0.8);
            /* Green */
        }

        #message-overlay.error .content {
            background-color: rgba(255, 0, 0, 0.8);
            /* Red */
        }

        #message-overlay i {
            margin-right: 10px;
        }
    </style>
</head>

<body class="eu-body">
    <div class="eu-container">
        <div class="eu-label">
            <h2>Editing <b><?php echo isset($userToEdit["name"]) ? $userToEdit["name"] : ''; ?></b>'s Account</h2>
        </div>

        <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" class="heatmap-form">
            <input type="hidden" name="user_id"
                value="<?php echo isset($userToEdit["id"]) ? $userToEdit["id"] : ''; ?>">

            <div class="field-group">
                <label for="new_fname">Full Name:</label>
                <input type="text" name="new_name"
                    value="<?php echo isset($userToEdit["name"]) ? $userToEdit["name"] : ''; ?>" required>
            </div>
            <div class="field-group">
                <label for="new_username">Username:</label>
                <input type="text" name="new_username"
                    value="<?php echo isset($userToEdit["username"]) ? $userToEdit["username"] : ''; ?>"
                    pattern="[^\s]+" title="Spacebar not allowed" required>
            </div>
            <div class="field-group">
                <label for="new_password">Password:</label>
                <input type="password" name="new_password" placeholder="Enter new password if changing">
            </div>

            <div class="field-group">
                <label for="new_mobile">Mobile Number:</label>
                <input type="text" name="new_mobile"
                    value="<?php echo isset($userToEdit["mobile"]) ? $userToEdit["mobile"] : ''; ?>" pattern="[^\s]+"
                    title="Spacebar not allowed" required>
            </div>
            <div class="field-group">
                <label for="new_email">Email:</label>
                <input type="email" name="new_email"
                    value="<?php echo isset($userToEdit["email"]) ? $userToEdit["email"] : ''; ?>" pattern="[^\s]+"
                    title="Spacebar not allowed" required>
            </div>
            <div class="btn-container">
                <button type="submit" name="update_user" class="update-user-btn">SAVE</button>
                <a href="account_management.php" class="cancel-btn">CANCEL</a>
            </div>
        </form>

        <?php
        // Display update message and optionally redirect after a short delay
        if (!empty($updateMessage)) {
            $isError = (strpos($updateMessage, "Error") !== false);
            $messageClass = $isError ? "error" : "success";
            $iconClass = $isError ? "fas fa-times-circle" : "fas fa-check-circle";
            $messageStyle = $isError ? "color: red;" : "color: green;";

            echo "<div id='message-overlay' class='$messageClass'>";
            echo "<div class='content'>";
            echo "<i class='$iconClass'></i><p id='update-message'>$updateMessage</p>";
            echo "</div>";
            echo "</div>";

            // Show message overlay with smooth fade-in
            echo "<script>
                document.getElementById('message-overlay').style.display = 'flex';
                setTimeout(function() {
                    document.getElementById('message-overlay').style.opacity = '1'; // Fade in
                }, 100);

                // Fade out the message after 3 seconds and remove from DOM
                setTimeout(function() {
                    document.getElementById('message-overlay').style.opacity = '0'; // Fade out
                    setTimeout(function() {
                        document.getElementById('message-overlay').remove(); // Remove from DOM after fade-out
                    }, 1000); // Wait for fade-out animation to complete
                }, 3000); // 3 seconds
            </script>";

            // If it's a success message, redirect after a short delay
            if (!$isError) {
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'account_management.php';
                    }, 3000); // Redirect after 3 seconds
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