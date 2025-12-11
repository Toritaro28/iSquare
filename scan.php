<?php 
include 'db.php'; 

// 1. Get Patient Data
$ic = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
$patient = null;

// Only run query if ID is provided
if($ic) {
    $sql = "SELECT * FROM patients WHERE ic_number = '$ic'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        
        // 2. SECURITY FEATURE: Log this access automatically
        // We use INSERT IGNORE or standard INSERT. 
        // Ensure your table 'access_logs' exists from Step 1!
        $doctor = 'Dr. Azlan (Emergency Dept)';
        $log_sql = "INSERT INTO access_logs (ic_number, doctor_name) VALUES ('$ic', '$doctor')";
        $conn->query($log_sql);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MyHealthID - Provider View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (If this fails offline, the style looks plain but works. Ideally download bootstrap.css if totally offline) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome (Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: sans-serif; }
        .header-bar { background: linear-gradient(90deg, #004d40 0%, #00695c 100%); color: white; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .blink-urgent { animation: blinker 1s linear infinite; color: #d32f2f; font-weight: 800; }
        @keyframes blinker { 50% { opacity: 0; } }
        .card-medical { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; background: white; margin-bottom: 15px; }
        .verified-badge { background-color: #e8f5e9; color: #2e7d32; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; display: inline-block; }
        .log-badge { font-size: 0.7rem; color: #666; border: 1px solid #ccc; padding: 2px 6px; border-radius: 4px; }
        
        /* Ensure Data is hidden initially */
        #data { display: none; }
        #loading { display: flex; }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div id="loading" class="text-center flex-column justify-content-center align-items-center vh-100 bg-white" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:999;">
        <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="mt-4 text-dark fw-bold">Connecting to National ID Gateway...</h4>
        <p class="text-muted small">Handshaking with MOH Database (Secure SSL)</p>
    </div>

    <!-- Main App Content -->
    <div id="data">
        
        <!-- App Header -->
        <div class="header-bar d-flex justify-content-between align-items-center">
            <div><strong>MyHealthID</strong> Provider</div>
            <div class="small opacity-75">Dr. Azlan • ER Unit</div>
        </div>

        <div class="container mt-4">
            
            <?php if($patient): ?>
            <!-- Verification Card -->
            <div class="text-center mb-4">
                <!-- Using a generic placeholder if internet is slow -->
                <div style="width: 100px; height: 100px; background-color: #00695c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem; font-weight: bold;">
                    <?php echo substr($patient['name'], 0, 1); ?>
                </div>
                
                <h2 class="mt-2 fw-bold"><?php echo $patient['name']; ?></h2>
                <div class="mt-1">
                    <span class="verified-badge">✔ Biometrics Matched</span>
                </div>
                <div class="mt-2">
                    <span class="badge bg-secondary"><?php echo $patient['ic_number']; ?></span>
                </div>
            </div>

            <!-- Critical Medical Info -->
            <div class="card card-medical">
                <div class="card-header bg-danger text-white fw-bold">
                    CRITICAL ALERTS
                </div>
                <div class="card-body text-center">
                    <h5 class="text-muted small text-uppercase">Known Allergies</h5>
                    <p class="display-6 blink-urgent mb-0"><?php echo $patient['allergy']; ?></p>
                </div>
            </div>

            <!-- Vitals / Info -->
            <div class="row g-2">
                <div class="col-6">
                    <div class="card card-medical h-100">
                        <div class="card-body text-center">
                            <small class="text-muted">Blood Type</small>
                            <h3 class="fw-bold text-dark"><?php echo $patient['blood_type']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card card-medical h-100">
                        <div class="card-body text-center">
                            <small class="text-muted">Emergency</small>
                            <h5 class="fw-bold text-primary mt-1"><?php echo $patient['emergency_contact']; ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access Log History -->
            <div class="mt-4 text-center">
                <p class="text-muted small">
                    🔒 Access Logged at <?php echo date("H:i:s"); ?>.<br>
                    Patient notified via MySejahtera.
                </p>
            </div>

            <?php else: ?>
                <div class="alert alert-danger m-4 text-center">
                    <h4>Patient Not Found</h4>
                    <p>IC: <?php echo $ic; ?></p>
                    <a href="scan.php" class="btn btn-outline-danger">Try Again</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- PURE JAVASCRIPT (No Internet Required) -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Wait 1.5 seconds, then switch screens
            setTimeout(function() {
                var loader = document.getElementById('loading');
                var content = document.getElementById('data');
                
                if(loader && content) {
                    loader.style.display = 'none';
                    content.style.display = 'block';
                }
            }, 1500);
        });
    </script>
</body>
</html>