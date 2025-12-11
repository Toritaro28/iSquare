<?php
include 'db.php';

if(isset($_POST['ic'])) {
    $ic = $conn->real_escape_string($_POST['ic']);
    $sql = "SELECT name, allergy FROM patients WHERE ic_number = '$ic'";
    $result = $conn->query($sql);

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Return JSON data found
        echo json_encode(["status" => "found", "name" => $row['name'], "allergy" => $row['allergy']]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}
?>