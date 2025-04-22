<?php
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_status':
            $id = $_POST['id'];
            $status = $_POST['status'];
            try {
                $stmt = $pdo->prepare("UPDATE affiliates SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;

        case 'update_commission':
            $id = $_POST['id'];
            $rate = $_POST['rate'];
            try {
                $stmt = $pdo->prepare("UPDATE affiliates SET commission_rate = ? WHERE id = ?");
                $stmt->execute([$rate, $id]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;

        case 'update_settings':
            try {
                foreach ($_POST['settings'] as $key => $value) {
                    $stmt = $pdo->prepare("UPDATE affiliate_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
    }
}

// Fetch statistics
$stats = [
    'total_affiliates' => $pdo->query("SELECT COUNT(*) FROM affiliates WHERE status = 'active'")->fetchColumn(),
    'total_earnings' => $pdo->query("SELECT SUM(amount) FROM affiliate_transactions WHERE status = 'completed'")->fetchColumn() ?: 0,
    'pending_payouts' => $pdo->query("SELECT COUNT(*) FROM affiliate_payouts WHERE status = 'pending'")->fetchColumn(),
    'total_referrals' => $pdo->query("SELECT SUM(total_referrals) FROM affiliates")->fetchColumn() ?: 0
];

// Fetch settings
$settings_query = $pdo->query("SELECT setting_key, setting_value FROM affiliate_settings");
$settings = [];
while ($row = $settings_query->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch affiliates with their stats
$affiliates = $pdo->query("
    SELECT a.*, 
           u.display_name,
           u.email,
           u.picture_url,
           COUNT(DISTINCT aft.id) as transaction_count,
           SUM(CASE WHEN aft.status = 'completed' THEN aft.amount ELSE 0 END) as earned_amount
    FROM affiliates a
    LEFT JOIN users u ON a.user_id = u.user_id
    LEFT JOIN affiliate_transactions aft ON a.id = aft.affiliate_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Management - AB Ai Management</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <style>
        .stats-box {
            transition: transform 0.2s;
        }
        .stats-box:hover {
            transform: translateY(-5px);
        }
    </style>
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
                            <h1>Affiliate Management</h1>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Statistics -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info stats-box">
                                <div class="inner">
                                    <h3><?php echo $stats['total_affiliates']; ?></h3>
                                    <p>Active Affiliates</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success stats-box">
                                <div class="inner">
                                    <h3>฿<?php echo number_format($stats['total_earnings'], 2); ?></h3>
                                    <p>Total Earnings</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning stats-box">
                                <div class="inner">
                                    <h3><?php echo $stats['pending_payouts']; ?></h3>
                                    <p>Pending Payouts</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger stats-box">
                                <div class="inner">
                                    <h3><?php echo $stats['total_referrals']; ?></h3>
                                    <p>Total Referrals</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Affiliate Settings</h3>
                        </div>
                        <div class="card-body">
                            <form id="settingsForm">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Default Commission Rate (%)</label>
                                            <input type="number" class="form-control" name="settings[default_commission_rate]" 
                                                value="<?php echo $settings['default_commission_rate']; ?>" step="0.01" min="0" max="100">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Minimum Payout (฿)</label>
                                            <input type="number" class="form-control" name="settings[minimum_payout]" 
                                                value="<?php echo $settings['minimum_payout']; ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Payout Schedule</label>
                                            <select class="form-control" name="settings[payout_schedule]">
                                                <option value="weekly" <?php echo $settings['payout_schedule'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo $settings['payout_schedule'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Cookie Duration (Days)</label>
                                            <input type="number" class="form-control" name="settings[cookie_duration]" 
                                                value="<?php echo $settings['cookie_duration']; ?>" min="1">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </form>
                        </div>
                    </div>

                    <!-- Affiliates Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Affiliate Members</h3>
                        </div>
                        <div class="card-body">
                            <table id="affiliatesTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Affiliate</th>
                                        <th>Code</th>
                                        <th>Commission Rate</th>
                                        <th>Earnings</th>
                                        <th>Referrals</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($affiliates as $affiliate): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($affiliate['picture_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($affiliate['picture_url']); ?>" 
                                                         class="img-circle mr-2" alt="" style="width: 40px; height: 40px;">
                                                <?php endif; ?>
                                                <div>
                                                    <div><?php echo htmlspecialchars($affiliate['display_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($affiliate['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($affiliate['code']); ?></code></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm commission-rate" 
                                                   data-id="<?php echo $affiliate['id']; ?>"
                                                   value="<?php echo $affiliate['commission_rate']; ?>" 
                                                   step="0.01" min="0" max="100" style="width: 80px;">
                                        </td>
                                        <td>฿<?php echo number_format($affiliate['earned_amount'], 2); ?></td>
                                        <td><?php echo $affiliate['total_referrals']; ?></td>
                                        <td>
                                            <select class="form-control form-control-sm affiliate-status" 
                                                    data-id="<?php echo $affiliate['id']; ?>">
                                                <option value="active" <?php echo $affiliate['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $affiliate['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                <option value="suspended" <?php echo $affiliate['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info view-transactions" 
                                                    data-id="<?php echo $affiliate['id']; ?>">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success view-payouts" 
                                                    data-id="<?php echo $affiliate['id']; ?>">
                                                <i class="fas fa-money-bill"></i>
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
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#affiliatesTable').DataTable({
                "responsive": true,
                "autoWidth": false
            });

            // Handle settings form submission
            $('#settingsForm').on('submit', function(e) {
                e.preventDefault();
                $.post('', $(this).serialize() + '&action=update_settings', function(response) {
                    if (response.success) {
                        alert('Settings updated successfully!');
                    } else {
                        alert('Error updating settings: ' + response.error);
                    }
                });
            });

            // Handle commission rate change
            $('.commission-rate').on('change', function() {
                const id = $(this).data('id');
                const rate = $(this).val();
                $.post('', {
                    action: 'update_commission',
                    id: id,
                    rate: rate
                }, function(response) {
                    if (!response.success) {
                        alert('Error updating commission rate: ' + response.error);
                    }
                });
            });

            // Handle status change
            $('.affiliate-status').on('change', function() {
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
                });
            });

            // Handle transaction view
            $('.view-transactions').on('click', function() {
                const id = $(this).data('id');
                // Implement transaction view modal
            });

            // Handle payout view
            $('.view-payouts').on('click', function() {
                const id = $(this).data('id');
                // Implement payout view modal
            });
        });
    </script>
</body>
</html> 