<!DOCTYPE html>
<html>
<head>
    <title>MyDigital ID - Citizen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .id-card { width: 100%; max-width: 350px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; }
        .id-header { background: linear-gradient(135deg, #0d47a1, #1976d2); color: white; padding: 20px; text-align: center; }
        .qr-box { padding: 30px; text-align: center; }
        .qr-img { width: 200px; height: 200px; object-fit: contain; }
    </style>
</head>
<body>
    <div class="id-card">
        <!-- National Header -->
        <div class="id-header">
            <img src="https://upload.wikimedia.org/wikipedia/commons/2/26/Coat_of_arms_of_Malaysia.svg" width="50" class="mb-2">
            <h5 class="mb-0">MALAYSIA DIGITAL ID</h5>
            <small>National Identity Gateway</small>
        </div>

        <!-- Patient Info -->
        <div class="text-center mt-4">
            <h3 class="fw-bold">ALI BIN AHMAD</h3>
            <p class="text-muted">900101-14-1234</p>
        </div>

        <!-- The QR Code -->
        <div class="qr-box">
            <img src="qr_code.png" class="qr-img" alt="Digital ID QR">
            <p class="mt-3 text-danger small blink">
                Use for Medical Emergencies Only
            </p>
        </div>
        
        <div class="bg-light p-3 text-center small text-muted">
            Valid until: Dec 2030
        </div>
    </div>
</body>
</html>