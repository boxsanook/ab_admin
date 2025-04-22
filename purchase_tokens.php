<?php
require_once 'auth_check.php';
require_once '../webhook/config/config.php';

// Get token packages from settings
$stmt = $conn->prepare("SELECT * FROM token_packages WHERE status = 'active' ORDER BY tokens ASC");
$stmt->execute();
$token_packages = $stmt->get_result();

// Get user's referral code if they were referred
$referral_code = isset($_SESSION['referral_code']) ? $_SESSION['referral_code'] : null;

// Process token purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_tokens'])) {
    $package_id = $_POST['package_id'];
    $payment_method = $_POST['payment_method'];
    
    // Get package details
    $stmt = $conn->prepare("SELECT * FROM token_packages WHERE id = ?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $package = $stmt->get_result()->fetch_assoc();
    
    if ($package) {
        // Create token purchase record
        $stmt = $conn->prepare("INSERT INTO token_purchases (user_id, amount, tokens, payment_method, referral_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdiss", $user_id, $package['price'], $package['tokens'], $payment_method, $referral_code);
        
        if ($stmt->execute()) {
            $purchase_id = $conn->insert_id;
            
            // Redirect to payment processing
            header("Location: process_payment.php?purchase_id=" . $purchase_id);
            exit();
        } else {
            $error_message = "Error creating purchase record.";
        }
    } else {
        $error_message = "Invalid package selected.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Tokens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .package-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .package-card:hover {
            transform: translateY(-5px);
        }
        .package-card.selected {
            border: 2px solid #0d6efd;
        }
        .payment-method-card {
            cursor: pointer;
        }
        .payment-method-card.selected {
            border: 2px solid #0d6efd;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Purchase Tokens</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form id="purchaseForm" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="package_id" id="selected_package">
            <input type="hidden" name="payment_method" id="selected_payment_method">
            
            <div class="row mb-4">
                <h3 class="mb-3">Select Token Package</h3>
                <?php while ($package = $token_packages->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card package-card h-100" data-package-id="<?php echo $package['id']; ?>">
                            <div class="card-body text-center">
                                <h4 class="card-title"><?php echo number_format($package['tokens']); ?> Tokens</h4>
                                <h3 class="card-text text-primary">฿<?php echo number_format($package['price'], 2); ?></h3>
                                <?php if ($package['bonus_tokens'] > 0): ?>
                                    <p class="card-text text-success">
                                        +<?php echo number_format($package['bonus_tokens']); ?> Bonus Tokens
                                    </p>
                                <?php endif; ?>
                                <p class="card-text">
                                    <small class="text-muted">
                                        ฿<?php echo number_format($package['price']/$package['tokens'], 2); ?> per token
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="row mb-4">
                <h3 class="mb-3">Select Payment Method</h3>
                <div class="col-md-4 mb-3">
                    <div class="card payment-method-card h-100" data-payment-method="promptpay">
                        <div class="card-body text-center">
                            <img src="assets/images/promptpay-logo.png" alt="PromptPay" class="mb-3" style="height: 40px;">
                            <h5 class="card-title">PromptPay</h5>
                            <p class="card-text"><small class="text-muted">Pay instantly with PromptPay QR</small></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card payment-method-card h-100" data-payment-method="bank_transfer">
                        <div class="card-body text-center">
                            <img src="assets/images/bank-transfer-logo.png" alt="Bank Transfer" class="mb-3" style="height: 40px;">
                            <h5 class="card-title">Bank Transfer</h5>
                            <p class="card-text"><small class="text-muted">Transfer to our bank account</small></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" name="purchase_tokens" class="btn btn-primary btn-lg" disabled>
                    Proceed to Payment
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('purchaseForm');
        const submitButton = form.querySelector('button[type="submit"]');
        let selectedPackage = null;
        let selectedPaymentMethod = null;

        // Package selection
        document.querySelectorAll('.package-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedPackage = this.dataset.packageId;
                document.getElementById('selected_package').value = selectedPackage;
                updateSubmitButton();
            });
        });

        // Payment method selection
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedPaymentMethod = this.dataset.paymentMethod;
                document.getElementById('selected_payment_method').value = selectedPaymentMethod;
                updateSubmitButton();
            });
        });

        function updateSubmitButton() {
            submitButton.disabled = !(selectedPackage && selectedPaymentMethod);
        }

        // Form submission
        form.addEventListener('submit', function(e) {
            if (!selectedPackage || !selectedPaymentMethod) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: 'Please select both a token package and payment method.',
                    icon: 'error'
                });
            }
        });
    });
    </script>
</body>
</html> 