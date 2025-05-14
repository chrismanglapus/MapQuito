<?php
require('main/session_users.php');
include('phps/php__create_account.php')
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/create_account.css">
    <title>Create Account | MapQuito</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>

    <main class="create_account__main" id="createAccount">

        <section class="create_account__create_profile">

            <header class="create_account__header">
                <h1>Create an Admin Account</h1>
            </header>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                <div class="field-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="field-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <input type="text" name="password" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>

                <div class="field-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>

                <div class="field-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="text" name="mobile" maxlength="11" pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>

                <div class="btn-container">
                    <button type="submit" class="create-user-btn">SAVE</button>
                    <a href="manage_accounts.php" class="cancel-btn">CANCEL</a>
                </div>

            </form>

            <!-- Error Overlay with Fade-out and Remove -->
            <?php if ($errorMessage): ?>
                <div id="error-overlay">
                    <div class="message-content">
                        <i class="fas fa-times-circle"></i>
                        <p><?php echo $errorMessage; ?></p>
                    </div>
                </div>

                <script>
                    // Show the error overlay with smooth fade-in
                    document.getElementById('error-overlay').style.opacity = '1';

                    // Fade out the error overlay and remove it from the DOM after 3 seconds
                    setTimeout(() => {
                        document.getElementById('error-overlay').style.opacity = '0'; // Fade out the error message
                        setTimeout(() => {
                            document.getElementById('error-overlay').remove(); // Completely remove the overlay from the DOM
                        }, 500); // Wait for fade-out animation to complete before removal
                    }, 3000); // 3 seconds delay
                </script>
            <?php elseif ($successMessage): ?>
                <div id="success-overlay">
                    <div class="message-content">
                        <i class="fas fa-check-circle"></i>
                        <p><?php echo $successMessage; ?></p>
                    </div>
                </div>
                <script>
                    // Show the success overlay with smooth fade-in
                    document.getElementById('success-overlay').style.opacity = '1';

                    // Redirect after a brief delay (3 seconds)
                    setTimeout(() => {
                        window.location.href = 'manage_accounts.php';
                    }, 3000); // 3 seconds delay
                </script>
            <?php endif; ?>

        </section>

    </main>

</body>

</html>