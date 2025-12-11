<?php
session_start();
include 'db.php';

// Handle Login
if(isset($_POST['login'])) {
    $doctor_id = $_POST['doctor_id'];
    
    // Fetch Doctor + Hospital Details
    $sql = "SELECT doctors.id, doctors.name AS doc_name, hospitals.name AS hosp_name, hospitals.type 
            FROM doctors 
            JOIN hospitals ON doctors.hospital_id = hospitals.id 
            WHERE doctors.id = '$doctor_id'";
            
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // SAVE TO SESSION
        $_SESSION['doctor_id'] = $user['id'];
        $_SESSION['doctor_name'] = $user['doc_name'];
        $_SESSION['hospital_name'] = $user['hosp_name'];
        $_SESSION['hospital_type'] = $user['type']; 
        
        header("Location: admin.php"); // Redirect to Dashboard
        exit();
    } else {
        $error = "Invalid Login Selection.";
    }
}

// 1. Fetch All Hospitals
$hospitals = $conn->query("SELECT * FROM hospitals ORDER BY name ASC");

// 2. Fetch All Doctors
$doctors = $conn->query("SELECT * FROM doctors ORDER BY name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>MyHealthID Provider Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card { 
            width: 100%; 
            max-width: 450px; 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.4); 
            overflow: hidden;
        }
        .card-header-custom {
            background: #00695c;
            color: white;
            padding: 25px;
            text-align: center;
        }
        .form-section { padding: 30px; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="card-header-custom">
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-user-doctor me-2"></i>Provider Portal</h3>
            <p class="mb-0 opacity-75 small">Secure National Health Gateway</p>
        </div>
        
        <div class="form-section">
            <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST">
                
                <!-- STEP 1: SELECT HOSPITAL -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted text-uppercase small">Step 1: Select Facility</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-hospital"></i></span>
                        <select id="hospitalSelect" class="form-select" onchange="filterDoctors()">
                            <option value="" selected disabled>-- Choose Hospital --</option>
                            <?php 
                            if($hospitals->num_rows > 0) {
                                $hospitals->data_seek(0);
                                while($row = $hospitals->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                            <?php 
                                endwhile; 
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- STEP 2: SELECT DOCTOR (Filtered) -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted text-uppercase small">Step 2: Select Identity</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-stethoscope"></i></span>
                        <select name="doctor_id" id="doctorSelect" class="form-select" disabled required>
                            <option value="" selected disabled>-- Select Doctor --</option>
                            
                            <?php 
                            if($doctors->num_rows > 0) {
                                $doctors->data_seek(0);
                                while($doc = $doctors->fetch_assoc()): 
                            ?>
                                <option class="doc-option" 
                                        data-hosp="<?php echo $doc['hospital_id']; ?>" 
                                        value="<?php echo $doc['id']; ?>">
                                    <?php echo $doc['name']; ?>
                                </option>
                            <?php 
                                endwhile; 
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-text small" id="docHelp" style="display:none;">
                        <i class="fa-solid fa-check-circle text-success"></i> Doctors loaded for selected facility.
                    </div>
                </div>
                
                <!-- STEP 3: PIN (Typing Enabled) -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted text-uppercase small">Security Pin</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter PIN (e.g. 1234)">
                </div>

                <button type="submit" name="login" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                    Access System <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
        <div class="bg-light p-3 text-center border-top">
            <small class="text-muted">Restricted Access • MOH Malaysia © 2025</small>
        </div>
    </div>

    <!-- JAVASCRIPT FILTER LOGIC -->
    <script>
        function filterDoctors() {
            var hospitalID = document.getElementById("hospitalSelect").value;
            var doctorSelect = document.getElementById("doctorSelect");
            var allOptions = document.getElementsByClassName("doc-option");
            
            // 1. Reset Doctor Selection
            doctorSelect.value = "";
            doctorSelect.disabled = false; 
            
            // 2. Loop through all doctors
            var count = 0;
            for (var i = 0; i < allOptions.length; i++) {
                var option = allOptions[i];
                if (option.getAttribute("data-hosp") == hospitalID) {
                    option.hidden = false; 
                    option.style.display = "block"; 
                    count++;
                } else {
                    option.hidden = true; 
                    option.style.display = "none";
                }
            }

            // 3. UX Feedback
            var helpText = document.getElementById("docHelp");
            if(count > 0) {
                helpText.style.display = "block";
                helpText.innerHTML = '<i class="fa-solid fa-check-circle text-success"></i> Found ' + count + ' doctors.';
            } else {
                helpText.style.display = "block";
                helpText.innerHTML = '<i class="fa-solid fa-circle-exclamation text-warning"></i> No doctors found.';
            }
        }
    </script>
</body>
</html>