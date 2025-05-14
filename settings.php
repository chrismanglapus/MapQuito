<?php
require('main/session_users.php');
include('phps/php__settings.php')
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/settings.css">
    <title>Settings | MapQuito</title>
</head>

<body>

    <main class="settings" id="settings">

        <section class="settings__edit-profile">

            <header class="settings__header">
                <h1>Edit Profile</h1>
            </header>

            <form method="post" action="settings.php" enctype="multipart/form-data">

                <div class="settings__profile-picture">

                    <figure class="profile-picture">
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_picture_path']); ?>" alt="Profile Picture" id="profilePreview">
                    </figure>
                    <!-- Modal for image preview -->
                    <div id="imageModal" class="image-modal">
                        <span class="close-modal">&times;</span>
                        <img class="modal-content" id="modalImage">
                    </div>

                    <div class="file__upload">
                        <label for="upload_profile_picture" class="upload__label">
                            <span class="file_upload-text">Upload Profile Picture</span>
                            <input type="file" name="profile_picture" id="upload_profile_picture" accept="image/*" style="display: none;">
                        </label>
                    </div>

                    <button class="delete__profile-picture">Delete</button>
                    <input type="hidden" name="delete_profile_picture" id="delete_profile_picture" value="0">

                </div>

                <input type="hidden" name="user_id" value="<?php echo isset($user["id"]) ? $user["id"] : ''; ?>">

                <div class="field-group">
                    <label for="new_fname">Full Name</label>
                    <input type="text" name="new_name"
                        value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                        placeholder="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <div class="field-group">
                    <label for="new_username">Username</label>
                    <input type="text" name="new_username"
                        value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                        placeholder="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                        pattern="[^\s]+" title="Spacebar not allowed" required>
                </div>

                <div class="field-group">
                    <label for="new_password">Password</label>
                    <input type="password" name="new_password"
                        placeholder="Enter new password or leave blank to keep current"
                        pattern="[^\s]+" title="Spacebar not allowed">
                </div>

                <div class="field-group">
                    <label for="new_mobile">Mobile Number</label>
                    <input type="text" name="new_mobile"
                        value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>"
                        placeholder="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>"
                        pattern="[^\s]+" title="Spacebar not allowed">
                </div>

                <div class="field-group">
                    <label for="new_email">Email</label>
                    <input type="email" name="new_email"
                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                        placeholder="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                        pattern="[^\s]+" title="Spacebar not allowed">
                </div>

                <div class="btn-container">
                    <button type="submit" name="update_user" class="update-user-btn">SAVE</button>
                    <a href="index.php" class="cancel-btn">CANCEL</a>
                </div>

            </form>

            <!-- Custom Modal -->
            <div id="saveModal" class="custom-modal">
                <div class="custom-modal-content">
                    <p>Updated profile successfully!</p>
                    <button class="close-modal-btn">OK</button>
                </div>
            </div>

        </section>

    </main>

    <script>
        var showModal = <?php echo json_encode($showModal); ?>;
    </script>

    <script src="js/settings.js"></script>

</body>

</html>