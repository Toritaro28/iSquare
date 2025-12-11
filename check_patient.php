<?php
include 'db.php';
header('Content-Type: application/json');

if(isset($_POST['ic'])) {
    $ic = $conn->real_escape_string($_POST['ic']);
    
    // 1. Check if Patient Exists
    $sql = "SELECT patient_id, name, allergy, blood_type FROM patients WHERE ic_number = '$ic'";
    $result = $conn->query($sql);

    if($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        $p_id = $patient['patient_id'];
        
        // 2. Fetch Medical History (JOIN DOCTORS)
        $hist_sql = "SELECT m.category, m.description, m.created_at, d.name AS doc_name 
                     FROM medical_records m 
                     JOIN doctors d ON m.doctor_id = d.doctor_id
                     WHERE m.patient_id = '$p_id' 
                     ORDER BY m.created_at DESC LIMIT 3";
        $hist_result = $conn->query($hist_sql);
        
        $history = [];
        while($row = $hist_result->fetch_assoc()) {
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