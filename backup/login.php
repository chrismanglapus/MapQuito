<?php
session_start(); // Move this to the very top
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        function hideErrorMessage() {
            var errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.style.display = 'block';
                setTimeout(function () {
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
            successOverlay.style.backgroundColor = 'rgba(0, 128, 0, 0.5)';  // Semi-transparent green
            successOverlay.style.display = 'flex';
            successOverlay.style.justifyContent = 'center';
            successOverlay.style.alignItems = 'center';
            successOverlay.style.zIndex = '9999';  // Ensure it appears above everything
            successOverlay.style.opacity = '0';   // Initially invisible
            successOverlay.style.transition = 'opacity 1s ease-out';  // Fade transition
            document.body.appendChild(successOverlay);

            // Add a subtle success icon (checkmark)
            var successIcon = document.createElement('i');
            successIcon.classList.add('fas', 'fa-check-circle');
            successIcon.style.color = 'white';
            successIcon.style.fontSize = '3rem';
            successOverlay.appendChild(successIcon);

            // Fade in the overlay and icon
            setTimeout(() => {
                successOverlay.style.opacity = '1';  // Fade to 100% opacity
            }, 100);

            // Redirect after a brief delay
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 3000);  // Redirect after 3 seconds
        }


        document.addEventListener('DOMContentLoaded', function () {
            const passwordField = document.querySelector('#password');
            const togglePassword = document.querySelector('.toggle-password');

            togglePassword.addEventListener('click', function () {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.classList.toggle('active');

                // Change the eye icon based on the visibility of the password
                const eyeIcon = this.querySelector('i');
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                    passwordField.style.backgroundImage = "url('assets/padlock.png')";
                } else {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                    passwordField.style.backgroundImage = "url('assets/padlock.png')";
                }
            });
        });
    </script>
</head>

<body class="login-page">
    <img src="assets/logologin.png" alt="Logo" class="logo">
    <h1>Login</h1>
    <div class="login-container-fields">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="input-fields">
                <label for="username" class="inputs"></label>
                <input type="text" id="username" name="username" placeholder="Username" autocomplete="username"
                    required>
                <label for="password" class="inputs"></label>
                <input type="password" id="password" name="password" placeholder="Password"
                    autocomplete="current-password" required>
                <span toggle="#password" class="field-icon toggle-password"><i class="fas fa-eye-slash"></i></span>
                <div class="error-success">
                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        require('main/connection.php');
                        $username = $_POST['username'];
                        $password = $_POST['password'];

                        // Fetch user data based on the provided username
                        $query = "SELECT id, role, status, name, profile_picture_path, password FROM admin_users WHERE username = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $stmt->store_result();

                        if ($stmt->num_rows > 0) {
                            $stmt->bind_result($userId, $userRole, $userStatus, $name, $profile_picture_path, $storedPassword);
                            $stmt->fetch();

                            // Check if the stored password is a hashed password
                            $isPasswordHashed = strlen($storedPassword) === 60 && strpos($storedPassword, '$2y$') === 0;

                            if ($isPasswordHashed) {
                                // Password is hashed, verify using password_verify
                                if (password_verify($password, $storedPassword)) {
                                    loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path);
                                } else {
                                    showErrorMessage();
                                }
                            } else {
                                // Password is plain text, compare directly
                                if ($password === $storedPassword) {
                                    // Update the password to a hashed version
                                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                                    $updatePasswordQuery = "UPDATE admin_users SET password = ? WHERE id = ?";
                                    $updateStmt = $conn->prepare($updatePasswordQuery);
                                    $updateStmt->bind_param("si", $hashedPassword, $userId);
                                    $updateStmt->execute();
                                    $updateStmt->close();

                                    loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path);
                                } else {
                                    showErrorMessage();
                                }
                            }
                        } else {
                            showErrorMessage();
                        }

                        $stmt->close();
                        $conn->close();
                    }

                    function loginUser($conn, $userId, $userRole, $userStatus, $name, $profile_picture_path)
                    {
                        if ($userRole == 1) {
                            if ($userStatus == 1) {
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
                                echo '<p id="error-message" class="error-message">This account is not active!<br>Please contact Super Admin.</p>';
                                echo '<script>hideErrorMessage();</script>';
                            }
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
                            showErrorMessage();
                        }
                    }

                    function showErrorMessage()
                    {
                        echo '<p id="error-message" class="error-message">Invalid username or password!</p>';
                        echo '<script>hideErrorMessage();</script>';
                    }
                    ?>

                </div>
            </div>
            <button class="login-submit" type="submit">LOGIN</button>
        </form>
    </div>
    <a href="index.php" class="redirect-button">&#10096;&#10096;</a>
</body>


</html>