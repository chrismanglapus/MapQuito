<?php
require('main/connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];
    $sql = "SELECT * FROM morbidity_data WHERE id = '$case_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "Case not found"]);
    }
}
