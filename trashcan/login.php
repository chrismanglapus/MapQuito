<?php
session_start(); // Move this to the very top
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        function hideErrorMessage() {
            var errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.style.display = 'block';
                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 5000); // 5 seconds
            }
        }

        function showSuccessMessageAndRedirect() {
            // Check if the success message already exists
            if (document.getElementById('success-overlay')) return;

            // Create a subtle overlay
            var successOverlay = document.createElement('div');
            successOverlay.id = 'success-overlay';
            successOverlay.style.position = 'fixed';
            successOverlay.style.top = '0';
            successOverlay.style.left = '0';
            successOverlay.style.width = '100%';
            successOverlay.style.height = '100%';
            successOverlay.style.backgroundColor = 'rgba(0, 128, 0, 0.5)'; // Semi-transparent green
            successOverlay.style.display = 'flex';
            successOverlay.style.justifyContent = 'center';
            successOverlay.style.alignItems = 'center';
            successOverlay.style.zIndex = '9999'; // Ensure it appears above everything
            successOverlay.style.opacity = '0'; // Initially invisible
            successOverlay.style.transition = 'opacity 1s ease-out'; // Fade transition
            document.body.appendChild(successOverlay);

            // Add a subtle success icon (checkmark)
            var successIcon = document.createElement('i');
            successIcon.classList.add('fas', 'fa-check-circle');
            successIcon.style.color = 'white';
            successIcon.style.fontSize = '3rem';
            successOverlay.appendChild(successIcon);

            // Fade in the overlay and icon
            setTimeout(() => {
                successOverlay.style.opacity = '1'; // Fade to 100% opacity
            }, 100);

            // Redirect after a brief delay
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 3000); // Redirect after 3 seconds
        }
    </script>
</head>

<body class="login-body">
    <div class="login-wrapper">
        <div class="login-box">
            <div class="login-left">
                <img src="assets/logologin.png" alt="Logo" class="login-logo">
            </div>
            <div class="login-right">
                <p class="login-title">Login</p>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                    <div class="input-group">
                        <input type="text" id="username" name="username" placeholder="Username" autocomplete="username" required>
                    </div>

                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
                    </div>

                    <div class="error-success">
                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == "POST") {
                            require('main/connection.php');
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

                                $isPasswordHashed = strlen($storedPassword) === 60 && strpos($storedPassword, '$2y$') === 0;

                                if ($isPasswordHashed) {
                                    if (password_verify($password, $storedPassword)) {
                                        loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path);
                                    } else {
                                        showErrorMessage("Invalid username or password!");
                                    }
                                } else {
                                    if ($password === $storedPassword) {
                                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                                        $updatePasswordQuery = "UPDATE admin_users SET password = ? WHERE id = ?";
                                        $updateStmt = $conn->prepare($updatePasswordQuery);
                                        $updateStmt->bind_param("si", $hashedPassword, $userId);
                                        $updateStmt->execute();
                                        $updateStmt->close();

                                        loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path);
                                    } else {
                                        showErrorMessage("Invalid username or password!");
                                    }
                                }
                            } else {
                                showErrorMessage("Invalid username or password!");
                            }

                            $stmt->close();
                            $conn->close();
                        }

                        function loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path)
                        {
                            if ($userRole == 1 && $userStatus == 1) {
                                echo '<script>showSuccessMessageAndRedirect();</script>';

                                $sqlUpdateLoginStatus = "UPDATE admin_users SET logged_in = 1 WHERE id = ?";
                                $updateStmt = $conn->prepare($sqlUpdateLoginStatus);
                                $updateStmt->bind_param("i", $userId);
                                $updateStmt->execute();
                                $updateStmt->close();

                                $_SESSION['ADMIN_ROLE'] = $userRole;
                                $_SESSION['username'] = $_POST['username'];
                                $_SESSION['name'] = $name;
                                $_SESSION['profile_picture_path'] = $profile_picture_path;
                            } elseif ($userRole == 0) {
                                echo '<script>showSuccessMessageAndRedirect();</script>';

                                $sqlUpdateLoginStatus = "UPDATE admin_users SET logged_in = 1 WHERE id = ?";
                                $updateStmt = $conn->prepare($sqlUpdateLoginStatus);
                                $updateStmt->bind_param("i", $userId);
                                $updateStmt->execute();
                                $updateStmt->close();

                                $_SESSION['ADMIN_ROLE'] = $userRole;
                                $_SESSION['username'] = $_POST['username'];
                                $_SESSION['name'] = $name;
                                $_SESSION['profile_picture_path'] = $profile_picture_path;
                            } else {
                                showErrorMessage("This account is not active! Please contact Super Admin.");
                            }
                        }

                        function showErrorMessage($message)
                        {
                            echo '
                                <div class="error">
                                    <div class="error__icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24" height="24" fill="none">
                                            <path fill="#393a37" d="m13 13h-2v-6h2zm0 4h-2v-2h2zm-1-15c-1.3132 0-2.61358.25866-3.82683.7612-1.21326.50255-2.31565 1.23915-3.24424 2.16773-1.87536 1.87537-2.92893 4.41891-2.92893 7.07107 0 2.6522 1.05357 5.1957 2.92893 7.0711.92859.9286 2.03098 1.6651 3.24424 2.1677 1.21325.5025 2.51363.7612 3.82683.7612 2.6522 0 5.1957-1.0536 7.0711-2.9289 1.8753-1.8754 2.9289-4.4189 2.9289-7.0711 0-1.3132-.2587-2.61358-.7612-3.82683-.5026-1.21326-1.2391-2.31565-2.1677-3.24424-.9286-.92858-2.031-1.66518-3.2443-2.16773-1.2132-.50254-2.5136-.7612-3.8268-.7612z"></path>
                                        </svg>
                                    </div>
                                    <div class="error__title">' . $message . '</div>
                                    <div class="error__close" onclick="closeErrorMessage()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 20 20" height="20">
                                            <path fill="#393a37" d="m15.8333 5.34166-1.175-1.175-4.6583 4.65834-4.65833-4.65834-1.175 1.175 4.65833 4.65834-4.65833 4.6583 1.175 1.175 4.65833-4.6583 4.6583 4.6583 1.175-1.175-4.6583-4.6583z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <script>
                                    function closeErrorMessage() {
                                        document.querySelector(".error").style.display = "none";
                                    }
                                    setTimeout(closeErrorMessage, 5000);
                                </script>';
                        }
                        ?>
                    </div>

                    <button class="login-submit" type="submit">LOGIN</button>

                </form>

                <div class="redirect">
                    <a href="index.php" class="styled-wrapper">
                        <button class="button">
                            <div class="button-box">
                                <span class="button-elem">
                                    <svg
                                        viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="arrow-icon">
                                        <path
                                            fill="#444444"
                                            d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path>
                                    </svg>
                                </span>
                                <span class="button-elem">
                                    <svg
                                        fill="#444444"
                                        viewBox="0 0  24 24"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="arrow-icon">
                                        <path
                                            d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path>
                                    </svg>
                                </span>
                            </div>
                        </button>
                    </a>
                </div>

            </div>
        </div>
    </div>
</body>

</html>