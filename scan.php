<?php 
include 'db.php'; 

$ic = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
$patient = null;
$records = null;

if($ic) {
    // 1. Get Basic Info
    $sql = "SELECT * FROM patients WHERE ic_number = '$ic'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        
        // 2. Log Access (Security)
        $conn->query("INSERT INTO access_logs (ic_number, doctor_name) VALUES ('$ic', 'Dr. Scanner (Mobile)')");

        // 3. Get History (Ordered by Date)
        $records = $conn->query("SELECT * FROM medical_records WHERE ic_number = '$ic' ORDER BY created_at DESC");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MyHealthID Provider</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #00bf8f; /* Modern Teal */
            --primary-dark: #005c4b;
            --bg-color: #f0f2f5;
        }
        body { background-color: var(--bg-color); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding-bottom: 80px; }
        
        /* Modern Header */
        .app-header {
            background: linear-gradient(135deg, #004d40 0%, #009688 100%);
            color: white;
            padding: 20px 20px 50px 20px;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 20px rgba(0,77,64,0.15);
            margin-bottom: -30px;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            position: relative;
            margin-bottom: 20px;
        }
        .avatar-circle {
            width: 70px; height: 70px;
            background: var(--primary-dark);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: bold;
            margin: 0 auto 10px auto;
            border: 4px solid white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        /* Vitals Grid */
        .info-box {
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            height: 100%;
        }
        .info-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 1.2rem; font-weight: 800; color: #212529; margin-top: 5px; }
        
        /* Allergy Warning */
        .allergy-box {
            background: #fff5f5;
            border: 1px solid #ffc9c9;
            color: #c53030;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }

        /* Timeline Styling */
        .timeline-item {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 15px;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            border-left: 5px solid #ccc;
        }
        .type-X-Ray { border-left-color: #007bff; }
        .type-CT-Scan { border-left-color: #9c27b0; }
        .type-Allergy { border-left-color: #dc3545; }
        .type-Lab-Result { border-left-color: #ffc107; }

        .scan-img { width: 100%; border-radius: 10px; margin-top: 10px; border: 1px solid #eee; }
        
        #data { display: none; }
        #loading { display: flex; }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div id="loading" class="text-center flex-column justify-content-center align-items-center vh-100 bg-white" style="position:fixed; top:0; left:0; width:100%; z-index:999;">
        <div class="spinner-grow text-success mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <h5 class="fw-bold text-dark">Accessing Secure Cloud...</h5>
        <p class="text-muted small">Verifying Biometrics</p>
    </div>

    <div id="data">
        <!-- 1. HEADER -->
        <div class="app-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="m-0 fw-bold"><i class="fa-solid fa-shield-heart me-2"></i>MyHealthID</h5>
                    <small class="opacity-75">Provider Access Mode</small>
                </div>
                <div class="badge bg-white text-dark shadow-sm px-3 py-2 rounded-pill">
                    Live
                </div>
            </div>
        </div>

        <div class="container" style="margin-top: -30px;">
            <?php if($patient): ?>
            
            <!-- 2. PATIENT PROFILE -->
            <div class="profile-card">
                <div class="avatar-circle">
                    <?php echo substr($patient['name'], 0, 1); ?>
                </div>
                <h4 class="fw-bold mb-0"><?php echo $patient['name']; ?></h4>
                <p class="text-muted small mb-2"><?php echo $patient['ic_number']; ?></p>
                <span class="badge bg-success rounded-pill px-3">Identity Verified</span>
            </div>

            <!-- 3. VITALS GRID (Blood & Emergency) -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="info-box">
                        <i class="fa-solid fa-droplet text-danger mb-2 fs-4"></i>
                        <div class="info-label">Blood Type</div>
                        <div class="info-value"><?php echo $patient['blood_type']; ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-box">
                        <i class="fa-solid fa-phone-volume text-success mb-2 fs-4"></i>
                        <div class="info-label">Emergency</div>
                        <!-- Click to Call Feature -->
                        <a href="tel:<?php echo $patient['emergency_contact']; ?>" class="info-value text-decoration-none d-block text-truncate">
                            <?php echo $patient['emergency_contact']; ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 4. ALLERGY ALERT (Only show if exists) -->
            <?php if($patient['allergy'] && $patient['allergy'] != 'None'): ?>
            <div class="allergy-box shadow-sm">
                <i class="fa-solid fa-triangle-exclamation fs-3"></i>
                <div>
                    <small class="fw-bold text-uppercase" style="letter-spacing:1px;">Critical Allergies</small>
                    <div class="fw-bold fs-5"><?php echo $patient['allergy']; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 5. MEDICAL TIMELINE -->
            <h6 class="text-muted ms-2 mb-3 text-uppercase fw-bold" style="font-size:0.8rem;">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Patient History
            </h6>

            <?php if($records && $records->num_rows > 0): ?>
                <?php while($row = $records->fetch_assoc()): 
                    // Handle space in CSS class name (e.g. "CT Scan" -> "CT-Scan")
                    $cssClass = str_replace(' ', '-', $row['category']);
                ?>
                    <div class="timeline-item type-<?php echo $cssClass; ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-dark text-white rounded-1"><?php echo $row['category']; ?></span>
                            <small class="text-muted"><?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                        </div>
                        
                        <!-- Description -->
                        <p class="mb-1 fw-medium text-dark"><?php echo $row['description']; ?></p>
                        
                        <!-- Image Attachment -->
                        <?php if($row['attachment']): ?>
                            <div class="mt-2 position-relative">
                                <img src="uploads/<?php echo $row['attachment']; ?>" 
                                     class="scan-img shadow-sm" 
                                     onclick="showImage(this.src)" 
                                     style="cursor: pointer;">
                                <div class="position-absolute bottom-0 end-0 m-2 badge bg-dark opacity-75">
                                    <i class="fa-solid fa-magnifying-glass"></i> Zoom
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-2 pt-2 border-top border-light d-flex justify-content-between align-items-center">
                            <small class="text-muted fst-italic" style="font-size:0.75rem">
                                <i class="fa-solid fa-user-doctor me-1"></i> <?php echo $row['doctor_name']; ?>
                            </small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted p-4 bg-white rounded-3">
                    <i class="fa-regular fa-folder-open fs-1 mb-2"></i>
                    <p>No medical history found.</p>
                </div>
            <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-danger text-center shadow">
                    <h4>Invalid Scan</h4>
                    <p>Patient ID not found in National Registry.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
          <div class="modal-body text-center p-0">
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index:1000;"></button>
            <img id="modalImage" src="" class="img-fluid rounded shadow" style="max-height: 85vh;">
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fake Loading Animation
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('data').style.display = 'block';
            }, 1200);
        });

        // Zoom Logic
        function showImage(src) {
            document.getElementById('modalImage').src = src;
            var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
            myModal.show();
        }
    </script>
</body>
</html>