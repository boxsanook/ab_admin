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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE affiliate_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $success_message = "Settings updated successfully!";
    } catch (Exception $e) {
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch current settings
$settings = $pdo->query("SELECT * FROM affiliate_settings")->fetchAll(PDO::FETCH_ASSOC);

// Group settings by category
$grouped_settings = [];
foreach ($settings as $setting) {
    $category = explode('_', $setting['setting_key'])[0];
    $grouped_settings[$category][] = $setting;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Settings - AB Ai Management</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                            <h1>Affiliate Settings</h1>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row">
                            <!-- Commission Settings -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Commission Settings</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($grouped_settings['default'] ?? [] as $setting): ?>
                                            <div class="form-group">
                                                <label><?php echo ucwords(str_replace(['default', '_'], ['', ' '], $setting['setting_key'])); ?></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" 
                                                           name="settings[<?php echo $setting['setting_key']; ?>]"
                                                           value="<?php echo $setting['setting_value']; ?>"
                                                           step="0.01" min="0" max="100">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted"><?php echo $setting['description']; ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Payout Settings -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Payout Settings</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($grouped_settings['minimum'] ?? [] as $setting): ?>
                                            <div class="form-group">
                                                <label><?php echo ucwords(str_replace(['minimum', '_'], ['', ' '], $setting['setting_key'])); ?></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">฿</span>
                                                    </div>
                                                    <input type="number" class="form-control" 
                                                           name="settings[<?php echo $setting['setting_key']; ?>]"
                                                           value="<?php echo $setting['setting_value']; ?>"
                                                           step="0.01" min="0">
                                                </div>
                                                <small class="form-text text-muted"><?php echo $setting['description']; ?></small>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php foreach ($grouped_settings['payout'] ?? [] as $setting): ?>
                                            <div class="form-group">
                                                <label><?php echo ucwords(str_replace(['payout', '_'], ['', ' '], $setting['setting_key'])); ?></label>
                                                <select class="form-control" name="settings[<?php echo $setting['setting_key']; ?>]">
                                                    <option value="weekly" <?php echo $setting['setting_value'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                    <option value="monthly" <?php echo $setting['setting_value'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                </select>
                                                <small class="form-text text-muted"><?php echo $setting['description']; ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Cookie Settings -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Cookie Settings</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($grouped_settings['cookie'] ?? [] as $setting): ?>
                                            <div class="form-group">
                                                <label><?php echo ucwords(str_replace(['cookie', '_'], ['', ' '], $setting['setting_key'])); ?></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" 
                                                           name="settings[<?php echo $setting['setting_key']; ?>]"
                                                           value="<?php echo $setting['setting_value']; ?>"
                                                           min="1">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">Days</span>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted"><?php echo $setting['description']; ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </div>
                    </form>
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
</body>
</html> 