<?php
require('main/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_id = $_POST['case_id'];
    $cases = $_POST['cases'];

    $sql = "UPDATE morbidity_data SET cases = '$cases' WHERE id = '$case_id'";

    if ($conn->query($sql) === TRUE) {
        echo "Success";
    } else {
        echo "Error updating case: " . $conn->error;
    }
}
