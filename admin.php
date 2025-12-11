<?php 
include 'db.php'; 

// Handle Form Submission (Update Patient)
$msg = "";
if(isset($_POST['update_patient'])) {
    $id = $_POST['p_id'];
    $alg = $_POST['allergy'];
    $ec = $_POST['emergency'];
    
    $sql = "UPDATE patients SET allergy='$alg', emergency_contact='$ec' WHERE id='$id'";
    if($conn->query($sql)) {
        $msg = "<div class='alert alert-success'>Patient Record Updated Globally!</div>";
    }
}

// Fetch Data
$patients = $conn->query("SELECT * FROM patients");
$logs = $conn->query("SELECT * FROM access_logs ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
    <title>MyHealthID Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container bg-white p-4 shadow rounded">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-4">
            <h2>🏥 Hospital Administration Portal</h2>
            <span class="badge bg-primary fs-6">Connected to JPN Database</span>
        </div>

        <?php echo $msg; ?>

        <div class="row">
            <!-- Left Column: Patient Management -->
            <div class="col-md-7">
                <h4>Manage Patients</h4>
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Current Allergy</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $patients->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['name']; ?> <br> <small class="text-muted"><?php echo $row['ic_number']; ?></small></td>
                            <td class="text-danger fw-bold"><?php echo $row['allergy']; ?></td>
                            <td>
                                <!-- Simple Edit Form -->
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="p_id" value="<?php echo $row['id']; ?>">
                                    <input type="text" name="allergy" class="form-control form-control-sm" value="<?php echo $row['allergy']; ?>">
                                    <input type="hidden" name="emergency" value="<?php echo $row['emergency_contact']; ?>">
                                    <button type="submit" name="update_patient" class="btn btn-sm btn-warning">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Right Column: Real-time Security Logs -->
            <div class="col-md-5">
                <div class="card bg-dark text-white">
                    <div class="card-header border-bottom border-secondary">
                        🔒 Live Security Audit Log
                    </div>
                    <ul class="list-group list-group-flush text-small">
                        <?php while($log = $logs->fetch_assoc()): ?>
                        <li class="list-group-item bg-dark text-white border-secondary d-flex justify-content-between">
                            <span>
                                <span class="text-info">IC ending <?php echo substr($log['ic_number'], -4); ?></span> 
                                accessed by <?php echo $log['doctor_name']; ?>
                            </span>
                            <small class="text-muted"><?php echo date('H:i:s', strtotime($log['timestamp'])); ?></small>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <div class="alert alert-info mt-3 small">
                    <strong>Note:</strong> Every time a doctor scans a QR code, it appears here instantly.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto-refresh logs every 5 seconds to show "Live" effect -->
    <script>
        setTimeout(function(){
           // window.location.reload(1); // Optional: Enable this if you want auto-refresh
        }, 5000);
    </script>
</body>
</html>