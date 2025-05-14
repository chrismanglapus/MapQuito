<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <!-- ERROR TOAST -->
    <div id="toast" class="toast"></div>

    <!-- LOGIN MODAL -->
    <div id="loginModal" class="modal">
        <div class="modal-content">

            <!-- Left Side (Image + Title) -->
            <div class="modal-left">
                <img src="assets/logologin.png" alt="Login Logo">
                <div class="modal-text">
                    <h2>MapQuito</h2>
                    <p>A Dengue Virus Dynamic Heatmap</p>
                </div>
            </div>

            <!-- Right Side (Login Form) -->
            <div class="modal-right">
                <span class="modal-close" onclick="closeLoginModal()">&times;</span>
                <h2>Login</h2>
                <form id="login-form">
                    <input type="text" id="username" name="username" placeholder="Username" required>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <button type="submit">LOGIN</button>
                </form>
            </div>

        </div>

    </div>

</body>

<script src="js/login.js" defer></script>

</html>