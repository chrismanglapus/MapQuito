<?php
session_start();
require('main/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id, role, status, name, profile_picture_path, password FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $userRole, $userStatus, $name, $profile_picture_path, $storedPassword);
        $stmt->fetch();

        // 🔍 Check if password is hashed
        $isPasswordHashed = strlen($storedPassword) === 60 && strpos($storedPassword, '$2y$') === 0;

        if ($isPasswordHashed) {
            // ✅ Verify password with hash
            if (password_verify($password, $storedPassword)) {
                loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path);
            } else {
                sendResponse("error", "Incorrect password!");
            }
        } else {
            // ❗ If password is not hashed, update it securely
            if ($password === $storedPassword) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $updatePasswordQuery = "UPDATE admin_users SET password = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updatePasswordQuery);
                $updateStmt->bind_param("si", $hashedPassword, $userId);
                $updateStmt->execute();
                $updateStmt->close();

                loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path);
            } else {
                sendResponse("error", "Invalid username or password!");
            }
        }
    } else {
        sendResponse("error", "Invalid username or password!");
    }

    $stmt->close();
    $conn->close();
}

// ✅ Function to handle successful login
function loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path)
{
    if ($userStatus != 1) {
        sendResponse("error", "This account is not active! Please contact Super Admin.");
        return;
    }

    $_SESSION['ADMIN_ROLE'] = $userRole;
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['name'] = $name;
    $_SESSION['profile_picture_path'] = $profile_picture_path;

    // ✅ Update login status
    $sqlUpdateLoginStatus = "UPDATE admin_users SET logged_in = 1 WHERE id = ?";
    $updateStmt = $conn->prepare($sqlUpdateLoginStatus);
    $updateStmt->bind_param("i", $userId);
    $updateStmt->execute();
    $updateStmt->close();

    sendResponse("success", "Login successful!");
}

// ✅ Function to return JSON responses
function sendResponse($status, $message)
{
    echo json_encode(["status" => $status, "message" => $message]);
    exit();
}
