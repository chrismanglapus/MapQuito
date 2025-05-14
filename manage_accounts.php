<?php
session_start();
require('main/connection.php');
if (!isset($_SESSION['username'])) {
    include('403.html');
    exit();
}
require('main/navbar.php');

function getUsersWithRoleOne()
{
    global $conn;
    $users = array();
    $sql = "SELECT id, name, username, mobile, email, status FROM admin_users WHERE role = 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

$usersWithRoleOne = getUsersWithRoleOne();
$message = $_SESSION['message'] ?? "";
unset($_SESSION['message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editUser'])) {
    $userId = intval($_POST['user_id']);
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];

    if ($userId && $name && $email) {
        $sql = "UPDATE admin_users SET name = ?, mobile = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $mobile, $email, $userId);

        if ($stmt->execute()) {
            $_SESSION['message'] = "User updated successfully!";
        } else {
            $_SESSION['message'] = "Failed to update user. Please try again.";
        }
        $stmt->close();
        echo "<script>window.location.href = 'manage_accounts.php';</script>";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {
    $userId = intval($_POST['delete']);

    if ($userId) {
        $sql = "DELETE FROM admin_users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    closeDeleteModal();
                    window.location.href = 'manage_accounts.php?deleted=true';
                });
            </script>";
        } else {
            $deleteError = "Failed to delete user. Please try again.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/manage_accounts.css">
    <title>Manage Accounts | MapQuito</title>
</head>

<body class="theme-orange">

    <?php if (!empty($message)) : ?>
        <div class="alert-message">
            <span><?= $message ?></span>
        </div>
    <?php endif; ?>

    <main class="manage-accounts" id="manageAccounts">

        <!-- ✅ Page Header -->
        <header class="manage-accounts__header">
            <h1>List of Admin Accounts</h1>
        </header>

        <section class="manage-accounts__buttons">
            <form action="manage_accounts__create_account.php" method="get">
                <button type="submit" class="btn-tertiary">Create Account</button>
            </form>
        </section>

        <section class="table-container">
            <?php if (!empty($usersWithRoleOne)) : ?>

                <table>
                    <tr>
                        <th>Account Status</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($usersWithRoleOne as $user) : ?>
                        <tr>
                            <td>
                                <button class='toggle-btn <?= $user["status"] ? 'active' : 'inactive' ?>' onclick='toggleUserStatus(<?= $user["id"] ?>, <?= $user["status"] ?>)'>
                                    <?= $user["status"] ? 'ACTIVE' : 'INACTIVE' ?>
                                </button>
                            </td>
                            <td><?= $user["name"] ?></td>
                            <td><?= $user["username"] ?></td>
                            <td><?= $user["mobile"] ?></td>
                            <td><?= $user["email"] ?></td>
                            <td>
                                <div class='action-buttons'>
                                    <button class='action-btn edit-btn' type='button' onclick='openEditModal(<?= json_encode($user) ?>)'>
                                        <span class='text'>Edit</span>
                                    </button>

                                    <button class='action-btn delete-btn' type='button' onclick='openDeleteModal(<?= $user["id"] ?>)'>
                                        <span class='text'>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else : ?>
                <p>No accounts created. Create one now!</p>
            <?php endif; ?>
        </section>
    </main>

    <!-- Edit Modal -->
    <div id="editModal" class="modal__card">
        <div class="modal__header">
            <h1>Edit User</h1>
        </div>
        <div class="modal__content">
            <form id="editForm" method="post" action="manage_accounts.php">
                <input type="hidden" id="editUserId" name="user_id">

                <label for="editName">Full Name</label>
                <input type="text" id="editName" name="name" required>

                <label for="editMobile">Mobile</label>
                <input type="text" id="editMobile" name="mobile">

                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email">

                <button type="submit" name="editUser" class="modal__edit-btn">Save Changes</button>
                <button type="button" class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>

            </form>
        </div>
    </div>

    <div id="modal-overlay"></div>

    <!-- Delete Modal -->
    <div id="delete-modal" class="modal__card">

        <div class="modal__header">
            <div class="modal__img">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z">
                    </path>
                </svg>
            </div>
        </div>

        <div class="modal__content">
            <span class="modal__title">Are you sure you want to delete this user?</span>

            <form id="delete-form" method="post" action="manage_accounts.php">
                <input type="hidden" name="delete" id="delete-user-id">

                <button type="submit" class="modal__delete-btn">Delete</button>
                <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            </form>

        </div>

    </div>

    <div id="modal-overlay"></div>

    <script>
        function openEditModal(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editName').value = user.name;
            document.getElementById('editMobile').value = user.mobile;
            document.getElementById('editEmail').value = user.email;

            document.getElementById('editModal').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        }

        function openDeleteModal(userId) {
            document.getElementById('delete-user-id').value = userId;
            document.getElementById('delete-modal').style.display = 'block';
            document.querySelector('#modal-overlay').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').style.display = 'none';
            document.querySelector('#modal-overlay').style.display = 'none';
        }

        // Auto-hide message after 3 seconds
        setTimeout(() => {
            const alertMessage = document.querySelector('.alert-message');
            if (alertMessage) {
                alertMessage.style.display = 'none';
            }
        }, 3000);

        function toggleUserStatus(userId, currentStatus) {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    location.reload();
                }
            };

            // Send a request to the server to toggle user status
            xmlhttp.open("GET", "manage_accounts__toggle_status.php?userId=" + userId +
                "&currentStatus=" + currentStatus, true);
            xmlhttp.send();
        }
    </script>

</body>

</html>