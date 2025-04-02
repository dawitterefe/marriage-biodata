<?php
session_start();
include 'config/db.php';
require_once 'vendor/autoload.php'; // For TCPDF

// Check if payment_id is provided
if (!isset($_GET['payment_id'])) {
    die("Payment ID not provided.");
}
$payment_id = $_GET['payment_id'];

// Verify payment status
$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND payment_status = 'completed'");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Payment not found or not completed.");
}

$customized_id = $payment['customized_biodata_id'];

// Fetch customized biodata
$stmt = $pdo->prepare("SELECT * FROM customized_biodatas WHERE id = ?");
$stmt->execute([$customized_id]);
$customized = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customized) {
    die("Customized biodata not found.");
}

// Handle download logic
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    $image_path = 'assets/images/' . $customized['preview_image'];

    if (!file_exists($image_path)) {
        die("Image file not found.");
    }

    if ($format === 'png') {
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="biodata.png"');
        readfile($image_path);
        exit;
    } elseif ($format === 'pdf') {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $pdf->Image($image_path, 0, 0, 210, 297, '', '', '', false, 300);
        $pdf->Output('biodata.pdf', 'D');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Download Biodata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            --gradient-accent: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-dark: #333;
            --text-light: #fff;
        }

        .navbar-custom {
            background: var(--gradient-primary);
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: var(--text-light);
            font-weight: 500;
        }

        .navbar-custom .nav-link:hover {
            color: #d0e8ff;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }

        .btn-secondary {
            background: var(--gradient-accent);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
        }

        body {
            background-color: #f8f9fa;
            color: var(--text-dark);
        }

        .container {
            max-width: 800px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Biodata Maker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5 pt-5">
        <h2 class="text-center mb-4">Download Your Biodata</h2>
        <p class="text-center">Thank you for your payment! Your biodata is ready to download.</p>

        <!-- Download Button -->
        <div class="text-center mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#downloadModal">Download Now</button>
        </div>

        <!-- Content inspired by marathibiodatamaker.com/downloadnow -->
        <div class="card shadow-sm p-4">
            <h3>Why Download This Biodata?</h3>
            <ul>
                <li>वॉटरमार्कशिवाय बायोडाटा (No watermark on the biodata)</li>
                <li>पेमेंट नंतर सुद्धा एडिट व डाउनलोड करू शकता (Edit and download even after payment)</li>
                <li>High-Quality Image आणि PDF मिळते (High-quality image and PDF)</li>
                <li>Unlimited Edit & Download</li>
                <li>लोकप्रिय बायोडाटा डिझाईन (Popular biodata designs)</li>
                <li>एकदम भारी तयार झालेला बायोडाटा आत्ताच डाउनलोड करा (Download your awesome biodata now)</li>
                <li>पेमेंट झाल्यानंतर लगेच डाउनलोड करू शकता (Download immediately after payment)</li>
            </ul>

            <h3>Payment Details</h3>
            <p>किंमत ₹ 100 फक्त 50/- रुपये.<br>आपणांस लगेच प्रिंटसाठी PDF आणि High Quality इमेज मिळते.</p>
            <p>
                <i class="fab fa-google-pay me-2"></i>
                <i class="fab fa-phone-alt me-2"></i>
                <i class="fab fa-paypal me-2"></i>
                <i class="fab fa-amazon-pay me-2"></i>
                <i class="fas fa-mobile-alt me-2"></i><br>
                Safe and Secure Payment with 100% Payment Protection.<br>
                Secure connection Https
            </p>

            <!-- Placeholder UPI Form (for show, non-functional) -->
            <h3>Confirm Payment (For Display Only)</h3>
            <form>
                <div class="mb-3">
                    <label for="upi" class="form-label">Enter UPI ID:</label>
                    <input type="text" class="form-control" id="upi" placeholder="example@upi" disabled>
                </div>
                <button type="button" class="btn btn-success" disabled>Proceed</button>
            </form>
            <p class="mt-2 text-muted">Note: Payment already completed. Click "Download Now" above.</p>
        </div>
    </div>

    <!-- Download Modal -->
    <div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="downloadModalLabel">Choose Download Format</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Select the format you want to download:</p>
                    <a href="download.php?payment_id=<?php echo $payment_id; ?>&format=png" class="btn btn-primary me-2">Download PNG</a>
                    <a href="download.php?payment_id=<?php echo $payment_id; ?>&format=pdf" class="btn btn-secondary">Download PDF</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>