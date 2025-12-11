<?php 
include 'db.php'; 

$ic = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

// 1. GET MODE (NFC vs QR)
$raw_mode = isset($_GET['mode']) ? $_GET['mode'] : 'qr';
$mode = strtolower(trim($raw_mode)); 
$is_nfc_emergency = ($mode === 'nfc');

$patient = null;
$records = null;

if($ic) {
    // Get Basic Info
    $sql = "SELECT * FROM patients WHERE ic_number = '$ic'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        
        // Log Access (Logs only once per scan now, not every time you filter)
        $log_type = $is_nfc_emergency ? 'EMERGENCY_OVERRIDE' : 'PATIENT_CONSENT';
        $alert_status = $is_nfc_emergency ? 1 : 0;
        
        $log_sql = "INSERT INTO access_logs (ic_number, doctor_name, access_method, alert_sent) 
                    VALUES ('$ic', 'Dr. Scanner (Mobile)', '$log_type', '$alert_status')";
        $conn->query($log_sql);

        // 2. GET ALL HISTORY (We removed the SQL Filter)
        // We load everything now and filter with JavaScript later
        $sql_history = "SELECT * FROM medical_records WHERE ic_number = '$ic' ORDER BY created_at DESC";
        $records = $conn->query($sql_history);
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
        :root { --primary-color: #00bf8f; --bg-color: #f0f2f5; }
        body { background-color: var(--bg-color); font-family: -apple-system, sans-serif; padding-bottom: 80px; }
        
        .app-header { background: linear-gradient(135deg, #004d40 0%, #009688 100%); color: white; padding: 20px 20px 50px 20px; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; margin-bottom: -30px; }
        .profile-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); text-align: center; margin-bottom: 20px; }
        .avatar-circle { width: 70px; height: 70px; background: #005c4b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: bold; margin: 0 auto 10px auto; border: 4px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        .info-box { background: white; border-radius: 15px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center; height: 100%; }
        .info-value { font-size: 1.2rem; font-weight: 800; color: #212529; }
        
        /* Timeline Items */
        .timeline-item { background: white; border-radius: 16px; padding: 16px; margin-bottom: 15px; border-left: 5px solid #ccc; transition: all 0.3s ease; }
        .type-X-Ray { border-left-color: #007bff; } 
        .type-CT-Scan { border-left-color: #9c27b0; } 
        .type-Allergy { border-left-color: #dc3545; }
        
        .scan-img { width: 100%; border-radius: 10px; margin-top: 10px; }
        
        /* Filter Chips - Now Buttons, not Links */
        .filter-scroll { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
        .filter-chip { 
            white-space: nowrap; padding: 8px 16px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; 
            border: 1px solid #ddd; color: #555; background: white; cursor: pointer;
        }
        .chip-active { background-color: #004d40; color: white; border-color: #004d40; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        
        /* Security Overlay */
        #nfc-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.95); z-index: 2000; 
            /* Controlled by PHP initially */
            display: none; 
            flex-direction: column; justify-content: center; align-items: center; 
            padding: 20px; text-align: center; color: white; 
        }
        .pulse-red { animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
        
        #data { display: none; }
        #loading { display: flex; }
    </style>
</head>
<body>

    <!-- Loading -->
    <div id="loading" class="text-center flex-column justify-content-center align-items-center vh-100 bg-white" style="position:fixed; top:0; left:0; width:100%; z-index:999;">
        <div class="spinner-grow text-success mb-3" style="width: 3rem; height: 3rem;"></div>
        <h5 class="fw-bold text-dark">Handshaking with National ID...</h5>
    </div>

    <!-- === BREAK GLASS MODAL === -->
    <div id="nfc-overlay" style="<?php echo $is_nfc_emergency ? 'display:flex !important;' : 'display:none;'; ?>">
        <div class="mb-4">
            <i class="fa-solid fa-triangle-exclamation text-danger" style="font-size: 5rem;"></i>
        </div>
        <h2 class="fw-bold text-danger mb-2">EMERGENCY ACCESS</h2>
        <p class="text-white-50 mb-4">You are bypassing patient consent protocols.</p>
        
        <div class="bg-dark border border-danger p-3 rounded mb-4 text-start" style="width: 100%;">
            <div class="d-flex align-items-center mb-2 text-danger fw-bold">
                <i class="fa-solid fa-gavel me-2"></i> SYSTEM AUDIT
            </div>
            <small class="d-block text-white">1. Access will be permanently logged.</small>
            <small class="d-block text-white">2. SMS Alert sent to Next-of-Kin.</small>
        </div>

        <button onclick="unlockEmergency()" class="btn btn-danger btn-lg w-100 fw-bold py-3 pulse-red shadow">
            BREAK GLASS & PROCEED
        </button>
    </div>

    <!-- === MAIN CONTENT === -->
    <div id="data">
        
        <div id="qr-success-banner" class="bg-success text-white p-2 text-center small fw-bold" style="display:none;">
            <i class="fa-solid fa-check-circle me-1"></i> Patient Consent Verified (Dynamic QR)
        </div>
        
        <div id="nfc-warning-banner" class="bg-danger text-white p-2 text-center small fw-bold" style="display:none;">
            <i class="fa-solid fa-lock-open me-1"></i> EMERGENCY OVERRIDE ACTIVE
        </div>

        <div class="app-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold"><i class="fa-solid fa-shield-heart me-2"></i>MyHealthID</h5>
            </div>
        </div>

        <div class="container" style="margin-top: -30px;">
            <?php if($patient): ?>
            <div class="profile-card">
                <div class="avatar-circle"><?php echo substr($patient['name'], 0, 1); ?></div>
                <h4 class="fw-bold mb-0"><?php echo $patient['name']; ?></h4>
                <p class="text-muted small mb-2"><?php echo $patient['ic_number']; ?></p>
                <span class="badge bg-success rounded-pill px-3">Identity Verified</span>
            </div>

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
                        <a href="tel:<?php echo $patient['emergency_contact']; ?>" class="info-value text-decoration-none d-block text-truncate">
                            <?php echo $patient['emergency_contact']; ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php if($patient['allergy'] && $patient['allergy'] != 'None'): ?>
            <div class="alert alert-danger shadow-sm d-flex align-items-center gap-3">
                <i class="fa-solid fa-skull-crossbones fs-3"></i>
                <div>
                    <small class="fw-bold text-uppercase">Severe Allergy</small>
                    <div class="fw-bold fs-5"><?php echo $patient['allergy']; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- INSTANT FILTER CHIPS (NO PAGE RELOAD) -->
            <div class="filter-scroll mb-3 mt-4">
                <button onclick="filterContent('All', this)" class="filter-chip chip-active">All Records</button>
                <button onclick="filterContent('X-Ray', this)" class="filter-chip">🩻 X-Ray</button>
                <button onclick="filterContent('CT Scan', this)" class="filter-chip">🧠 CT Scan</button>
                <button onclick="filterContent('Allergy', this)" class="filter-chip">⚠️ Allergy</button>
                <button onclick="filterContent('Lab Result', this)" class="filter-chip">🩸 Lab</button>
                <button onclick="filterContent('Diagnosis', this)" class="filter-chip">📋 Clinical Diagnosis</button>

            </div>

            <div id="timeline-container">
            <?php if($records && $records->num_rows > 0): ?>
                <?php while($row = $records->fetch_assoc()): 
                    $cssClass = str_replace(' ', '-', $row['category']); ?>
                    
                    <!-- We add a 'data-category' attribute here for JavaScript to find -->
                    <div class="timeline-item type-<?php echo $cssClass; ?>" data-category="<?php echo $row['category']; ?>">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="badge bg-dark"><?php echo $row['category']; ?></span>
                            <small class="text-muted"><?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                        </div>
                        <p class="mb-1 fw-bold"><?php echo $row['description']; ?></p>
                        <?php if($row['attachment']): ?>
                            <img src="uploads/<?php echo $row['attachment']; ?>" class="scan-img shadow-sm" onclick="showImage(this.src)">
                        <?php endif; ?>
                        <small class="text-muted d-block mt-2 fst-italic text-end"><?php echo $row['doctor_name']; ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted py-4">No records found.</div>
            <?php endif; ?>
            </div>

            <?php else: ?>
                <div class="alert alert-danger text-center mt-4">Patient ID Invalid</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
          <div class="modal-body p-0 text-center"><img id="modalImage" src="" class="img-fluid rounded shadow"></div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var isEmergency = <?php echo $is_nfc_emergency ? 'true' : 'false'; ?>;

        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                document.getElementById('loading').style.display = 'none';
                
                if (isEmergency) {
                    // NFC: Overlay handled by PHP style, just ensure data is hidden behind it
                } else {
                    // QR: Show Data + Green Banner
                    document.getElementById('qr-success-banner').style.display = 'block';
                    document.getElementById('data').style.display = 'block';
                }
            }, 1000);
        });

        // Break Glass Function
        function unlockEmergency() {
            document.getElementById('nfc-overlay').style.display = 'none';
            document.getElementById('data').style.display = 'block';
            document.getElementById('nfc-warning-banner').style.display = 'block';
        }

        // --- NEW INSTANT FILTER LOGIC ---
        function filterContent(category, btnElement) {
            // 1. Update Buttons (Visual)
            var buttons = document.getElementsByClassName('filter-chip');
            for(var i=0; i<buttons.length; i++) {
                buttons[i].classList.remove('chip-active');
            }
            btnElement.classList.add('chip-active');

            // 2. Hide/Show Items
            var items = document.getElementsByClassName('timeline-item');
            for(var i=0; i<items.length; i++) {
                var itemCat = items[i].getAttribute('data-category');
                
                if(category === 'All' || itemCat === category) {
                    items[i].style.display = 'block';
                } else {
                    items[i].style.display = 'none';
                }
            }
        }

        function showImage(src) {
            document.getElementById('modalImage').src = src;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
    </script>
</body>
</html>