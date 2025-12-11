<?php 
include 'db.php'; 
$ic = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
$patient = null;
$records = null;

if($ic) {
    $sql = "SELECT * FROM patients WHERE ic_number = '$ic'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        $conn->query("INSERT INTO access_logs (ic_number, doctor_name) VALUES ('$ic', 'Dr. Azlan (Emergency Dept)')");
        $records = $conn->query("SELECT * FROM medical_records WHERE ic_number = '$ic' ORDER BY created_at DESC");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MyHealthID</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: sans-serif; padding-bottom: 50px; }
        .header-bar { background: #00695c; color: white; padding: 15px; }
        .timeline-card { border-left: 4px solid #ccc; margin-bottom: 15px; background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .type-X-Ray { border-left-color: #1976d2; } 
        .type-Allergy { border-left-color: #d32f2f; }
        
        /* Image styling */
        .scan-img { width: 100%; border-radius: 8px; margin-top: 10px; border: 1px solid #ddd; }
        
        #data { display: none; }
        #loading { display: flex; }
    </style>
</head>
<body>

    <!-- Loading -->
    <div id="loading" class="text-center flex-column justify-content-center align-items-center vh-100 bg-white" style="position:fixed; top:0; left:0; width:100%; z-index:999;">
        <div class="spinner-border text-success" role="status"></div>
        <h4 class="mt-4">Retrieving Cloud Records...</h4>
    </div>

    <!-- Data -->
    <div id="data">
        <div class="header-bar"><strong>MyHealthID</strong> Provider View</div>

        <div class="container mt-3">
            <?php if($patient): ?>
            
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:50px; height:50px; font-weight:bold;">
                        <?php echo substr($patient['name'], 0, 1); ?>
                    </div>
                    <div>
                        <h5 class="m-0 fw-bold"><?php echo $patient['name']; ?></h5>
                        <small class="text-muted"><?php echo $patient['ic_number']; ?></small>
                    </div>
                </div>
            </div>

            <!-- TIMELINE -->
            <h6 class="text-muted text-uppercase ms-1 mb-3"><i class="fa-solid fa-file-medical"></i> Medical Records</h6>
            
            <?php if($records && $records->num_rows > 0): ?>
                <?php while($row = $records->fetch_assoc()): ?>
                    <div class="timeline-card type-<?php echo $row['category']; ?>">
                        <div class="d-flex justify-content-between">
                            <strong class="text-uppercase"><?php echo $row['category']; ?></strong>
                            <small class="text-muted"><?php echo date('d M', strtotime($row['created_at'])); ?></small>
                        </div>
                        
                        <p class="mb-1 mt-2"><?php echo $row['description']; ?></p>
                        
                        <!-- IMAGE DISPLAY LOGIC -->
                        <?php if($row['attachment']): ?>
                            <div class="mt-2">
                                <span class="badge bg-primary mb-1"><i class="fa-solid fa-paperclip"></i> Image Attached</span>
                                <img src="uploads/<?php echo $row['attachment']; ?>" 
                                    class="scan-img" 
                                    onclick="showImage(this.src)" 
                                    style="cursor: pointer;">
                            </div>
                        <?php endif; ?>

                        <div class="mt-2 pt-2 border-top text-end">
                            <small class="text-muted fst-italic">Signed by Dr. <?php echo $row['doctor_name']; ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted p-3">No history records found.</div>
            <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-danger">Patient Not Found</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Image Zoom Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body text-center">
        <img id="modalImage" src="" class="img-fluid rounded shadow" style="max-height: 80vh;">
        <button type="button" class="btn btn-light mt-3 rounded-pill" data-bs-dismiss="modal">Close View</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Existing loading script
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('data').style.display = 'block';
        }, 1000);
    });

    // New Zoom Script
    function showImage(src) {
        document.getElementById('modalImage').src = src;
        var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
        myModal.show();
    }
</script>
</body>
</html>