<?php
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

// Retrieve parameters from the AJAX request
$userId = $_GET["userId"];
$currentStatus = $_GET["currentStatus"];

// Toggle user status
$newStatus = ($currentStatus == 1) ? 0 : 1;

$stmt = $conn->prepare("UPDATE admin_users SET status = ? WHERE id = ?");
$stmt->bind_param("ii", $newStatus, $userId);

if ($stmt->execute()) {
    echo "Status updated successfully";
} else {
    echo "Error updating status: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
