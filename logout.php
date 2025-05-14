<?php
session_start();

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mapquitodb';

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Check if the user is logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Get the user ID securely
    $sqlGetUserId = "SELECT id FROM admin_users WHERE username = ?";
    $stmt = $mysqli->prepare($sqlGetUserId);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userId = $row['id'];

        // Update the logout status
        $sqlUpdateLogoutStatus = "UPDATE admin_users SET logged_in = 0 WHERE id = ?";
        $stmtUpdate = $mysqli->prepare($sqlUpdateLogoutStatus);
        $stmtUpdate->bind_param("i", $userId);

        if ($stmtUpdate->execute()) {
            // Optional: You can log the logout time if needed
            echo "Logout status updated successfully";
        } else {
            echo "Error updating logout status: " . $mysqli->error;
        }

        $stmtUpdate->close();
    } else {
        echo "Error fetching user ID: " . $mysqli->error;
    }

    $stmt->close();
}

// Clear the session
$_SESSION = array();
session_destroy();

// Redirect to the login page
header('Location: index.php');  // Update to your login page URL
exit();
