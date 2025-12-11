<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>MyHealthID Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>🏥 Hospital Admin Portal</h2>
    
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $ic = $_POST['ic'];
        $allergy = $_POST['allergy'];
        // 更新数据库
        $sql = "UPDATE patients SET allergy='$allergy' WHERE ic_number='$ic'";
        if ($conn->query($sql) === TRUE) {
            echo "<div class='alert alert-success'>Record Updated Successfully!</div>";
        }
    }
    ?>

    <form method="post" class="card p-4 shadow mt-3">
        <div class="mb-3">
            <label>Patient IC Number</label>
            <input type="text" name="ic" value="900101-14-1234" class="form-control" readonly>
        </div>
        <div class="mb-3">
            <label>Update Allergy</label>
            <select name="allergy" class="form-control">
                <option value="None">None</option>
                <option value="PEANUTS">PEANUTS</option>
                <option value="PENICILLIN (Severe)">PENICILLIN (Severe)</option>
                <option value="SEAFOOD">SEAFOOD</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save to Digital ID</button>
    </form>
</body>
</html>