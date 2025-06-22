<?php
include 'main/barangay_population.php';
header('Content-Type: application/json');
echo json_encode($barangay_population);
?>
