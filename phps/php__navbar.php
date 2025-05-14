<?php
$fullName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Guest';
$nameParts = explode(" ", $fullName);
$firstName = $nameParts[0];
$current_page = basename($_SERVER['PHP_SELF']);
