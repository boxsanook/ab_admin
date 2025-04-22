<?php
require_once 'auth_check.php';
require_once '../webhook/config/config.php';

if (!isset($_GET['purchase_id'])) {
    header('Location: purchase_tokens.php');
    exit();
}

$purchase_id = $_GET['purchase_id'];

// Get purchase details
$stmt = $conn->prepare("
    SELECT tp.*, tpkg.name as package_name 
    FROM token_purchases tp
    JOIN token_packages tpkg ON tp.tokens = tpkg.tokens
    WHERE tp.id = ? AND tp.user_id = ?
");
$stmt->bind_param("is", $purchase_id, $user_id);
$stmt->execute();
$purchase = $stmt->get_result()->fetch_assoc();

if (!$purchase) {
    header('Location: purchase_tokens.php');
    exit();
}

// Get payment settings
$stmt = $conn->prepare("SELECT * FROM payment_settings WHERE status = 'active' LIMIT 1");
$stmt->execute();
$payment_settings = $stmt->get_result()->fetch_assoc();

// Function to generate PromptPay QR code
function generatePromptPayQR($amount, $promptpay_id) {
    // Implementation of PromptPay QR code generation
    // This should integrate with your preferred QR code generation library
    return "data:image/png;base64,..."; // Replace with actual QR code
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Payment Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Order Summary</h5>
                                <p><strong>Package:</strong> <?php echo htmlspecialchars($purchase['package_name']); ?></p>
                                <p><strong>Tokens:</strong> <?php echo number_format($purchase['tokens']); ?></p>
                                <p><strong>Amount:</strong> ฿<?php echo number_format($purchase['amount'], 2); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $purchase['payment_method'])); ?></p>
                            </div>
                        </div>

                        <?php if ($purchase['payment_method'] === 'promptpay'): ?>
                            <div class="text-center mb-4">
                                <h5>Scan QR Code to Pay</h5>
                                <div class="qr-container my-3">
                                    <img src="<?php echo generatePromptPayQR($purchase['amount'], $payment_settings['promptpay_id']); ?>" 
                                         alt="PromptPay QR Code" 
                                         class="img-fluid">
                                </div>
                                <p class="mb-0">PromptPay ID: <?php echo $payment_settings['promptpay_id']; ?></p>
                                <p>Amount: ฿<?php echo number_format($purchase['amount'], 2); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <h5>Bank Transfer Details</h5>
                                <div class="bank-details p-3 bg-light rounded">
                                    <p><strong>Bank Name:</strong> <?php echo $payment_settings['bank_name']; ?></p>
                                    <p><strong>Account Name:</strong> <?php echo $payment_settings['bank_account_name']; ?></p>
                                    <p><strong>Account Number:</strong> <?php echo $payment_settings['bank_account_number']; ?></p>
                                    <p><strong>Amount:</strong> ฿<?php echo number_format($purchase['amount'], 2); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <h5>Important Instructions:</h5>
                            <ol>
                                <li>Please make the payment for the exact amount shown above.</li>
                                <li>Keep your payment confirmation for reference.</li>
                                <li>After payment, please wait for our system to verify your payment (usually within 5-15 minutes).</li>
                                <li>Once verified, your tokens will be credited to your account automatically.</li>
                            </ol>
                        </div>

                        <div class="text-center mt-4">
                            <a href="purchase_history.php" class="btn btn-primary">View Purchase History</a>
                            <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 