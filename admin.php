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

        $stmt = $conn->prepare("INSERT INTO users (user_id, display_name, email, picture_url, status_message, access_token, refresh_token, token_expires_at, name, active, is_admin, notify_by, telegram_token_id, telegram_chat_id, max_profile, Computer_ID, affiliate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssisissis", $user_id, $display_name, $email, $picture_url, $status_message, $access_token, $refresh_token, $token_expires_at, $name, $active, $is_admin, $notify_by, $telegram_token_id, $telegram_chat_id, $max_profile, $computer_id, $affiliate);
        $stmt->execute();
        echo $stmt->affected_rows > 0 ? "User inserted successfully." : "Failed to insert user.";
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

            // ถ้าเป็น affiliate ต้องมี affiliate_code
            if ($affiliate == '1' && empty($affiliate_code)) {
                // สร้าง affiliate code ถ้ายังไม่มี
                $affiliate_code = generateUniqueAffiliateCode($conn);
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
                affiliate = ?,
                affiliate_code = ?,
                referral_by = ?,
                updated_at = NOW() 
                WHERE id = ?");

            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("sssssssssiiissi", 
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
                $affiliate,
                $affiliate_code,
                $referral_by,
                $id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            echo $stmt->affected_rows > 0 ? "User updated successfully." : "No changes made to user.";
            $stmt->close();

        } catch (Exception $e) {
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

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo $stmt->affected_rows > 0 ? "User deleted successfully." : "Failed to delete user.";
        $stmt->close();
    } elseif ($action === 'copy') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            if ($user) {
                $stmtInsert = $conn->prepare("INSERT INTO users (user_id, display_name, email, picture_url, status_message, access_token, refresh_token, token_expires_at, name, active, is_admin, notify_by, telegram_token_id, telegram_chat_id, max_profile, Computer_ID, affiliate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $affiliate = isset($_POST['affiliate']) ? $_POST['affiliate'] : $user['affiliate'];
                $stmtInsert->bind_param("sssssssssisissisi", $user['user_id'], $user['display_name'], $user['email'], $user['picture_url'], $user['status_message'], $user['access_token'], $user['refresh_token'], $user['token_expires_at'], $user['name'], $user['active'], $user['is_admin'], $user['notify_by'], $user['telegram_token_id'], $user['telegram_chat_id'], $user['max_profile'], $user['Computer_ID'], $affiliate);
                $stmtInsert->execute();
                echo $stmtInsert->affected_rows > 0 ? "User copied successfully." : "Failed to copy user.";
                $stmtInsert->close();
            } else {
                echo "User not found.";
            }
        } else {
            echo "Failed to fetch user details.";
        }
        $stmt->close();
        ob_end_clean();
        exit;
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
                    <!-- Insert Button -->
                    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#insertModal">Add
                        User</button>
                    <!-- /.Generate Token Section -->

                    <!-- Display Users -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Manage Users</h3>
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
                                                <td><?= isset($user['display_name']) && $user['display_name'] ? htmlspecialchars($user['display_name']) : htmlspecialchars($user['name']) ?></td>
                                                <td><?= isset($user['email']) ? htmlspecialchars($user['email']) : '' ?></td>
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
                                                <td><?= $user['active'] ? 'Yes' : 'No' ?></td>
                                                <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
                                                <td><?= isset($user['expires_at']) ? htmlspecialchars($user['expires_at']) : '' ?></td>
                                                <td><?= isset($user['affiliate']) && $user['affiliate'] == '1' ? 'Yes' : 'No' ?></td>
                                                <td><?= isset($user['affiliate_code']) ? htmlspecialchars($user['affiliate_code']) : 'N/A' ?></td>
                                                <td><?= isset($user['referral_by']) ? htmlspecialchars($user['referral_by']) : 'N/A' ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm edit-btn"
                                                        data-user='<?= json_encode($user) ?>' data-toggle="modal"
                                                        data-target="#editModal">Edit</button>
                                                    <button class="btn btn-danger btn-sm delete-btn"
                                                        data-id="<?= $user['id'] ?>" data-toggle="modal"
                                                        data-target="#deleteModal">Delete</button>
                                                    <button class="btn btn-success btn-sm gen-token-btn"
                                                        data-id="<?= $user['user_id'] ?>" data-toggle="modal"
                                                        data-target="#generateTokenModal">Gen Token</button>
                                                    <button class="btn btn-info btn-sm copy-btn"
                                                        data-user='<?= json_encode($user) ?>' data-toggle="modal"
                                                        data-target="#copyModal">Copy</button>
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
                        "orderable": false
                    }
                ]
            });

            // Event handlers for buttons
            $('#userTable').on('click', '.edit-btn', function () {
                const user = $(this).data('user');
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
                const generateBtn = $('#editAffiliateCode').siblings('.input-group-append').find('button:first-child');
                const copyBtn = $('#editAffiliateCode').siblings('.input-group-append').find('button:last-child');
                generateBtn.prop('disabled', !isAffiliate);
                copyBtn.prop('disabled', !isAffiliate);
            });
        });
    </script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function () {
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

            // Delete User
            $('.delete-btn').on('click', function () {
                const userId = $(this).data('id');
                if (!userId) {
                    alert('Invalid user ID');
                    return;
                }
                $('#deleteUserId').val(userId);
            });

            $('#deleteForm').on('submit', function (e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
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

            // Handle Gen Token button click
            $('.gen-token-btn').on('click', function () {
                const userId = $(this).data('id');
                $('#modal_user_id').val(userId);
            });

            // Copy User
            $('.copy-btn').on('click', function () {
                const user = $(this).data('user');
                if (!user) {
                    alert('Invalid user data. Please try again.');
                    return;
                }

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
                const generateBtn = $('#copyAffiliateCode').siblings('.input-group-append').find('button:first-child');
                const copyBtn = $('#copyAffiliateCode').siblings('.input-group-append').find('button:last-child');
                generateBtn.prop('disabled', !isAffiliate);
                copyBtn.prop('disabled', !isAffiliate);
            });

            $('#copyForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: $(this).serialize() + '&action=copy',
                    success: function (response) {
                        $('#copyModal').modal('hide');
                        alert(response);
                        location.reload();
                    },
                    error: function () {
                        alert('An error occurred while copying the user.');
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
        });
    </script>

    <!-- Function to generate unique affiliate code -->
    <script>
        function generateUniqueAffiliateCode($conn) {
            try {
                $maxAttempts = 10;
                $attempt = 0;
                
                do {
                    // Generate random code
                    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
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
            } catch (Exception $e) {
                error_log("Error generating affiliate code: " . $e->getMessage());
                return null;
            }
        }
    </script>
</body>

</html>