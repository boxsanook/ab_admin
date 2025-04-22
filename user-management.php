<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Check if the required session variables are set
if (!isset($_SESSION['line_user'], $_SESSION['authenticated'], $_SESSION['isAdmin'], $_SESSION['userActive'])) {
    // Redirect to login if session variables are missing
    header('Location: login.php');
    exit;
}

// Ensure the user is authenticated and is an admin
if (!$_SESSION['authenticated'] || $_SESSION['isAdmin'] != 1) {
    // Redirect to an error page or login if the user is not an admin
    header('Location: error.php');
    exit;
}

require_once '../webhook/config/config.php';
$host = DB_HOST;
$user = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Check if the user is authenticated 
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate a unique affiliate code
function generateUniqueAffiliateCode($conn)
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxAttempts = 10;
    $attempt = 0;

    do {
        // Generate random code
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Check if code exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE affiliate_code = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $code);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $exists = $row['count'] > 0;
        $stmt->close();

        $attempt++;
    } while ($exists && $attempt < $maxAttempts);

    if ($attempt >= $maxAttempts) {
        throw new Exception("Could not generate unique affiliate code after {$maxAttempts} attempts");
    }

    return $code;
}

// Create affiliate record in the affiliates table
function createAffiliateRecord($conn, $user_id, $affiliate_code)
{
    // Get user name and email
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Get default commission rate
    $result = $conn->query("SELECT setting_value FROM affiliate_settings WHERE setting_key = 'default_commission_rate'");
    $default_rate = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['setting_value'] : 10.00;

    // Check if affiliate record already exists
    $stmt = $conn->prepare("SELECT id FROM affiliates WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE affiliates SET code = ?, status = 'active', name = ? WHERE user_id = ?");
        $stmt->bind_param("sss", $affiliate_code, $user['name'], $user_id);
        $stmt->execute();
    } else {
        // Create new record
        $stmt = $conn->prepare("INSERT INTO affiliates (user_id, name, code, commission_rate, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssd", $user_id, $user['name'], $affiliate_code, $default_rate);
        $stmt->execute();
    }
    $stmt->close();

    return true;
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // Prevent output of the entire HTML page
    ob_start();

    if ($action === 'insert') {
        $user_id = $_POST['user_id'];
        $display_name = $_POST['display_name'];
        $email = $_POST['email'];
        $picture_url = $_POST['picture_url'];
        $status_message = $_POST['status_message'];
        $access_token = $_POST['access_token'];
        $refresh_token = $_POST['refresh_token'];
        $token_expires_at = $_POST['token_expires_at'];
        $name = $_POST['name'];
        $active = $_POST['active'];
        $is_admin = $_POST['is_admin'];
        $notify_by = $_POST['notify_by'];
        $telegram_token_id = $_POST['telegram_token_id'];
        $telegram_chat_id = $_POST['telegram_chat_id'];
        $max_profile = $_POST['max_profile'];
        $computer_id = $_POST['computer_id'];
        $affiliate = isset($_POST['affiliate']) ? $_POST['affiliate'] : '0';
        $affiliate_code = isset($_POST['affiliate_code']) ? $_POST['affiliate_code'] : null;
        $referral_by = isset($_POST['referral_by']) ? $_POST['referral_by'] : null;

        // If user is an affiliate but doesn't have a code, generate one
        if ($affiliate == '1' && empty($affiliate_code)) {
            $affiliate_code = generateUniqueAffiliateCode($conn);
        }

        try {
            // Start transaction
            $conn->begin_transaction();

            // Insert the user
            $stmt = $conn->prepare("INSERT INTO users (user_id, display_name, email, picture_url, status_message, access_token, refresh_token, token_expires_at, name, active, is_admin, notify_by, telegram_token_id, telegram_chat_id, max_profile, Computer_ID, affiliate, affiliate_code, referral_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssiisississ", $user_id, $display_name, $email, $picture_url, $status_message, $access_token, $refresh_token, $token_expires_at, $name, $active, $is_admin, $notify_by, $telegram_token_id, $telegram_chat_id, $max_profile, $computer_id, $affiliate, $affiliate_code, $referral_by);
            $stmt->execute();

            // If user is an affiliate, create affiliate record
            if ($affiliate == '1' && $affiliate_code) {
                createAffiliateRecord($conn, $user_id, $affiliate_code);
            }

            // Commit transaction
            $conn->commit();

            echo "User inserted successfully.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "Error creating user: " . $e->getMessage();
        }
        $stmt->close();
    } elseif ($action === 'update') {
        try {
            $id = $_POST['id'];
            $display_name = $_POST['display_name'];
            $email = $_POST['email'];
            $picture_url = $_POST['picture_url'];
            $status_message = $_POST['status_message'];
            $access_token = $_POST['access_token'];
            $refresh_token = $_POST['refresh_token'];
            $token_expires_at = $_POST['token_expires_at'];
            $name = $_POST['name'];
            $active = $_POST['active'];
            $is_admin = $_POST['is_admin'];
            $Computer_ID = isset($_POST['Computer_ID']) && trim($_POST['Computer_ID']) !== '' ? $_POST['Computer_ID'] : null;
            $affiliate = isset($_POST['affiliate']) ? $_POST['affiliate'] : '0';
            $affiliate_code = isset($_POST['affiliate_code']) ? $_POST['affiliate_code'] : null;
            $referral_by = isset($_POST['referral_by']) ? $_POST['referral_by'] : null;
            $max_profile = isset($_POST['max_profile']) ? $_POST['max_profile'] : null;
            $telegram_token_id = isset($_POST['telegram_token_id']) ? $_POST['telegram_token_id'] : null;
            $telegram_chat_id = isset($_POST['telegram_chat_id']) ? $_POST['telegram_chat_id'] : null;
            $notify_by = isset($_POST['notify_by']) ? $_POST['notify_by'] : 'telegram';

            // Start transaction
            $conn->begin_transaction();

            // Get the user_id from the database using the id
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            $stmt->close();

            // ถ้าเป็น affiliate ต้องมี affiliate_code
            if ($affiliate == '1' && empty($affiliate_code)) {
                // สร้าง affiliate code ถ้ายังไม่มี
                $affiliate_code = generateUniqueAffiliateCode($conn);

                // Create or update the affiliate record
                createAffiliateRecord($conn, $user_id, $affiliate_code);
            }

            $stmt = $conn->prepare("UPDATE users SET 
                display_name = ?, 
                Computer_ID = ?, 
                email = ?, 
                picture_url = ?, 
                status_message = ?, 
                access_token = ?, 
                refresh_token = ?, 
                token_expires_at = ?, 
                name = ?, 
                active = ?, 
                is_admin = ?, 
                notify_by = ?,
                telegram_token_id = ?,
                telegram_chat_id = ?,
                max_profile = ?,
                affiliate = ?,
                affiliate_code = ?,
                referral_by = ?,
                updated_at = NOW() 
                WHERE id = ?");

            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sssssssssississisi",
                $display_name,
                $Computer_ID,
                $email,
                $picture_url,
                $status_message,
                $access_token,
                $refresh_token,
                $token_expires_at,
                $name,
                $active,
                $is_admin,
                $notify_by,
                $telegram_token_id,
                $telegram_chat_id,
                $max_profile,
                $affiliate,
                $affiliate_code,
                $referral_by,
                $id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            // Update affiliate status in affiliates table if needed
            if ($affiliate == '1') {
                $stmt = $conn->prepare("UPDATE affiliates SET status = 'active' WHERE user_id = ?");
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
            } else if ($affiliate == '0') {
                $stmt = $conn->prepare("UPDATE affiliates SET status = 'inactive' WHERE user_id = ?");
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
            }

            // Commit transaction
            $conn->commit();

            echo $stmt->affected_rows > 0 ? "User updated successfully." : "No changes made to user.";
            $stmt->close();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error in update operation: " . $e->getMessage());
            echo "Error updating user: " . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            if ($user) {
                echo json_encode($user);
            } else {
                echo json_encode(["error" => "User not found"]);
            }
        } else {
            echo json_encode(["error" => "Failed to execute query", "details" => $stmt->error]);
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = $_POST['id'];

        try {
            // Start transaction
            $conn->begin_transaction();

            // Get the user_id from the database using the id
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            $stmt->close();

            // Delete from affiliates if exists
            $stmt = $conn->prepare("DELETE FROM affiliates WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $stmt->close();

            // Delete from registration_tokens if exists
            $stmt = $conn->prepare("DELETE FROM registration_tokens WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $stmt->close();

            // Delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            echo "User deleted successfully.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "Error deleting user: " . $e->getMessage();
        }
    } elseif ($action === 'copy') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            if ($user) {
                // Generate a new user_id
                $new_user_id = $user['user_id'] . '_' . uniqid();

                // Insert new user with modified user_id
                $stmtInsert = $conn->prepare("INSERT INTO users (user_id, display_name, email, picture_url, status_message, access_token, refresh_token, token_expires_at, name, active, is_admin, notify_by, telegram_token_id, telegram_chat_id, max_profile, Computer_ID, affiliate, affiliate_code, referral_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $affiliate = isset($_POST['affiliate']) ? $_POST['affiliate'] : $user['affiliate'];
                $affiliate_code = null;

                // Generate new affiliate code if needed
                if ($affiliate == '1') {
                    $affiliate_code = generateUniqueAffiliateCode($conn);
                }

                $stmtInsert->bind_param(
                    "sssssssssiisississ",
                    $new_user_id,
                    $user['display_name'],
                    $user['email'],
                    $user['picture_url'],
                    $user['status_message'],
                    $user['access_token'],
                    $user['refresh_token'],
                    $user['token_expires_at'],
                    $user['name'],
                    $user['active'],
                    $user['is_admin'],
                    $user['notify_by'],
                    $user['telegram_token_id'],
                    $user['telegram_chat_id'],
                    $user['max_profile'],
                    $user['Computer_ID'],
                    $affiliate,
                    $affiliate_code,
                    $user['referral_by']
                );

                // Execute within transaction
                try {
                    $conn->begin_transaction();

                    $stmtInsert->execute();

                    // Create affiliate record if needed
                    if ($affiliate == '1' && $affiliate_code) {
                        createAffiliateRecord($conn, $new_user_id, $affiliate_code);
                    }

                    $conn->commit();
                    echo "User copied successfully.";
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "Error copying user: " . $e->getMessage();
                }

                $stmtInsert->close();
            } else {
                echo "User not found.";
            }
        } else {
            echo "Failed to fetch user details.";
        }
        $stmt->close();
    } elseif ($action === 'generate_token') {
        $user_id = $_POST['user_id'];
        $expiration_days = intval($_POST['expiration_days']);

        try {
            // Get user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user) {
                // Calculate expiration date
                $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiration_days} days"));

                // Direct JWT generation without external dependencies
                function generate_jwt_directly($userData, $expiration_days)
                {
                    // Use a default secret key if not found in config
                    $secret_key = defined('SECRET_KEY') ? SECRET_KEY : "AbAiManagement_SecretKey_2023";

                    $issuedAt = time();
                    $expirationTime = $issuedAt + ($expiration_days * 86400); // Convert days to seconds

                    $header = json_encode([
                        'typ' => 'JWT',
                        'alg' => 'HS256'
                    ]);

                    $payload = json_encode([
                        'iat' => $issuedAt,
                        'exp' => $expirationTime,
                        'data' => $userData
                    ]);

                    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
                    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

                    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
                    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

                    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
                }

                // Prepare user data
                $userData = [
                    'id' => $user['id'],
                    'user_id' => $user['user_id'],
                    'display_name' => $user['display_name'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'active' => $user['active'],
                    'is_admin' => $user['is_admin'],
                    'max_profile' => $user['max_profile'],
                    'Computer_ID' => $user['Computer_ID'],
                ];

                // Generate token using direct method
                $token = generate_jwt_directly($userData, $expiration_days);

                // Store token in database
                try {
                    $conn->begin_transaction();

                    // Check if token already exists for this user
                    $stmt = $conn->prepare("SELECT id FROM registration_tokens WHERE user_id = ?");
                    $stmt->bind_param("s", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        // Update existing token
                        $stmt = $conn->prepare("UPDATE registration_tokens SET token = ?, expires_at = ?, used = 0, used_at = NULL, created_at = NOW() WHERE user_id = ?");
                        $stmt->bind_param("sss", $token, $expires_at, $user_id);
                    } else {
                        // Insert new token
                        $stmt = $conn->prepare("INSERT INTO registration_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $user_id, $token, $expires_at);
                    }

                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();

                    echo json_encode(['success' => true, 'token' => $token]);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'User not found']);
            }
        } catch (Exception $e) {
            error_log("Token generation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } elseif ($action === 'search_users') {
        $search = $_POST['search'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE 
            user_id LIKE ? OR 
            display_name LIKE ? OR 
            email LIKE ? OR 
            name LIKE ? OR 
            affiliate_code = ? OR
            referral_by = ?
            LIMIT 10");

        $searchParam = "%{$search}%";
        $stmt->bind_param("ssssss", $searchParam, $searchParam, $searchParam, $searchParam, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode(['success' => true, 'users' => $users]);
        $stmt->close();
    }

    // End output buffering and clean up
    ob_end_clean();
    exit;
}

// Fetch users for display
$users = [];
$result = $conn->query("SELECT * FROM user_registration_view");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin AB Ai Management</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="index.php" class="nav-link">Home</a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?php include 'includes/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content">
                <div class="container-fluid">
                    <h1>Admin</h1>
                    <!-- Quick Search and Add User -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchUser"
                                    placeholder="Search users by name, email, ID, or affiliate code...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="searchResults" class="mt-2"></div>
                        </div>
                        <div class="col-md-4 text-right">
                            <button class="btn btn-success" data-toggle="modal" data-target="#insertModal">
                                <i class="fas fa-user-plus"></i> Add User
                            </button>
                        </div>
                    </div>

                    <!-- User Stats -->
                    <div class="row mb-3">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo count($users); ?></h3>
                                    <p>Total Users</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php
                                    $activeUsers = array_filter($users, function ($user) {
                                        return $user['active'] == 1;
                                    });
                                    echo count($activeUsers);
                                    ?></h3>
                                    <p>Active Users</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php
                                    $adminUsers = array_filter($users, function ($user) {
                                        return $user['is_admin'] == 1;
                                    });
                                    echo count($adminUsers);
                                    ?></h3>
                                    <p>Admin Users</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php
                                    $affiliateUsers = array_filter($users, function ($user) {
                                        return isset($user['affiliate']) && $user['affiliate'] == 1;
                                    });
                                    echo count($affiliateUsers);
                                    ?></h3>
                                    <p>Affiliates</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.Generate Token Section -->

                    <!-- Display Users -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Manage Users</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="table-responsive"> <!-- Added table-responsive for mobile support -->
                                <table id="userTable" class="table table-sm table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">#</th>
                                            <th>Display Name</th>
                                            <th>Email</th>
                                            <th>Name</th>
                                            <th>Picture</th>
                                            <th>Active</th>
                                            <th>Admin</th>
                                            <th>Expires Token</th>
                                            <th>Affiliate</th>
                                            <th>Affiliate Code</th>
                                            <th>Referral By</th>
                                            <th style="width: 150px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $index => $user): ?>
                                            <tr>
                                                <td><?= $index + 1 ?>.</td>
                                                <td><?= isset($user['display_name']) && $user['display_name'] ? htmlspecialchars($user['display_name']) : htmlspecialchars($user['name']) ?>
                                                </td>
                                                <td><?= isset($user['email']) ? htmlspecialchars($user['email']) : '' ?>
                                                </td>
                                                <td><?= isset($user['name']) ? htmlspecialchars($user['name']) : '' ?></td>
                                                <td>
                                                    <?php if (!empty($user['picture_url'])): ?>
                                                        <img class="img-circle elevation-2"
                                                            src="<?= htmlspecialchars($user['picture_url']) ?>"
                                                            alt="User Picture"
                                                            style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $user['active'] ? 'success' : 'danger' ?>">
                                                        <?= $user['active'] ? 'Yes' : 'No' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-<?= $user['is_admin'] ? 'primary' : 'secondary' ?>">
                                                        <?= $user['is_admin'] ? 'Yes' : 'No' ?>
                                                    </span>
                                                </td>
                                                <td><?= isset($user['expires_at']) ? htmlspecialchars($user['expires_at']) : '' ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-<?= isset($user['affiliate']) && $user['affiliate'] == '1' ? 'warning' : 'secondary' ?>">
                                                        <?= isset($user['affiliate']) && $user['affiliate'] == '1' ? 'Yes' : 'No' ?>
                                                    </span>
                                                </td>
                                                <td><?= isset($user['affiliate_code']) ? htmlspecialchars($user['affiliate_code']) : 'N/A' ?>
                                                </td>
                                                <td><?= isset($user['referral_by']) ? htmlspecialchars($user['referral_by']) : 'N/A' ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-primary btn-sm edit-btn"
                                                            data-user='<?= json_encode($user) ?>' data-toggle="modal"
                                                            data-target="#editModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm delete-btn"
                                                            data-id="<?= $user['id'] ?>" data-toggle="modal"
                                                            data-target="#deleteModal">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <button class="btn btn-success btn-sm gen-token-btn"
                                                            data-id="<?= $user['user_id'] ?>">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        <button class="btn btn-info btn-sm copy-btn"
                                                            data-user='<?= json_encode($user) ?>'>
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
                AB Ai Management
            </div>
            <strong>&copy; <?php echo date('Y'); ?> <a href="#">AB Ai Management</a>.</strong> All rights reserved.
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- Insert Modal -->
    <?php include './admin/insert_modal.php'; ?>
    <!-- Edit Modal -->
    <?php include './admin/editModal.php'; ?>
    <!-- Delete Modal -->
    <?php include './admin/deleteModal.php'; ?>
    <!-- Generate Token Modal -->
    <?php include './admin/generateTokenModal.php'; ?>
    <!-- Copy Modal -->
    <?php include './admin/copyModal.php'; ?>

    <!-- jQuery (load only once) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- AdminLTE JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <!-- Initialize DataTables -->
    <script>
        $(document).ready(function () {
            console.log('Document ready - initializing handlers');

            // Check if Bootstrap modal functionality is available
            if (typeof $.fn.modal !== 'function') {
                console.error('Bootstrap modal function is not available!');
                // Try to reload Bootstrap
                $('head').append('<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"><\/script>');
            } else {
                console.log('Bootstrap modal is available');
            }

            // Initialize all modals manually
            $('#generateTokenModal, #copyModal, #editModal, #deleteModal, #insertModal').each(function () {
                try {
                    $(this).modal({
                        show: false,
                        backdrop: 'static',
                        keyboard: false
                    });
                    console.log('Initialized modal:', this.id);
                } catch (e) {
                    console.error('Error initializing modal:', this.id, e);
                }
            });

            // Insert User
            $('#insertForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize() + '&action=insert';
                $.post('', formData, function (response) {
                    alert(response);
                    location.reload();
                });
            });

            // Edit User Form Submit
            $('#editForm').on('submit', function (e) {
                e.preventDefault();
                const formData = $(this).serialize() + '&action=update';
                $.post('', formData, function (response) {
                    alert(response);
                    location.reload();
                });
            });

            // Delete User - Use event delegation for proper handling after table redraws
            $(document).on('click', '.delete-btn', function () {
                const userId = $(this).data('id');
                if (!userId) {
                    alert('Invalid user ID');
                    return;
                }
                $('#deleteUserId').val(userId);
                // Reset the confirmation field and button state
                $('#confirmDelete').val('');
                $('#deleteUserBtn').prop('disabled', true);
                $('#deleteModal').modal('show');
            });

            $('#deleteForm').on('submit', function (e) {
                e.preventDefault();
                if ($('#confirmDelete').val() !== 'DELETE') {
                    alert('Please type DELETE to confirm');
                    return;
                }

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: $(this).serialize() + '&action=delete',
                    success: function (response) {
                        $('#deleteModal').modal('hide');
                        alert(response);
                        location.reload();
                    },
                    error: function () {
                        alert('An error occurred while deleting the user.');
                    }
                });
            });

            // Use event delegation for the confirmDelete field
            $(document).on('keyup', '#confirmDelete', function () {
                if ($(this).val() === 'DELETE') {
                    $('#deleteUserBtn').prop('disabled', false);
                } else {
                    $('#deleteUserBtn').prop('disabled', true);
                }
            });

            // Handle Gen Token button click - Use event delegation
            $(document).on('click', '.gen-token-btn', function () {
                const userId = $(this).data('id');
                $('#modal_user_id').val(userId);
                console.log('Setting user ID for token generation:', userId); // Debug log
                // Make sure modal is shown explicitly
                $('#generateTokenModal').modal('show');
            });
            function generateToken(userId, expirationDays) {
                $.ajax({
                    url: 'GenerateToken.php',
                    type: 'POST',
                    data: {
                        action: 'generate_token',
                        user_id: userId,
                        expiration_days: expirationDays
                    },
                    dataType: 'json',
                    success: function (response) {
                        console.log('Token generation response:', response);
                        if (response.success && response.token) {
                            $('#generatedTokenDisplay').val(response.token);
                        } else {
                            let errorMsg = response.error || 'Unknown error occurred';
                            alert('Error: ' + errorMsg);
                            $('#generatedTokenDisplay').val('');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error:', xhr.responseText);
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                alert('Error: ' + response.error);
                            } else {
                                alert('An error occurred while generating the token: ' + error);
                            }
                        } catch (e) {
                            if (xhr.responseText && xhr.responseText.length < 200) {
                                alert('Server error: ' + xhr.responseText);
                            } else {
                                alert('An error occurred while generating the token. Please check if get_jwt.php exists and is properly configured.');
                            }
                        }
                        $('#generatedTokenDisplay').val('');
                    }
                });
            }
            // Handle token generation
            $('#generateTokenForm').on('submit', function (e) {
                e.preventDefault();
                console.log('Generate token form submitted');
                const userId = $('#modal_user_id').val();
                const expirationDays = $('#modal_expiration_days').val();

                console.log('User ID:', userId, 'Expiration days:', expirationDays);

                if (!userId || !expirationDays) {
                    alert('Please fill all required fields');
                    return;
                }

                // Show loading state
                $('#generatedTokenDisplay').val('Generating token...');

                generateToken(userId, expirationDays);
            });

            // Copy generated token
            $('#copyTokenButton').on('click', function () {
                console.log('Copy token button clicked');
                const tokenText = $('#generatedTokenDisplay');
                tokenText.select();
                document.execCommand('copy');
                alert('Token copied to clipboard!');
            });

            // Copy User - Use event delegation
            $(document).on('click', '.copy-btn', function () {
                console.log('Copy button clicked');
                const userData = $(this).data('user');
                if (!userData) {
                    alert('Invalid user data. Please try again.');
                    return;
                }

                try {
                    let user;
                    if (typeof userData === 'string') {
                        user = JSON.parse(userData);
                    } else {
                        user = userData;
                    }

                    console.log('User data for copy:', user);

                    // Populate fields in the modal
                    $('#copyUserId').val(user.id);
                    $('#copyEmail').val(user.email);
                    $('#copyName').val(user.name);
                    $('#copyActive').val(user.active);
                    $('#copyIsAdmin').val(user.is_admin);
                    $('#copyDisplayName').val(user.display_name || '');
                    $('#copyComputerId').val(user.Computer_ID || '');
                    $('#copyAffiliate').val(user.affiliate || '0');
                    $('#copyAffiliateCode').val(user.affiliate_code || '');
                    $('#copyReferralBy').val(user.referral_by || '');

                    // Enable/disable affiliate buttons based on status
                    const isAffiliate = user.affiliate === '1';
                    toggleAffiliateControls('#copyAffiliateCode', isAffiliate);

                    // Show the modal
                    $('#copyModal').modal('show');
                } catch (e) {
                    console.error('Error parsing user data:', e);
                    alert('Error processing user data: ' + e.message);
                }
            });

            $('#copyForm').on('submit', function (e) {
                e.preventDefault();
                console.log('Copy form submitted');
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: $(this).serialize() + '&action=copy',
                    success: function (response) {
                        $('#copyModal').modal('hide');
                        alert(response);
                        location.reload();
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error:', xhr.responseText);
                        alert('An error occurred while copying the user.');
                    }
                });
            });

            // Generate affiliate code for button clicks
            $('.generate-code-btn').on('click', function () {
                const targetField = $(this).closest('.input-group').find('input');
                generateAffiliateCodeForField(targetField);
            });

            // Copy affiliate link for button clicks
            $('.copy-link-btn').on('click', function () {
                const code = $(this).closest('.input-group').find('input').val();
                if (!code) {
                    alert('No affiliate code to copy');
                    return;
                }

                // Create the affiliate link (adjust this based on your site structure)
                const affiliateLink = window.location.origin + '/register.php?ref=' + code;

                // Create a temporary input to copy to clipboard
                const tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(affiliateLink).select();
                document.execCommand('copy');
                tempInput.remove();

                alert('Affiliate link copied to clipboard!');
            });

            // User search functionality
            $('#searchUser').on('keyup', function () {
                const searchTerm = $(this).val();
                if (searchTerm.length < 3) return; // Only search with 3+ characters

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        action: 'search_users',
                        search: searchTerm
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            const results = $('#searchResults');
                            results.empty();

                            if (response.users.length === 0) {
                                results.append('<p>No users found</p>');
                                return;
                            }

                            response.users.forEach(function (user) {
                                results.append(`
                                    <div class="search-result">
                                        <p><strong>${user.name || user.display_name || 'Unnamed'}</strong> (${user.email})</p>
                                        <button class="btn btn-sm btn-primary quick-edit" data-id="${user.id}">Edit</button>
                                    </div>
                                `);
                            });

                            // Handle quick edit buttons
                            $('.quick-edit').on('click', function () {
                                const userId = $(this).data('id');
                                $.ajax({
                                    url: '',
                                    type: 'POST',
                                    data: {
                                        action: 'edit',
                                        id: userId
                                    },
                                    dataType: 'json',
                                    success: function (userData) {
                                        $('#editUserId').val(userData.id);
                                        $('#editEmail').val(userData.email);
                                        $('#editName').val(userData.name);
                                        $('#editActive').val(userData.active);
                                        $('#editIsAdmin').val(userData.is_admin);
                                        $('#editDisplayName').val(userData.display_name || '');
                                        $('#editPictureUrl').val(userData.picture_url || '');
                                        $('#editStatusMessage').val(userData.status_message || '');
                                        $('#editAccessToken').val(userData.access_token || '');
                                        $('#editRefreshToken').val(userData.refresh_token || '');
                                        $('#editTokenExpiresAt').val(userData.token_expires_at || '');
                                        $('#editNotifyBy').val(userData.notify_by || '');
                                        $('#editTelegramTokenId').val(userData.telegram_token_id || '');
                                        $('#editTelegramChatId').val(userData.telegram_chat_id || '');
                                        $('#editMaxProfile').val(userData.max_profile || '');
                                        $('#editComputerId').val(userData.Computer_ID || '');
                                        $('#editToken').val(userData.token || '');
                                        $('#editExpiresAt').val(userData.expires_at || '');
                                        $('#editAffiliate').val(userData.affiliate || '0');
                                        $('#editAffiliateCode').val(userData.affiliate_code || '');
                                        $('#editReferralBy').val(userData.referral_by || '');

                                        // Show edit modal
                                        $('#editModal').modal('show');
                                    },
                                    error: function () {
                                        alert('Failed to load user data');
                                    }
                                });
                            });
                        }
                    }
                });
            });
        });

        // Clear data for all modals when they are closed
        $('#insertModal, #editModal, #deleteModal, #generateTokenModal, #copyModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            if ($(this).attr('id') === 'generateTokenModal') {
                $('#generatedTokenDisplay').val('');
            }

            if ($(this).attr('id') === 'searchResults') {
                $('#searchResults').empty();
            }
        });

        // Generate affiliate code function
        function generateAffiliateCode() {
            generateAffiliateCodeForField('#affiliateCode');
        }

        // Copy affiliate link function
        function copyAffiliateLink() {
            const code = $('#affiliateCode').val();
            if (!code) {
                alert('Please generate an affiliate code first');
                return;
            }
            
            // Create the affiliate link
            const affiliateLink = window.location.origin + '/register.php?ref=' + code;
            
            // Create temporary input for copying
            const tempInput = document.createElement('input');
            document.body.appendChild(tempInput);
            tempInput.value = affiliateLink;
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            alert('Affiliate link copied to clipboard: ' + affiliateLink);
        }

        // Test Telegram Message button
        $(document).on('click', '#testTelegramBtn', function() {
            const chatId = $('#editTelegramChatId').val();
            const botToken = $('#editTelegramTokenId').val();
            
            if (!chatId) {
                alert('Please enter a Telegram Chat ID');
                return;
            }
            
            if (!botToken) {
                alert('Please enter a Telegram Bot Token');
                return;
            }
            
            // Show loading state
            const originalText = $(this).text();
            $(this).text('Sending...').prop('disabled', true);
            
            // Send test message
            $.ajax({
                url: 'callTelegram.php',
                type: 'POST',
                data: {
                    action: 'test_message',
                    chat_id: chatId,
                    bot_token: botToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Test message sent successfully!');
                    } else {
                        alert('Error: ' + (response.error || 'Failed to send message'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr.responseText);
                    alert('Error sending test message. Check console for details.');
                },
                complete: function() {
                    $('#testTelegramBtn').text(originalText).prop('disabled', false);
                }
            });
        });
    </script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function () {
            // Initialize DataTable with proper configuration
            var table = $('#userTable').DataTable({
                "responsive": true,
                "processing": true,
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[0, 'asc']],
                "language": {
                    "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
                    "zeroRecords": "ไม่พบข้อมูล",
                    "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                    "infoEmpty": "ไม่มีข้อมูล",
                    "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                    "search": "ค้นหา:",
                    "paginate": {
                        "first": "หน้าแรก",
                        "last": "หน้าสุดท้าย",
                        "next": "ถัดไป",
                        "previous": "ก่อนหน้า"
                    }
                },
                "columnDefs": [
                    {
                        "targets": -1,
                        "orderable": false,
                        "className": "dt-center" // Center align action buttons
                    },
                    {
                        "targets": [5, 6, 8], // Active, Admin, Affiliate columns
                        "className": "dt-center" // Center align status badges
                    }
                ],
                "drawCallback": function () {
                    // Ensure modals work correctly after table redraw
                    $('.modal').each(function () {
                        $(this).data('bs.modal', null);
                    });
                }
            });

            // Handle edit button clicks with event delegation
            $(document).on('click', '.edit-btn', function () {
                const userData = $(this).data('user');
                let user;

                try {
                    if (typeof userData === 'string') {
                        user = JSON.parse(userData);
                    } else {
                        user = userData;
                    }

                    console.log('User data:', user);
                    if (!user) {
                        alert('Invalid user data. Please try again.');
                        return;
                    }

                    // Populate all fields in the modal
                    $('#editUserId').val(user.id);
                    $('#editEmail').val(user.email);
                    $('#editName').val(user.name);
                    $('#editActive').val(user.active);
                    $('#editIsAdmin').val(user.is_admin);
                    $('#editDisplayName').val(user.display_name || '');
                    $('#editPictureUrl').val(user.picture_url || '');
                    $('#editStatusMessage').val(user.status_message || '');
                    $('#editAccessToken').val(user.access_token || '');
                    $('#editRefreshToken').val(user.refresh_token || '');
                    $('#editTokenExpiresAt').val(user.token_expires_at || '');
                    $('#editNotifyBy').val(user.notify_by || '');
                    $('#editTelegramTokenId').val(user.telegram_token_id || '');
                    $('#editTelegramChatId').val(user.telegram_chat_id || '');
                    $('#editMaxProfile').val(user.max_profile || '');
                    $('#editComputerId').val(user.Computer_ID || '');
                    $('#editToken').val(user.token || '');
                    $('#editExpiresAt').val(user.expires_at || '');
                    $('#editAffiliate').val(user.affiliate || '0');
                    $('#editAffiliateCode').val(user.affiliate_code || '');
                    $('#editReferralBy').val(user.referral_by || '');

                    // Enable/disable affiliate buttons based on status
                    const isAffiliate = user.affiliate === '1';
                    toggleAffiliateControls('#editAffiliateCode', isAffiliate);

                    // Show the modal
                    $('#editModal').modal('show');
                } catch (e) {
                    console.error('Error processing user data:', e);
                    alert('Error: ' + e.message);
                }
            });

            // Handle affiliate toggle in edit form
            $('#editAffiliate').on('change', function () {
                const isAffiliate = $(this).val() === '1';
                toggleAffiliateControls('#editAffiliateCode', isAffiliate);

                // Generate new affiliate code if needed
                if (isAffiliate && !$('#editAffiliateCode').val()) {
                    generateAffiliateCodeForField('#editAffiliateCode');
                }
            });

            // Handle affiliate toggle in insert form
            $('#affiliate').on('change', function () {
                const isAffiliate = $(this).val() === '1';
                toggleAffiliateControls('#affiliateCode', isAffiliate);

                // Generate new affiliate code if needed
                if (isAffiliate && !$('#affiliateCode').val()) {
                    generateAffiliateCodeForField('#affiliateCode');
                }
            });

            // Handle affiliate toggle in copy form
            $('#copyAffiliate').on('change', function () {
                const isAffiliate = $(this).val() === '1';
                toggleAffiliateControls('#copyAffiliateCode', isAffiliate);

                // Generate new affiliate code if needed
                if (isAffiliate && !$('#copyAffiliateCode').val()) {
                    generateAffiliateCodeForField('#copyAffiliateCode');
                }
            });
        });

        // Helper function to toggle affiliate controls
        function toggleAffiliateControls(fieldId, isAffiliate) {
            const generateBtn = $(fieldId).siblings('.input-group-append').find('button:first-child');
            const copyBtn = $(fieldId).siblings('.input-group-append').find('button:last-child');
            generateBtn.prop('disabled', !isAffiliate);
            copyBtn.prop('disabled', !isAffiliate);

            if (!isAffiliate) {
                $(fieldId).val('');
            }
        }

        // Generate affiliate code for a specific field
        function generateAffiliateCodeForField(fieldId) {
            // Generate a random code (8 characters, uppercase letters and numbers)
            const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            $(fieldId).val(code);
        }
    </script>
</body>

</html>