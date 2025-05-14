<?php
session_start();
require('main/connection.php');
if (!isset($_SESSION['username'])) {
    // Load the 403 error page
    include('403.html');
    exit();
}
require('main/navbar.php');
