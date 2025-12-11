<?php 
include 'db.php'; 
$ic = $_GET['id']; // 从网址获取 IC
$sql = "SELECT * FROM patients WHERE ic_number = '$ic'";
$result = $conn->query($sql);
$patient = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dr. Scanner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .blink { animation: blinker 1s linear infinite; color: red; font-weight: bold; }
        @keyframes blinker { 50% { opacity: 0; } }
    </style>
</head>
<body class="bg-dark text-white text-center p-4">

    <!-- 1. 模拟验证动画 -->
    <div id="loading">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
        <h3 class="mt-3">Connecting to JPN Digital ID...</h3>
        <p class="text-muted">Verifying Biometrics...</p>
    </div>

    <!-- 2. 真实数据显示 -->
    <div id="data" style="display:none;">
        <div class="alert alert-success">✔ Identity Verified</div>
        
        <img src="https://ui-avatars.com/api/?name=Ali+Ahmad&background=random" class="rounded-circle mb-3">
        <h3><?php echo $patient['name']; ?></h3>
        <p>IC: <?php echo $patient['ic_number']; ?></p>
        
        <div class="card text-dark text-start mt-3">
            <div class="card-header bg-warning">EMERGENCY MEDICAL RECORD</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">Blood Type: <strong><?php echo $patient['blood_type']; ?></strong></li>
                <li class="list-group-item">Emergency Contact: <br><strong><?php echo $patient['emergency_contact']; ?></strong></li>
                <li class="list-group-item">
                    Allergies: <br>
                    <span class="blink" style="font-size: 1.5rem;">
                        <?php echo $patient['allergy']; ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // 假装加载 2.5 秒，制造科技感
        setTimeout(function() {
            $('#loading').hide();
            $('#data').fadeIn();
        }, 2500);
    </script>
</body>
</html>