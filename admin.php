<?php 
session_start();
include 'db.php'; 

if(!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

// Session uses ID now, not name
$current_doc_id = $_SESSION['doctor_id']; 
$current_doc_name = $_SESSION['doctor_name'];
$current_hosp = $_SESSION['hospital_name'];
$hosp_type = $_SESSION['hospital_type'];
$msg = "";

// --- DELETE LOGIC ---
if(isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $check = $conn->query("SELECT created_at FROM medical_records WHERE record_id='$del_id'");
    $row = $check->fetch_assoc();
    $rec_date = date('Y-m-d', strtotime($row['created_at']));
    $today = date('Y-m-d');

    if($rec_date == $today) {
        // DELETE CHECK: record_id AND doctor_id
        $stmt = $conn->prepare("DELETE FROM medical_records WHERE record_id=? AND doctor_id=?");
        $stmt->bind_param("ii", $del_id, $current_doc_id);
        if($stmt->execute()) {
            $msg = "<div class='alert alert-warning'>Record deleted successfully.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'><b>Security Violation:</b> You cannot delete records older than 24 hours.</div>";
    }
}

// --- EDIT FETCH LOGIC ---
$edit_mode = false;
$e_id = ""; $e_ic = ""; $e_cat = ""; $e_desc = "";

if(isset($_GET['edit_id'])) {
    $e_id = $_GET['edit_id'];
    
    // JOIN needed to get IC Number back for display
    $stmt = $conn->prepare("SELECT m.*, p.ic_number FROM medical_records m JOIN patients p ON m.patient_id = p.patient_id WHERE m.record_id=? AND m.doctor_id=?");
    $stmt->bind_param("ii", $e_id, $current_doc_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $rec_date = date('Y-m-d', strtotime($row['created_at']));
        $today = date('Y-m-d');
        
        if($rec_date == $today) {
            $edit_mode = true;
            $e_ic = $row['ic_number']; // Display IC
            $e_cat = $row['category'];
            $e_desc = str_replace("[$current_hosp] ", "", $row['description']);
        } else {
            $msg = "<div class='alert alert-danger'><b>Error:</b> This record is locked (Over 24 hours).</div>";
        }
    }
}

// --- HANDLE SUBMISSION ---
if(isset($_POST['save_record'])) {
    $ic = $_POST['ic_target'];
    $cat = $_POST['category'];
    $raw_desc = $_POST['description'];
    $final_desc = "[$current_hosp] " . $raw_desc; 
    
    $attachment = NULL;
    if(isset($_FILES['medical_image']) && $_FILES['medical_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $filename = time() . "_" . basename($_FILES["medical_image"]["name"]);
        $target_file = $target_dir . $filename;
        if(move_uploaded_file($_FILES["medical_image"]["tmp_name"], $target_file)) { $attachment = $filename; }
    }

    // 1. LOOKUP PATIENT ID FROM IC
    $p_check = $conn->query("SELECT patient_id, allergy FROM patients WHERE ic_number = '$ic'");
    if($p_check->num_rows > 0) {
        $p_data = $p_check->fetch_assoc();
        $target_patient_id = $p_data['patient_id'];

        if(isset($_POST['update_id']) && !empty($_POST['update_id'])) {
            // UPDATE: record_id and doctor_id
            $uid = $_POST['update_id'];
            $stmt = $conn->prepare("UPDATE medical_records SET patient_id=?, category=?, description=? WHERE record_id=? AND doctor_id=?");
            $stmt->bind_param("issii", $target_patient_id, $cat, $final_desc, $uid, $current_doc_id);
            if($stmt->execute()) {
                $msg = "<div class='alert alert-info'>Record updated successfully!</div>";
                $edit_mode = false; $e_id = ""; $e_ic = ""; $e_cat = ""; $e_desc = "";
            }
        } else {
            // INSERT: Use IDs
            $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, doctor_id, category, description, attachment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $target_patient_id, $current_doc_id, $cat, $final_desc, $attachment);
            
            if($stmt->execute()) {
                // ALLERGY SYNC LOGIC
                if($cat == "Allergy") {
                    $current_allergies = $p_data['allergy'];
                    if(stripos($current_allergies, 'None') !== false || empty($current_allergies)) {
                        $new_allergies = $raw_desc;
                    } else {
                        if(stripos($current_allergies, $raw_desc) === false) { $new_allergies = $current_allergies . ", " . $raw_desc; } 
                        else { $new_allergies = $current_allergies; }
                    }
                    $update_p = $conn->prepare("UPDATE patients SET allergy = ? WHERE patient_id = ?");
                    $update_p->bind_param("si", $new_allergies, $target_patient_id);
                    $update_p->execute();
                    $msg = "<div class='alert alert-success'><b>Success!</b> Allergy synced to Profile.</div>";
                } else {
                    $msg = "<div class='alert alert-success'>Record synced to National Cloud!</div>";
                }
            }
        }
    } else {
        $msg = "<div class='alert alert-danger'>Error: Patient IC not found in database.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Clinical Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f4f6f9; }
        .top-nav { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .verified-box { display: none; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .edit-mode-banner { background-color: #fff3cd; border: 1px solid #ffecb5; color: #664d03; padding: 10px; margin-bottom: 15px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .btn-locked { background-color: #e9ecef; border: 1px solid #ced4da; color: #6c757d; cursor: not-allowed; font-size: 0.7rem; }
    </style>
</head>
<body>

    <div class="top-nav">
        <div class="d-flex align-items-center">
            <h4 class="mb-0 me-3">🏥 MyHealthID</h4>
            <span class="badge bg-primary"><?php echo $hosp_type; ?> Sector</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-bold"><?php echo $current_doc_name; ?></div>
                <div class="small text-muted"><?php echo $current_hosp; ?></div>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container mt-4">
        <?php echo $msg; ?>

        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header <?php echo $edit_mode ? 'bg-warning text-dark' : 'bg-dark text-white'; ?> fw-bold">
                        <i class="fa-solid fa-user-doctor"></i> 
                        <?php echo $edit_mode ? 'Edit Clinical Record (ID: '.$e_id.')' : 'Add Clinical Record'; ?>
                    </div>
                    <div class="card-body">
                        <?php if($edit_mode): ?>
                        <div class="edit-mode-banner">
                            <span><i class="fa-solid fa-pen-to-square"></i> Editing active record.</span>
                            <a href="admin.php" class="btn btn-sm btn-outline-dark">Cancel</a>
                        </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="update_id" value="<?php echo $e_id; ?>">
                            <div class="mb-3">
                                <label class="fw-bold">Patient IC Number</label>
                                <div class="input-group">
                                    <input type="text" name="ic_target" id="ic_input" class="form-control" 
                                           value="<?php echo $e_ic; ?>" placeholder="e.g. 900101-14-1234" required autocomplete="off">
                                    <button class="btn btn-secondary" type="button" id="checkBtn">Verify Identity</button>
                                </div>
                            </div>
                            
                            <div id="patient_panel" style="display:none;">
                                <div class="alert alert-success d-flex justify-content-between align-items-center">
                                    <div>✅ <strong><span id="p_name"></span></strong><br><small>Blood: <span id="p_blood"></span></small></div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <div class="card mb-3">
                                    <div class="card-header bg-light small text-muted fw-bold">History (Last 3)</div>
                                    <ul class="list-group list-group-flush" id="history_list"></ul>
                                </div>
                            </div>
                            <div id="error_display" class="verified-box alert-danger">❌ Error: Patient Not Found!</div>
                            <hr>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Record Type</label>
                                    <select name="category" class="form-select">

                                        <option value="X-Ray" <?php if($e_cat=='X-Ray') echo 'selected'; ?>>🩻 X-Ray</option>
                                        <option value="CT Scan" <?php if($e_cat=='CT Scan') echo 'selected'; ?>>🧠 CT Scan</option>
                                        <option value="Lab Result" <?php if($e_cat=='Lab Result') echo 'selected'; ?>>🩸 Lab Report</option>
                                        <option value="Allergy" <?php if($e_cat=='Allergy') echo 'selected'; ?>>⚠️ Allergy </option>
                                        <option value="Diagnosis" <?php if($e_cat=='Diagnosis') echo 'selected'; ?>>📋 Clinical Diagnosis</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Attachment</label>
                                    <input type="file" name="medical_image" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Findings / Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter details..." required><?php echo $e_desc; ?></textarea>
                            </div>
                            <button type="submit" name="save_record" class="btn <?php echo $edit_mode ? 'btn-warning' : 'btn-primary'; ?> w-100 fw-bold">
                                <?php echo $edit_mode ? 'Update Record' : 'Submit Record'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">📂 <?php echo $current_hosp; ?> (Local Logs)</div>
                    <div class="list-group list-group-flush">
                        <?php 
                        $search_term = '[' . $current_hosp . ']%';
                        // JOIN DOCTORS TO GET NAME
                        $rec = $conn->query("SELECT m.*, d.name AS doc_name 
                                             FROM medical_records m 
                                             JOIN doctors d ON m.doctor_id = d.doctor_id 
                                             WHERE m.description LIKE '$search_term' 
                                             ORDER BY m.created_at DESC LIMIT 5");
                        $today_date = date('Y-m-d');

                        if($rec->num_rows > 0):
                            while($r = $rec->fetch_assoc()): 
                                // ID comparison
                                $is_mine = ($r['doctor_id'] == $current_doc_id);
                                $rec_date = date('Y-m-d', strtotime($r['created_at']));
                                $is_today = ($rec_date == $today_date);
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-primary"><?php echo $r['category']; ?></h6>
                                        <small class="text-muted fst-italic">
                                            <?php echo $is_mine ? 'My Entry' : $r['doc_name']; ?>
                                            • <?php echo date('d M H:i', strtotime($r['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php if($is_mine): ?>
                                        <?php if($is_today): ?>
                                            <div class="btn-group">
                                                <a href="admin.php?edit_id=<?php echo $r['record_id']; ?>" class="btn btn-outline-warning btn-sm py-0" style="font-size: 0.7rem;">Edit</a>
                                                <a href="admin.php?delete_id=<?php echo $r['record_id']; ?>" class="btn btn-outline-danger btn-sm py-0" style="font-size: 0.7rem;" onclick="return confirm('Delete this record?');">Del</a>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-locked btn-sm py-0" onclick="alert('SECURITY PROTOCOL:\nRecord locked (24h+).');"><i class="fa-solid fa-lock"></i> Locked</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-1 mt-1 small text-dark"><?php echo str_replace("[$current_hosp] ", "", $r['description']); ?></p>
                            </div>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                            <div class="p-3 text-center text-muted small">No recent uploads found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            function checkIC() {
                var icNum = $('#ic_input').val();
                if(icNum.length > 5) {
                    $.ajax({
                        url: 'check_patient.php',
                        type: 'POST',
                        data: {ic: icNum},
                        dataType: 'json',
                        success: function(response){
                            if(response.status == 'found') {
                                $('#error_display').hide();
                                $('#p_name').text(response.name);
                                $('#p_blood').text(response.blood);
                                var historyHtml = '';
                                if(response.history.length > 0) {
                                    response.history.forEach(function(item) {
                                        historyHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                        historyHtml += '<div><strong>' + item.category + '</strong><br><small class="text-muted">' + item.description + '</small></div>';
                                        historyHtml += '<span class="badge bg-secondary rounded-pill">' + item.date_formatted + '</span>';
                                        historyHtml += '</li>';
                                    });
                                } else { historyHtml = '<li class="list-group-item text-muted text-center">No previous history.</li>'; }
                                $('#history_list').html(historyHtml);
                                $('#patient_panel').slideDown();
                            } else { $('#patient_panel').hide(); $('#error_display').slideDown(); }
                        }
                    });
                }
            }
            $('#checkBtn').click(checkIC);
            $('#ic_input').blur(checkIC);
            if($('#ic_input').val().length > 5) { checkIC(); }
        });
    </script>
</body>
</html>