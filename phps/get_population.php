<?php
header('Content-Type: application/json');
require('../main/barangay_population.php');

echo json_encode($barangay_population);
