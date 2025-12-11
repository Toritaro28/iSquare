<?php
include 'db.php';

header('Content-Type: application/json');

if(isset($_POST['ic'])) {
    $ic = $conn->real_escape_string($_POST['ic']);
    
    // 1. Check if Patient Exists
    $sql = "SELECT name, allergy, blood_type FROM patients WHERE ic_number = '$ic'";
    $result = $conn->query($sql);

    if($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        
        // 2. Fetch Medical History (Last 3 items)
        $hist_sql = "SELECT category, description, created_at, doctor_name 
                     FROM medical_records 
                     WHERE ic_number = '$ic' 
                     ORDER BY created_at DESC LIMIT 3";
        $hist_result = $conn->query($hist_sql);
        
        $history = [];
        while($row = $hist_result->fetch_assoc()) {
            // Format date nicely (e.g. 12 Dec)
            $row['date_formatted'] = date('d M Y', strtotime($row['created_at']));
            $history[] = $row;
        }

        echo json_encode([
            "status" => "found", 
            "name" => $patient['name'], 
            "blood" => $patient['blood_type'],
            "history" => $history
        ]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}
?>