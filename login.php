<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        // JavaScript function to hide the error message after a certain time
        function hideErrorMessage() {
            var errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.style.display = 'block';
                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 5000); // 5000 milliseconds (5 seconds), adjust as needed
            }
        }
        // JavaScript function to display the success message and redirect after a delay
        function showSuccessMessageAndRedirect() {
            var successMessage = document.getElementById('success-message');
            if (successMessage) {
                successMessage.style.display = 'block'; // Show the success message

                // Create a new image for the mosquito icon
                var mosquitoIcon = document.createElement('img');
                mosquitoIcon.src = 'assets/loading.png';
                mosquitoIcon.style.width = '45px';
                // Apply styles to the mosquito icon
                mosquitoIcon.style.position = 'absolute';
                mosquitoIcon.style.top = '590px'; // Adjust the top position
                mosquitoIcon.style.left = '940px';

                successMessage.parentNode.appendChild(mosquitoIcon);

                // Add an animation with intervals to create the custom icon effect
                var rotationDegrees = 0;
                var intervalId = setInterval(function() {
                    mosquitoIcon.style.transform = 'rotate(' + (rotationDegrees += 5) + 'deg)';
                }, 50); // Increased the interval duration for a slower rotation

                setTimeout(function() {
                    clearInterval(intervalId); // Stop the animation after a certain time
                    window.location.href = 'index.php'; // Redirect after a delay
                }, 3000); // 3000 milliseconds (3 seconds), adjust as needed
            }
        }
    </script>

</head>

<body class="login-page">
    <div class="login-container">
        <img src="assets/logologin.png" alt="Logo" class="logo">
        <h1>Login</h1>
        <div class="login-container-fields">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="input-fields">
                    <label for="username" class="inputs">Username</label>
                    <input type="text" id="username" name="username" placeholder="Type your username" autocomplete="username" required>
                    <label for="password" class="inputs">Password</label>
                    <input type="password" id="password" name="password" placeholder="Type your password" autocomplete="current-password" required>
                </div>
                <button class="login-submit" type="submit">LOGIN</button>
            </form>
        </div>
        <div class="error-success">
            <?php
            session_start(); // Start the session at the beginning

            // Check if the form is submitted
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                require('connection.php');

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

                // Get user input from the form
                $username = $_POST['username'];
                $password = $_POST['password'];

                // Initialize a variable to control further processing
                $allowLogin = true;

                // Perform a simple query to check if the username is already logged in
                $queryCheckLoggedIn = "SELECT id FROM admin_users WHERE username = ? AND logged_in = 1";
                $stmtCheckLoggedIn = $mysqli->prepare($queryCheckLoggedIn);
                $stmtCheckLoggedIn->bind_param("s", $username);
                $stmtCheckLoggedIn->execute();
                $stmtCheckLoggedIn->store_result();

                // Check if any rows were returned
                if ($stmtCheckLoggedIn->num_rows > 0) {
                    // User is already logged in
                    echo '<p id="error-message" class="error-message">This account is already logged in!</p>';
                    echo '<script>hideErrorMessage();</script>'; // Hide the error message after a certain time
                    $allowLogin = false; // Set the variable to prevent further processing
                }

                if ($allowLogin) {
                    // Perform a simple query to check if the username and password match
                    $query = "SELECT id, role, status FROM admin_users WHERE username = ? AND password = ?";
                    // Use prepared statement to prevent SQL injection
                    $stmt = $mysqli->prepare($query);
                    $stmt->bind_param("ss", $username, $password);
                    $stmt->execute();
                    $stmt->store_result();

                    // Check if any rows were returned
                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($userId, $userRole, $userStatus);
                        $stmt->fetch();

                        // Check if the user is an admin with role=1
                        if ($userRole == 1) {
                            // Check if the user is active (status=1)
                            if ($userStatus == 1) {
                                // Valid login
                                echo '<p id="success-message" class="success-message">Successful, logging in</p>';
                                echo '<script>showSuccessMessageAndRedirect();</script>'; // Display success message and redirect after a delay

                                // Update login date and set logged_in to 1
                                $sqlUpdateLoginStatus = "UPDATE admin_users SET login_date = NOW(), logged_in = 1 WHERE id = $userId";
                                $mysqli->query($sqlUpdateLoginStatus);

                                $_SESSION['ADMIN_ROLE'] = $userRole;
                                $_SESSION['username'] = $username;
                            } else {
                                // User is inactive
                                echo '<p id="error-message" class="error-message">This account is not active!<br>Please contact Super Admin.</p>';
                                echo '<script>hideErrorMessage();</script>'; // Hide the error message after a certain time
                            }
                        } elseif ($userRole == 0) {
                            // Valid login for super admin
                            echo '<p id="success-message" class="success-message">Successful, logging in</p>';
                            echo '<script>showSuccessMessageAndRedirect();</script>'; // Display success message and redirect after a delay

                            // Update login date and set logged_in to 1
                            $sqlUpdateLoginStatus = "UPDATE admin_users SET login_date = NOW(), logged_in = 1 WHERE id = $userId";
                            $mysqli->query($sqlUpdateLoginStatus);

                            $_SESSION['ADMIN_ROLE'] = $userRole;
                            $_SESSION['username'] = $username; // Store username in the session

                        } else {
                            // Invalid login for non-admin user
                            echo '<p class="error-message">Invalid username or password!</p>';
                            echo '<script>hideErrorMessage();</script>'; // Hide the error message after a certain time
                        }
                    } else {
                        // Invalid login
                        echo '<p id="error-message" class="error-message">Invalid username or password!</p>';
                        echo '<script>hideErrorMessage();</script>'; // Hide the error message after a certain time
                    }

                    // Close the prepared statement
                    $stmt->close();
                    // Close the database connection
                    $mysqli->close();
                }
            }
            ?>
        </div>
        <a href="index.php" class="redirect-button">&#8592; Go to Dashboard</a>
    </div>
</body>

</html>