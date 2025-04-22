<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Check if the required session variables are set
if (!isset($_SESSION['line_user'], $_SESSION['authenticated'], $_SESSION['isAdmin'], $_SESSION['userActive'])) {
    header('Location: login.php');
    exit;
}

// Check if user is admin
if (!$_SESSION['isAdmin']) {
    header('Location: index.php');
    exit;
}

require_once '../webhook/config/config.php';
require_once '../webhook/config.php';
require_once '../webhook/vendor/autoload.php';
use \Firebase\JWT\JWT;

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate a JWT token
function generateJwt($user, $days = 1) {
    $issuedAt = time();
    $expirationTime = $issuedAt + ($days * 24 * 60 * 60);  // Expiration time in days
    $payload = array(
        "iat" => $issuedAt,
        "exp" => $expirationTime,
        "data" => $user
    );

    return JWT::encode($payload, SECRET_KEY, 'HS256');
}

// Handle AJAX request for DataTables
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && isset($_GET['action']) && $_GET['action'] == 'list') {
    // Parameters from DataTables
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 5;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

    // Column names for ordering
    $columns = array(
        0 => 'rt.id',
        1 => 'u.name',
        2 => 'rt.token',
        3 => 'rt.expires_at',
        4 => 'rt.used',
        5 => 'rt.created_at'
    );

    // Base query
    $sql = "FROM registration_tokens rt 
            LEFT JOIN users u ON rt.user_id = u.user_id";

    // Search condition
    $searchWhere = "";
    if (!empty($search)) {
        $searchWhere = " WHERE (rt.user_id LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR rt.token LIKE ?)";
        $search = "%$search%";
    }

    // Count total records
    $totalQuery = "SELECT COUNT(*) as total " . $sql;
    $total = $conn->query($totalQuery)->fetch_assoc()['total'];

    // Count filtered records
    $filteredQuery = "SELECT COUNT(*) as total " . $sql . $searchWhere;
    if (!empty($search)) {
        $stmt = $conn->prepare($filteredQuery);
        $stmt->bind_param("ssss", $search, $search, $search, $search);
        $stmt->execute();
        $filtered = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $filtered = $total;
    }

    // Get the data
    $query = "SELECT rt.*, u.name as user_name, u.email " . 
             $sql . $searchWhere . 
             " ORDER BY " . $columns[$orderColumn] . " " . $orderDir . 
             " LIMIT ? OFFSET ?";

    if (!empty($search)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssii", $search, $search, $search, $search, $length, $start);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $length, $start);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();

    while ($row = $result->fetch_assoc()) {
        // Format dates
        $expires_at = date('Y-m-d H:i', strtotime($row['expires_at']));
        $created_at = date('Y-m-d H:i', strtotime($row['created_at']));
        
        // Build actions HTML
        $actions = '<div class="action-buttons">';
        $actions .= '<button type="button" class="btn btn-primary btn-sm edit-btn" data-toggle="tooltip" title="Edit token" ' .
                   'data-id="' . htmlspecialchars($row['id']) . '" ' .
                   'data-userid="' . htmlspecialchars($row['user_id']) . '" ' .
                   'data-expires="' . htmlspecialchars($row['expires_at']) . '">' .
                   '<i class="fas fa-edit"></i></button>';
        $actions .= '<form action="token_management.php" method="POST" class="d-inline">' .
                   '<input type="hidden" name="action" value="delete">' .
                   '<input type="hidden" name="token_id" value="' . $row['id'] . '">' .
                   '<button type="submit" class="btn btn-danger btn-sm" data-toggle="tooltip" title="Delete token">' .
                   '<i class="fas fa-trash"></i></button></form>';
        $actions .= '</div>';

        // Format token cell
        $tokenCell = '<div class="d-flex align-items-center">' .
                    '<span class="text-truncate mr-2">' . htmlspecialchars($row['token']) . '</span>' .
                    '<button class="btn btn-sm btn-outline-secondary copy-btn" ' .
                    'data-toggle="tooltip" title="Copy token" ' .
                    'data-token="' . htmlspecialchars($row['token']) . '">' .
                    '<i class="fas fa-copy"></i></button></div>';

        // Build data array
        $data[] = array(
            htmlspecialchars($row['id']),
            htmlspecialchars($row['user_name'] ?? 'N/A') . '<br><small>' . htmlspecialchars($row['email'] ?? '') . '</small>',
            $tokenCell,
            array(
                'display' => $expires_at,
                'timestamp' => strtotime($row['expires_at'])
            ),
            $row['used'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>',
            array(
                'display' => $created_at,
                'timestamp' => strtotime($row['created_at'])
            ),
            $actions
        );
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(array(
        "draw" => $draw,
        "recordsTotal" => $total,
        "recordsFiltered" => $filtered,
        "data" => $data
    ));
    exit;
}

// Handle token actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $response = ['success' => false, 'message' => ''];
        
        switch ($_POST['action']) {
            case 'delete':
                if (isset($_POST['token_id'])) {
                    $stmt = $conn->prepare("DELETE FROM registration_tokens WHERE id = ?");
                    $stmt->bind_param("i", $_POST['token_id']);
                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Token deleted successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to delete token'];
                    }
                    $stmt->close();
                }
                break;
            
            case 'create':
                if (isset($_POST['user_id'])) {
                    // First, check if user exists
                    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->bind_param("s", $_POST['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($user) {
                        // Generate JWT token with 30 days expiration
                        $token = generateJwt($user, 30);
                        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        // Check if user already has a token
                        $stmt = $conn->prepare("SELECT id FROM registration_tokens WHERE user_id = ?");
                        $stmt->bind_param("s", $_POST['user_id']);
                        $stmt->execute();
                        $existing = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        
                        if ($existing) {
                            // Update existing token
                            $stmt = $conn->prepare("UPDATE registration_tokens SET token = ?, expires_at = ?, used = 0, created_at = NOW() WHERE user_id = ?");
                            $stmt->bind_param("sss", $token, $expires_at, $_POST['user_id']);
                        } else {
                            // Create new token
                            $stmt = $conn->prepare("INSERT INTO registration_tokens (user_id, token, expires_at, used, created_at) VALUES (?, ?, ?, 0, NOW())");
                            $stmt->bind_param("sss", $_POST['user_id'], $token, $expires_at);
                        }
                        
                        if ($stmt->execute()) {
                            $response = ['success' => true, 'message' => 'Token ' . ($existing ? 'updated' : 'created') . ' successfully'];
                        } else {
                            $response = ['success' => false, 'message' => 'Failed to ' . ($existing ? 'update' : 'create') . ' token'];
                        }
                        $stmt->close();
                    } else {
                        $response = ['success' => false, 'message' => 'User not found'];
                    }
                }
                break;

            case 'edit':
                if (isset($_POST['token_id'], $_POST['user_id'], $_POST['expires_at'])) {
                    // First, check if user exists
                    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->bind_param("s", $_POST['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($user) {
                        // Calculate days difference for JWT token
                        $now = new DateTime();
                        $expires = new DateTime($_POST['expires_at']);
                        $days = $expires->diff($now)->days;
                        
                        // Generate new JWT token
                        $token = generateJwt($user, $days);
                        
                        // Update token in database
                        $stmt = $conn->prepare("UPDATE registration_tokens SET user_id = ?, token = ?, expires_at = ?, used = 0 WHERE id = ?");
                        $stmt->bind_param("sssi", $_POST['user_id'], $token, $_POST['expires_at'], $_POST['token_id']);
                        
                        if ($stmt->execute()) {
                            $response = ['success' => true, 'message' => 'Token updated successfully'];
                        } else {
                            $response = ['success' => false, 'message' => 'Failed to update token'];
                        }
                        $stmt->close();
                    } else {
                        $response = ['success' => false, 'message' => 'User not found'];
                    }
                }
                break;
        }
        
        // If it's an AJAX request, return JSON response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        // Otherwise, redirect with message in session
        $_SESSION['token_message'] = $response['message'];
        $_SESSION['token_success'] = $response['success'];
        header('Location: token_management.php');
        exit;
    }
}

// Get all tokens with user information
$query = "SELECT rt.*, u.name as user_name, u.email 
          FROM registration_tokens rt 
          LEFT JOIN users u ON rt.user_id = u.user_id 
          ORDER BY rt.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Management - Admin AB Ai Management</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <style>
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 10px;
        }
        .action-buttons {
            white-space: nowrap;
            min-width: 100px;
        }
        .token-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media screen and (max-width: 767px) {
            .action-buttons {
                display: flex;
                gap: 5px;
            }
            .token-cell {
                max-width: 150px;
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include 'includes/navbar.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Token Management</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Token Management</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    

                    <!-- Tokens List Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Active Tokens</h3>
                        </div>
                        <div class="card-body">
                            <table id="tokensTable" class="table table-bordered table-striped dt-responsive nowrap" width="100%">
                                <thead>
                                    <tr>
                                        <th data-priority="1">ID</th>
                                        <th data-priority="2">User</th>
                                        <th data-priority="3">Token</th>
                                        <th data-priority="4">Expires At</th>
                                        <th data-priority="4">Used</th>
                                        <th data-priority="5">Created At</th>
                                        <th data-priority="1" class="no-sort">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['user_name'] ?? 'N/A'); ?><br>
                                            <small><?php echo htmlspecialchars($row['email'] ?? ''); ?></small>
                                        </td>
                                        <td class="token-cell">
                                            <div class="d-flex align-items-center">
                                                <span class="text-truncate mr-2"><?php echo htmlspecialchars($row['token']); ?></span>
                                                <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                                        data-toggle="tooltip"
                                                        title="Copy token"
                                                        data-token="<?php echo htmlspecialchars($row['token']); ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td data-order="<?php echo strtotime($row['expires_at']); ?>">
                                            <?php echo date('Y-m-d H:i', strtotime($row['expires_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($row['used']): ?>
                                                <span class="badge badge-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-order="<?php echo strtotime($row['created_at']); ?>">
                                            <?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" 
                                                        class="btn btn-primary btn-sm edit-btn" 
                                                        data-toggle="tooltip"
                                                        title="Edit token"
                                                        data-id="<?php echo htmlspecialchars($row['id']); ?>"
                                                        data-userid="<?php echo htmlspecialchars($row['user_id']); ?>"
                                                        data-expires="<?php echo htmlspecialchars($row['expires_at']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="token_management.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="token_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" 
                                                            class="btn btn-danger btn-sm"
                                                            data-toggle="tooltip"
                                                            title="Delete token"
                                                            onclick="return confirm('Are you sure you want to delete this token?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                AB Ai Management
            </div>
            <strong>&copy; <?php echo date('Y'); ?> <a href="#">AB Ai Management</a>.</strong> All rights reserved.
        </footer>
    </div>

    <!-- Edit Token Modal -->
    <div class="modal fade" id="editTokenModal" tabindex="-1" role="dialog" aria-labelledby="editTokenModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTokenModalLabel">Edit Token</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="token_management.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="token_id" id="edit_token_id">
                        
                        <div class="form-group">
                            <label for="edit_user_id">User ID</label>
                            <input type="text" class="form-control" id="edit_user_id" name="user_id" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_expires_at">Expiration Date</label>
                            <input type="datetime-local" class="form-control" id="edit_expires_at" name="expires_at" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
    
    <script>
        
    $(document).ready(function() {
        // Show message if exists
        <?php if (isset($_SESSION['token_message'])): ?>
            const alertType = <?php echo $_SESSION['token_success'] ? "'success'" : "'danger'"; ?>;
            const message = <?php echo json_encode($_SESSION['token_message']); ?>;
            showAlert(message, alertType);
            <?php 
            unset($_SESSION['token_message']);
            unset($_SESSION['token_success']);
            ?>
        <?php endif; ?>

        // Function to show alert messages
        function showAlert(message, type = 'info') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            $('.content-header').after(alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }

        // Initialize DataTable with improved configuration
        const table = $('#tokensTable').DataTable({
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function(row) {
                            return 'Token Details';
                        }
                    }),
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            order: [[5, 'desc']], // Sort by created_at by default
            pageLength: 25,
            columnDefs: [
                { targets: 'no-sort', orderable: false },
                { 
                    targets: [3, 5], // Expires At and Created At columns
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return new Date(data * 1000).toLocaleString();
                        }
                        return data;
                    }
                }
            ],
            language: {
                search: "Search tokens:",
                lengthMenu: "Show _MENU_ tokens per page",
                info: "Showing _START_ to _END_ of _TOTAL_ tokens",
                infoEmpty: "No tokens available",
                infoFiltered: "(filtered from _MAX_ total tokens)"
            },
            drawCallback: function() {
                // Reinitialize tooltips after table redraw
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        // Handle form submissions with AJAX
        $('form').on('submit', function(e) {
            const form = $(this);
            const isDelete = form.find('input[name="action"]').val() === 'delete';
            
            if (isDelete && !confirm('Are you sure you want to delete this token?')) {
                e.preventDefault();
                return false;
            }
            
            if (!isDelete) {
                e.preventDefault();
                
                // Form validation
                const userId = form.find('input[name="user_id"]').val().trim();
                const expiresAt = form.find('input[name="expires_at"]').val();
                
                if (!userId) {
                    showAlert('Please enter a user ID', 'warning');
                    return false;
                }
                
                if (expiresAt) {
                    const expiresDate = new Date(expiresAt);
                    if (isNaN(expiresDate.getTime())) {
                        showAlert('Please enter a valid expiration date', 'warning');
                        return false;
                    }
                }
            }
            
            // Submit form with AJAX
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    showAlert(response.message, response.success ? 'success' : 'danger');
                    if (response.success) {
                        
                        // Close modal if open
                        $('.modal').modal('hide');
                        // Reload table data
                       window.location.reload();
                    }
                },
                error: function() {
                    showAlert('An error occurred while processing your request', 'danger');
                }
            });
            
            return false;
        });

        // Copy token functionality with improved feedback
        $('.copy-btn').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const token = button.data('token');
            
            navigator.clipboard.writeText(token).then(function() {
                const originalTitle = button.attr('data-original-title');
                button.attr('data-original-title', 'Copied!').tooltip('show');
                
                setTimeout(function() {
                    button.attr('data-original-title', originalTitle).tooltip('hide');
                }, 1000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                button.attr('data-original-title', 'Failed to copy').tooltip('show');
            });
        });

        // Edit token modal population with improved error handling
        $('.edit-btn').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const id = button.data('id');
            const userId = button.data('userid');
            const expires = button.data('expires');
            
            try {
                $('#edit_token_id').val(id);
                $('#edit_user_id').val(userId);
                
                // Convert MySQL datetime to input datetime-local format
                const expiresDate = new Date(expires);
                if (isNaN(expiresDate.getTime())) {
                    throw new Error('Invalid date');
                }
                const formattedExpires = expiresDate.toISOString().slice(0, 16);
                $('#edit_expires_at').val(formattedExpires);
                
                $('#editTokenModal').modal('show');
            } catch (error) {
                console.error('Error populating modal:', error);
                showAlert('Error loading token data. Please try again.', 'danger');
            }
        });

        // Initialize all tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
    </script>
</body>
</html> 