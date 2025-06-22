<?php
header('Content-Type: application/json');

if (!isset($_POST['population']) || !is_array($_POST['population'])) {
    echo json_encode(['status' => 'error', 'message' => 'No population data received.']);
    exit;
}

$populationData = $_POST['population'];
$filepath = __DIR__ . '/main/barangay_population.php';  // adjust if needed

// Build PHP array string
$phpCode = "<?php\n\n\$barangay_population = [\n";

foreach ($populationData as $barangay => $population) {
    $barangaySafe = addslashes($barangay); // escape special chars
    $populationSafe = intval($population);
    $phpCode .= "    '{$barangaySafe}' => {$populationSafe},\n";
}

$phpCode .= "];\n";

// Try saving the file
if (file_put_contents($filepath, $phpCode)) {
    echo json_encode(['status' => 'success', 'message' => 'Population updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update population file.']);
}
