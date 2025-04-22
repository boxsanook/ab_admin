<?php
session_start();
require_once '../webhook/config/config.php';

// Get referral code from URL if exists
$referralCode = $_GET['ref'] ?? null;

// Initialize variables
$referrer = null;
$error = null;

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If referral code exists, verify it
    if ($referralCode) {
        $stmt = $pdo->prepare("SELECT u.*, CONCAT(?, '/login/profile.php?id=', u.id) as profile_url 
                              FROM users u 
                              WHERE u.affiliate_code = ? AND u.affiliate = '1' AND u.active = '1'");
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $domain;
        
        $stmt->execute([$baseUrl, $referralCode]);
        $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $referrer ? "Join " . htmlspecialchars($referrer['display_name']) . "'s Network" : "Register - AB Ai Management"; ?></title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        .register-box {
            margin-top: 5%;
        }
        .referrer-card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .profile-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto;
            display: block;
        }
        .social-buttons .btn {
            margin: 0 5px;
        }
    </style>
</head>
<body class="hold-transition register-page">
    <div class="register-box">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($referrer): ?>
        <!-- Referrer Info Card -->
        <div class="card referrer-card mb-4">
            <div class="card-body text-center">
                <?php if (!empty($referrer['picture_url'])): ?>
                    <img src="<?php echo htmlspecialchars($referrer['picture_url']); ?>" 
                         alt="Profile Picture" class="profile-image mb-3">
                <?php endif; ?>
                
                <h4><?php echo htmlspecialchars($referrer['display_name'] ?? $referrer['name']); ?></h4>
                <p class="text-muted">Invited you to join AB Ai Management</p>
                
                <?php if (!empty($referrer['profile_url'])): ?>
                <div class="social-buttons">
                    <a href="<?php echo htmlspecialchars($referrer['profile_url']); ?>" 
                       class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="fas fa-user mr-1"></i>View Profile
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body register-card-body">
                <p class="login-box-msg">
                    <?php echo $referrer ? 
                        "Join " . htmlspecialchars($referrer['display_name']) . "'s network" : 
                        "Register a new membership"; ?>
                </p>

                <div class="text-center mb-4">
                    <a href="https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=<?php echo LINE_CHANNEL_ID; ?>&redirect_uri=<?php echo urlencode(LINE_CALLBACK_URL); ?>&state=<?php echo urlencode($referralCode ?? ''); ?>&scope=profile%20openid%20email" 
                       class="btn btn-success btn-block">
                        <i class="fab fa-line mr-2"></i>Register with LINE
                    </a>
                </div>

                <div class="text-center mt-3">
                    <p class="mb-1">
                        Already have an account? 
                        <a href="login.php<?php echo $referralCode ? '?ref=' . urlencode($referralCode) : ''; ?>">
                            Sign In
                        </a>
                    </p>
                    <p class="mb-0">
                        <a href="index.php" class="text-center">Back to home</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html> 