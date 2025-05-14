<?php
session_start();
require('main/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["user_id"])) {
    $userIdToDelete = $conn->real_escape_string($_GET["user_id"]);

    // Attempt to delete the user
    $deleteUserSql = "DELETE FROM admin_users WHERE id = ?";
    $stmtDeleteUser = $conn->prepare($deleteUserSql);
    $stmtDeleteUser->bind_param("i", $userIdToDelete);
    $stmtDeleteUser->execute();

    if ($stmtDeleteUser->affected_rows > 0) {
        echo "success"; // Send success message
    } else {
        echo "error"; // Send error message
    }

    // Close the statements after use
    $stmtDeleteUser->close();
}

// Close the database connection
$conn->close();
?>
