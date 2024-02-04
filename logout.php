<?php
session_start();

// Include your database connection code here
// require('connection.php');

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mapquitodb';

// Create a database connection
$mysqli = new mysqli($host, $username, $password, $database);

// Check the connection
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Assuming you have the username stored in the session
$username = $_SESSION['username'];

// Get the user ID based on the username
$sqlGetUserId = "SELECT id FROM admin_users WHERE username = '$username'";
$result = $mysqli->query($sqlGetUserId);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userId = $row['id'];

    // Assuming you have a logout functionality
    $sqlUpdateLogoutStatus = "UPDATE admin_users SET logged_in = 0 WHERE id = $userId";
    $mysqli->query($sqlUpdateLogoutStatus);


    if ($resultLogout) {
        // Logout date updated successfully
        echo "Logout date updated successfully";
    } else {
        // Error updating logout date
        echo "Error updating logout date: " . $mysqli->error;
    }
} else {
    // Unable to fetch user ID
    echo "Error fetching user ID: " . $mysqli->error;
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
header('Location: login.php');  // Change this to the actual filename of your login page
exit();
