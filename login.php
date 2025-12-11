<?php
session_start();
include 'db.php';

// Handle Login
if(isset($_POST['login'])) {
    $doctor_id = $_POST['doctor_id'];
    
    // In a real app, we check password. For Hackathon, we trust the selection.
    // We fetch the Doctor AND their Hospital info immediately.
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
        $_SESSION['hospital_type'] = $user['type']; // Gov or Private
        
        header("Location: admin.php"); // Redirect to Dashboard
        exit();
    }
}

// Fetch Doctors for the Dropdown
$doctors = $conn->query("SELECT doctors.id, doctors.name, hospitals.name AS hospital FROM doctors JOIN hospitals ON doctors.hospital_id = hospitals.id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>MyHealthID Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f2027; background: linear-gradient(to right, #2c5364, #203a43, #0f2027); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <h3 class="fw-bold text-dark mb-4">🩺 Provider Portal</h3>
        <p class="text-muted mb-4">Secure Access for Medical Staff</p>
        
        <form method="POST">
            <div class="form-floating mb-3 text-start">
                <select name="doctor_id" class="form-select" id="floatingSelect" required>
                    <option value="" disabled selected>Select Identity</option>
                    <?php while($row = $doctors->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>">
                            <?php echo $row['name']; ?> (<?php echo $row['hospital']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="floatingSelect">Select Doctor Account</label>
            </div>
            
            <div class="form-floating mb-3">
                <input type="password" class="form-control" placeholder="Password" value="123456">
                <label>Password (Default: 123)</label>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100 py-2 fw-bold">Login to System</button>
        </form>
    </div>
</body>
</html>