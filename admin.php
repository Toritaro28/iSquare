<?php 
session_start();
include 'db.php'; 

// 1. SECURITY CHECK (Redirect if not logged in)
if(!isset($_SESSION['doctor_id'])) {
    header("Location: login.php");
    exit();
}

$current_doc = $_SESSION['doctor_name'];
$current_hosp = $_SESSION['hospital_name'];
$hosp_type = $_SESSION['hospital_type'];

$msg = "";

// Handle New Record Submission
if(isset($_POST['add_record'])) {
    $ic = $_POST['ic_target'];
    $cat = $_POST['category'];
    $raw_desc = $_POST['description'];
    
    // Auto-Format: "[Hospital Name] Description"
    $final_desc = "[$current_hosp] " . $raw_desc;

    // File Upload Logic
    $attachment = NULL;
    if(isset($_FILES['medical_image']) && $_FILES['medical_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $filename = time() . "_" . basename($_FILES["medical_image"]["name"]);
        $target_file = $target_dir . $filename;
        if(move_uploaded_file($_FILES["medical_image"]["tmp_name"], $target_file)) {
            $attachment = $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO medical_records (ic_number, category, description, doctor_name, attachment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $ic, $cat, $final_desc, $current_doc, $attachment);
    
    if($stmt->execute()) {
        $msg = "<div class='alert alert-success'>Record successfully synced to Cloud!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Clinical Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f4f6f9; }
        .top-nav { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .verified-box { display: none; margin-top: 5px; padding: 8px; border-radius: 5px; font-size: 0.9rem; }
    </style>
</head>
<body>

    <!-- NAV BAR -->
    <div class="top-nav">
        <div class="d-flex align-items-center">
            <h4 class="mb-0 me-3">🏥 MyHealthID</h4>
            <span class="badge bg-primary"><?php echo $hosp_type; ?> Sector</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <div class="fw-bold"><?php echo $current_doc; ?></div>
                <div class="small text-muted"><?php echo $current_hosp; ?></div>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>

    <div class="container mt-4">
        <?php echo $msg; ?>

        <div class="row">
            <!-- INPUT FORM -->
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold">
                        Add Clinical Record
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            
                            <!-- 1. IC NUMBER WITH LIVE CHECK -->
                            <div class="mb-3">
                                <label class="fw-bold">Patient IC Number</label>
                                <div class="input-group">
                                    <input type="text" name="ic_target" id="ic_input" class="form-control" placeholder="e.g. 900101-14-1234" required autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="checkBtn">Check</button>
                                </div>
                                
                                <!-- The "Column Below" logic -->
                                <div id="name_display" class="verified-box alert-success">
                                    ✅ Verified: <strong>Ali Bin Ahmad</strong>
                                </div>
                                <div id="error_display" class="verified-box alert-danger">
                                    ❌ Error: Patient Not Found!
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Record Type</label>
                                <select name="category" class="form-select">
                                    <option value="X-Ray">🩻 X-Ray / Imaging</option>
                                    <option value="Lab Result">🩸 Lab Report</option>
                                    <option value="Allergy">⚠️ New Allergy Found</option>
                                    <option value="Diagnosis">📋 Clinical Diagnosis</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Findings / Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter clinical details..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Attachment (Image)</label>
                                <input type="file" name="medical_image" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" name="add_record" class="btn btn-success w-100 fw-bold">Submit Record</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- PREVIEW LIST -->
            <div class="col-md-7">
                <h4>Recent System Uploads</h4>
                <div class="list-group shadow-sm">
                    <?php 
                    $rec = $conn->query("SELECT * FROM medical_records ORDER BY id DESC LIMIT 5");
                    while($r = $rec->fetch_assoc()): 
                    ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 text-primary"><?php echo $r['category']; ?></h5>
                                <small class="text-muted"><?php echo date('d M H:i', strtotime($r['created_at'])); ?></small>
                            </div>
                            <p class="mb-1 fw-bold"><?php echo $r['description']; ?></p>
                            
                            <?php if($r['attachment']): ?>
                                <img src="uploads/<?php echo $r['attachment']; ?>" style="height: 50px; border:1px solid #ddd; border-radius:4px;" class="mt-1">
                            <?php endif; ?>

                            <div class="mt-1 small text-muted">
                                Signed by: <?php echo $r['doctor_name']; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT FOR LIVE IC CHECK -->
    <script>
        $(document).ready(function(){
            
            // Function to run the check
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
                                $('#name_display').html('✅ Verified: <strong>' + response.name + '</strong>').slideDown();
                            } else {
                                $('#name_display').hide();
                                $('#error_display').slideDown();
                            }
                        }
                    });
                }
            }

            // Run check when "Check" button is clicked
            $('#checkBtn').click(function(){
                checkIC();
            });

            // Also run check when user leaves the input box (blur)
            $('#ic_input').blur(function(){
                checkIC();
            });
        });
    </script>
</body>
</html>