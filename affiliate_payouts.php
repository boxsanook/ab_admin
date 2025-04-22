<?php
session_start();
require_once '../webhook/config/config.php';

// Check if the required session variables are set
if (!isset($_SESSION['line_user'], $_SESSION['authenticated'], $_SESSION['isAdmin'], $_SESSION['userActive'])) {
    // Redirect to login if session variables are missing
    header('Location: login.php');
    exit;
}


// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle payout status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        try {
            $stmt = $pdo->prepare("UPDATE affiliate_payouts SET status = ?, payout_date = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

// Fetch all payouts with affiliate details
$payouts = $pdo->query("
    SELECT ap.*, 
           a.code as affiliate_code,
           u.display_name as affiliate_name,
           u.email as affiliate_email
    FROM affiliate_payouts ap
    LEFT JOIN affiliates a ON ap.affiliate_id = a.id
    LEFT JOIN users u ON a.user_id = u.user_id
    ORDER BY ap.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Payouts - AB Ai Management</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar & Sidebar -->
        <?php include 'includes/navbar.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Affiliate Payouts</h1>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <table id="payoutsTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Affiliate</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Payment Details</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payouts as $payout): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($payout['created_at'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($payout['affiliate_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($payout['affiliate_email']); ?></small>
                                            <br>
                                            <small class="text-muted">Code: <?php echo htmlspecialchars($payout['affiliate_code']); ?></small>
                                        </td>
                                        <td>฿<?php echo number_format($payout['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payout['payment_method']); ?></td>
                                        <td>
                                            <select class="form-control form-control-sm payout-status" 
                                                    data-id="<?php echo $payout['id']; ?>">
                                                <option value="pending" <?php echo $payout['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $payout['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="completed" <?php echo $payout['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="failed" <?php echo $payout['status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="popover" 
                                                    title="Payment Details" data-content="<?php echo htmlspecialchars($payout['payment_details']); ?>">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary process-payout" 
                                                    data-id="<?php echo $payout['id']; ?>"
                                                    <?php echo $payout['status'] != 'pending' ? 'disabled' : ''; ?>>
                                                <i class="fas fa-money-bill-wave"></i> Process
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#payoutsTable').DataTable({
                "order": [[0, "desc"]],
                "responsive": true,
                "autoWidth": false
            });

            // Initialize popovers
            $('[data-toggle="popover"]').popover({
                trigger: 'click',
                placement: 'left',
                html: true
            });

            // Handle status change
            $('.payout-status').on('change', function() {
                const id = $(this).data('id');
                const status = $(this).val();
                $.post('', {
                    action: 'update_status',
                    id: id,
                    status: status
                }, function(response) {
                    if (!response.success) {
                        alert('Error updating status: ' + response.error);
                    }
                    // Disable/enable process button based on status
                    const processBtn = $(this).closest('tr').find('.process-payout');
                    processBtn.prop('disabled', status !== 'pending');
                });
            });

            // Handle process button click
            $('.process-payout').on('click', function() {
                const id = $(this).data('id');
                if (confirm('Are you sure you want to process this payout?')) {
                    const statusSelect = $(this).closest('tr').find('.payout-status');
                    statusSelect.val('processing').trigger('change');
                }
            });
        });
    </script>
</body>
</html> 