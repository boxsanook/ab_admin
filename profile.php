<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

// Retrieve user data from the session
$user = $_SESSION['line_user'] ?? null;

if (!$user) {
    echo "User data not found.";
    exit;
}

// Include database configuration
require_once '../webhook/config/config.php';

// Assign database credentials from constants
$host = DB_HOST;
$database = DB_NAME;
$user_db = DB_USER;
$password = DB_PASS;

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user_db, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}

// Fetch user's affiliate status and code
$affiliateData = null;
if (!empty($user['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT affiliate, affiliate_code
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['user_id']]);
    $affiliateData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get the current domain
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$affiliateLink = '';

if ($affiliateData && $affiliateData['affiliate'] == '1' && !empty($affiliateData['affiliate_code'])) {
    $affiliateLink = $protocol . $domain . '/register.php?ref=' . $affiliateData['affiliate_code'];
}

// Handle affiliate update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_affiliate'])) {
    $newName = $_POST['affiliate_name'] ?? '';
    $newDescription = $_POST['affiliate_description'] ?? '';
    if (!empty($newName) && !empty($user['user_id'])) {
        $updateStmt = $pdo->prepare("
            UPDATE affiliates 
            SET name = ?, description = ? 
            WHERE user_id = ?
        ");
        $updateStmt->execute([$newName, $newDescription, $user['user_id']]);
        // Refresh affiliate data
        $stmt->execute([$user['user_id']]);
        $affiliateData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Handle recommender update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recommender'])) {
    $recommenderCode = $_POST['recommender_code'] ?? '';
    if (!empty($recommenderCode) && !empty($user['user_id'])) {
        // Check if the recommender code exists
        $recommenderStmt = $pdo->prepare("
            SELECT user_id FROM affiliates WHERE code = ?
        ");
        $recommenderStmt->execute([$recommenderCode]);
        $recommender = $recommenderStmt->fetch(PDO::FETCH_ASSOC);

        if ($recommender) {
            // Update the user's recommender
            $updateRecommenderStmt = $pdo->prepare("
                UPDATE users SET recommender_id = ? WHERE user_id = ?
            ");
            $updateRecommenderStmt->execute([$recommender['user_id'], $user['user_id']]);
            $successMessage = "Recommender updated successfully!";
        } else {
            $errorMessage = "Invalid recommender code.";
        }
    }
}

$Admin_menu ='';

if ($_SESSION['isAdmin'] == 1) {
    $Admin_menu = ' <li class="nav-item">
    <a href="admin.php" class="nav-link ">
        <i class="nav-icon fas fa-users"></i>
        <p>Manage Users</p>
    </a>
</li>';
}  
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="profile.php" class="nav-link">Home</a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="profile.php" class="brand-link">
                <span class="brand-text font-weight-light">AB Ai Management</span>
            </a>
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <?php  echo $Admin_menu; ?>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link active">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Profile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="settings.php" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>User Profile</h1>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card card-primary card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <?php if (!empty($user['pictureUrl'])): ?>
                                            <p><img src="<?php echo htmlspecialchars($user['pictureUrl']); ?>"
                                                    alt="Profile Picture" class="profile-user-img img-fluid img-circle"
                                                    width="150"></p>
                                        <?php endif; ?>

                                    </div>

                                    <h3 class="profile-username text-center"> Welcome,
                                        <?php echo htmlspecialchars($user['displayName'] ?? 'User'); ?>!</h3>

                                    <?php if ($affiliateData && $affiliateData['affiliate'] == '1'): ?>
                                    <!-- Affiliate Dashboard -->
                                    <div class="row mb-4">
                                        <div class="col-12 col-sm-6 col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Total Referrals</span>
                                                    <span class="info-box-number">
                                                        <?php 
                                                        $referralCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referral_by = ?");
                                                        $referralCountStmt->execute([$affiliateData['affiliate_code']]);
                                                        echo $referralCountStmt->fetchColumn();
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Total Earnings</span>
                                                    <span class="info-box-number">
                                                        <?php 
                                                        $earningsStmt = $pdo->prepare("
                                                            SELECT COALESCE(SUM(amount), 0) 
                                                            FROM affiliate_transactions 
                                                            WHERE affiliate_id = ? AND status = 'completed'
                                                        ");
                                                        $earningsStmt->execute([$affiliateData['id']]);
                                                        echo '$' . number_format($earningsStmt->fetchColumn(), 2);
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Pending Earnings</span>
                                                    <span class="info-box-number">
                                                        <?php 
                                                        $pendingStmt = $pdo->prepare("
                                                            SELECT COALESCE(SUM(amount), 0) 
                                                            FROM affiliate_transactions 
                                                            WHERE affiliate_id = ? AND status = 'pending'
                                                        ");
                                                        $pendingStmt->execute([$affiliateData['id']]);
                                                        echo '$' . number_format($pendingStmt->fetchColumn(), 2);
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-3">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-danger"><i class="fas fa-chart-line"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Conversion Rate</span>
                                                    <span class="info-box-number">
                                                        <?php 
                                                        $clicksStmt = $pdo->prepare("
                                                            SELECT visits, conversions 
                                                            FROM affiliates 
                                                            WHERE id = ?
                                                        ");
                                                        $clicksStmt->execute([$affiliateData['id']]);
                                                        $stats = $clicksStmt->fetch();
                                                        $conversionRate = $stats['visits'] > 0 
                                                            ? round(($stats['conversions'] / $stats['visits']) * 100, 2)
                                                            : 0;
                                                        echo $conversionRate . '%';
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Affiliate Link Section with Enhanced UI -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-link mr-2"></i>Your Affiliate Link
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" id="affiliateLink" 
                                                    value="<?php echo $affiliateLink; ?>" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary" type="button" 
                                                        onclick="copyAffiliateLink()" data-toggle="tooltip" 
                                                        title="Copy to clipboard">
                                                        <i class="fas fa-copy"></i> Copy
                                                    </button>
                                                </div>
                                            </div>
                                            <div id="copyMessage" class="alert alert-success" style="display: none;">
                                                Link copied to clipboard!
                                            </div>
                                            
                                            <div class="mt-3">
                                                <h5>Share your link:</h5>
                                                <div class="btn-group">
                                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($affiliateLink); ?>" 
                                                        target="_blank" class="btn btn-primary" data-toggle="tooltip" 
                                                        title="Share on Facebook">
                                                        <i class="fab fa-facebook-f"></i>
                                                    </a>
                                                    <a href="https://line.me/R/msg/text/?<?php echo urlencode('Join AB Ai Management! ' . $affiliateLink); ?>" 
                                                        target="_blank" class="btn btn-success" data-toggle="tooltip" title="Share on LINE">
                                                        <i class="fab fa-line"></i>
                                                    </a>
                                                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Join AB Ai Management! ' . $affiliateLink); ?>" 
                                                        target="_blank" class="btn btn-info" data-toggle="tooltip" title="Share on Twitter">
                                                        <i class="fab fa-twitter"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-secondary" onclick="shareToTelegram()" 
                                                        data-toggle="tooltip" title="Share on Telegram">
                                                        <i class="fab fa-telegram-plane"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <ul class="list-group list-group-unbordered mb-3">
                                        <li class="list-group-item">
                                            <b>Status</b> 
                                            <span class="float-right">
                                                <?php if ($affiliateData && $affiliateData['affiliate'] == '1'): ?>
                                                    <span class="badge badge-success">Affiliate Member</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Regular Member</span>
                                                <?php endif; ?>
                                            </span>
                                        </li>
                                        <?php if ($affiliateData && $affiliateData['affiliate'] == '1'): ?>
                                        <li class="list-group-item">
                                            <b>Affiliate Code</b>
                                            <span class="float-right">
                                                <code><?php echo htmlspecialchars($affiliateData['affiliate_code']); ?></code>
                                            </span>
                                        </li>
                                        <?php endif; ?>
                                        <li class="list-group-item">
                                            <b>Domain</b>
                                            <span class="float-right">
                                                <a href="<?php echo $protocol . $domain; ?>" target="_blank"><?php echo htmlspecialchars($domain); ?></a>
                                            </span>
                                        </li>
                                    </ul>
                                    <?php if (!empty($affiliateData['code'])): ?>
                                        <a href="https://example.com/ref/<?php echo htmlspecialchars($affiliateData['code']); ?>"
                                            target="_blank" class="btn btn-success btn-block">
                                            <b>Share Link</b>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Display success or error message -->
                                    <?php if (!empty($successMessage)): ?>
                                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?>
                                        </div>
                                    <?php elseif (!empty($errorMessage)): ?>
                                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                                    <?php endif; ?>
                                    <a href="logout.php" class="btn btn-danger">Logout</a>
                                    <a href="settings.php" class="btn btn-info">Settings</a>
                                </div>
                                <!-- /.card-body -->
                            </div>
                        </div>
                        <div class="col-md-9">
                            <?php if ($affiliateData && $affiliateData['affiliate'] == '1'): ?>
                            <!-- Referral List -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-users mr-2"></i>Your Referrals
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Fetch referral users
                                    $referralStmt = $pdo->prepare("
                                        SELECT u.*, DATE_FORMAT(u.created_at, '%d/%m/%Y') as join_date 
                                        FROM users u 
                                        WHERE u.referral_by = ?
                                        ORDER BY u.created_at DESC
                                    ");
                                    $referralStmt->execute([$affiliateData['affiliate_code']]);
                                    $referrals = $referralStmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>

                                    <?php if (!empty($referrals)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Join Date</th>
                                                        <th>Status</th>
                                                        <th>Commission</th>
                                                        <th>Last Activity</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($referrals as $referral): 
                                                        // Fetch commission for this referral
                                                        $commissionStmt = $pdo->prepare("
                                                            SELECT COALESCE(SUM(amount), 0) as total_commission
                                                            FROM affiliate_transactions
                                                            WHERE affiliate_id = ? AND referral_id = ?
                                                        ");
                                                        $commissionStmt->execute([$affiliateData['id'], $referral['id']]);
                                                        $commission = $commissionStmt->fetch()['total_commission'];
                                                        
                                                        // Get last activity
                                                        $activityStmt = $pdo->prepare("
                                                            SELECT DATE_FORMAT(last_login, '%d/%m/%Y %H:%i') as last_active
                                                            FROM users
                                                            WHERE id = ?
                                                        ");
                                                        $activityStmt->execute([$referral['id']]);
                                                        $lastActivity = $activityStmt->fetch()['last_active'];
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($referral['picture_url'])): ?>
                                                                        <img src="<?php echo htmlspecialchars($referral['picture_url']); ?>" 
                                                                            class="img-circle mr-2" alt="" style="width: 30px; height: 30px;">
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <div><?php echo htmlspecialchars($referral['display_name'] ?? $referral['name']); ?></div>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($referral['email']); ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($referral['email']); ?></td>
                                                            <td><?php echo $referral['join_date']; ?></td>
                                                            <td>
                                                                <?php if ($referral['active']): ?>
                                                                    <span class="badge badge-success">Active</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">Inactive</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>$<?php echo number_format($commission, 2); ?></td>
                                                            <td><?php echo $lastActivity ?? 'Never'; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">You haven't referred any users yet.</p>
                                            <p class="text-muted">Share your affiliate link to start earning rewards!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Update Recommender</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="recommender_code">Recommender Affiliate Code</label>
                                            <input type="text" name="recommender_code" id="recommender_code"
                                                class="form-control" placeholder="Enter affiliate code" required>
                                        </div>
                                        <button type="submit" name="update_recommender" class="btn btn-primary">
                                            Update Recommender
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>
        <!-- /.content-wrapper -->

        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                AB Ai Management System
            </div>
            <strong>&copy; <?php echo date('Y'); ?> AB Ai Management. All rights reserved.</strong>
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- AdminLTE JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    function copyAffiliateLink() {
        var copyText = document.getElementById("affiliateLink");
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            // Execute the copy command
            document.execCommand("copy");
            
            // Show success message
            var messageDiv = document.getElementById('copyMessage');
            messageDiv.style.display = 'block';
            
            // Hide message after 2 seconds
            setTimeout(function() {
                messageDiv.style.display = 'none';
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text: ', err);
            alert('Failed to copy text. Please try again.');
        }
    }

    function shareToTelegram() {
        var text = encodeURIComponent('Join AB Ai Management! ' + document.getElementById('affiliateLink').value);
        window.open('https://t.me/share/url?url=' + text, '_blank');
    }

    // Initialize tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
    </script>
</body>

</html>