<?php
// Function to check if username already exists
function usernameExists($username)
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0; // Return true if the username exists
}

// Function to create a new user account
function createAccount($username, $password, $email, $mobile, $name)
{
    global $conn;

    // Check if the username already exists
    if (usernameExists($username)) {
        return "Error: Username already taken.";
    }

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, role, email, mobile, name) VALUES (?, ?, 1, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $password, $email, $mobile, $name);

    if ($stmt->execute()) {
        return "Account created successfully";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Initialize error and success messages
$errorMessage = '';
$successMessage = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $superAdminUsername = $_POST["username"];
    $superAdminPassword = $_POST["password"];
    $superAdminEmail = $_POST["email"];
    $superAdminMobile = $_POST["mobile"];
    $superAdminName = $_POST["name"];

    // Create a admin account
    $resultMessage = createAccount($superAdminUsername, $superAdminPassword, $superAdminEmail, $superAdminMobile, $superAdminName);

    // Check if the result message contains "Error" (username exists)
    if (strpos($resultMessage, 'Error') !== false) {
        $errorMessage = $resultMessage; // Set the error message
    } else {
        $successMessage = $resultMessage; // Set the success message
    }
}

// Close the database connection
$conn->close();
