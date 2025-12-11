<!DOCTYPE html>
<html lang="en">
<head>
    <title>MyDigital ID - Citizen View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background-color: #f0f2f5; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        .id-card { 
            width: 100%; 
            max-width: 360px; 
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            overflow: hidden; 
            border: 1px solid #e0e0e0;
        }

        /* THE NEW GREEN THEME */
        .id-header { 
            background: linear-gradient(135deg, #004d40 0%, #00695c 100%); 
            color: white; 
            padding: 25px; 
            text-align: center; 
            position: relative;
        }

        .coat-of-arms {
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            margin-bottom: 10px;
        }

        .qr-box { 
            padding: 30px; 
            text-align: center; 
            background: white;
        }
        
        .qr-border {
            border: 4px solid #004d40;
            padding: 10px;
            border-radius: 12px;
            display: inline-block;
        }

        .qr-img { 
            width: 180px; 
            height: 180px; 
            object-fit: contain; 
        }

        .info-section {
            padding: 0 20px 20px 20px;
            text-align: center;
        }

        .status-badge {
            background-color: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #c8e6c9;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="id-card">
        <!-- National Header -->
        <div class="id-header">
            <!-- Using FontAwesome as a placeholder for Coat of Arms if image fails -->
            <div class="coat-of-arms">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/26/Coat_of_arms_of_Malaysia.svg" width="60" alt="Jatana Negara">
            </div>
            <h5 class="mb-0 fw-bold" style="letter-spacing: 1px;">MALAYSIA DIGITAL ID</h5>
            <small class="opacity-75">National Identity Gateway</small>
        </div>

        <!-- QR Code Section -->
        <div class="qr-box">
            <div class="qr-border">
                <!-- IMPORTANT: Make sure you have 'qr_code.png' in your folder -->
                <!-- Use a generic placeholder if you haven't generated one yet -->
                <img src="qr_code.png" class="qr-img" alt="Digital ID QR" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=http://192.168.0.185/iSquare/scan.php?id=900101-14-1234'">
            </div>
            
            <p class="mt-3 text-danger small fw-bold blink">
                <i class="fa-solid fa-triangle-exclamation"></i> Emergency Medical Access Only
            </p>
        </div>

        <!-- Citizen Info -->
        <div class="info-section">
            <h3 class="fw-bold mb-0">ALI BIN AHMAD</h3>
            <p class="text-muted fs-5 mb-1">900101-14-1234</p>
            
            <div class="status-badge">
                <i class="fa-solid fa-circle-check"></i> ACTIVE CITIZEN
            </div>
        </div>
        
        <div class="bg-light p-3 text-center small text-muted border-top">
            Valid until: <strong>Dec 2030</strong>
        </div>
    </div>

    <!-- Simple blink animation for the warning text -->
    <style>
        .blink { animation: blinker 2s linear infinite; }
        @keyframes blinker { 50% { opacity: 0.5; } }
    </style>

</body>
</html>