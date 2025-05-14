<?php
require('main/connection.php');

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
