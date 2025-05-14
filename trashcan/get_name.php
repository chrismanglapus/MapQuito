<?php
session_start();
if (isset($_SESSION['name'])) {
    $nameParts = explode(" ", trim($_SESSION['name']));
    $lastName = end($nameParts); // Get the last element of the array (last name)
    echo htmlspecialchars($lastName);
} else {
    echo "Guest";
}
