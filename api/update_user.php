<?php
// Include database configuration
require_once '../webhook/config/config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check required fields
$requiredFields = ['user_id', 'username', 'email', 'role', 'status'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    $response['message'] = 'Missing required fields: ' . implode(', ', $missingFields);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get form data
$userId = $_POST['user_id'];
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$role = $_POST['role'];
$status = $_POST['status'];

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email address';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->fetchColumn() == 0) {
        $response['message'] = 'User not found';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Check if username or email already exists for other users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->execute([$username, $email, $userId]);
    
    if ($stmt->fetchColumn() > 0) {
        $response['message'] = 'Username or email already exists for another user';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Update user
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ?, updated_at = NOW() WHERE user_id = ?");
    $result = $stmt->execute([$username, $email, $role, $status, $userId]);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'User updated successfully';
    } else {
        $response['message'] = 'Failed to update user';
    }

} catch (PDOException $e) {
    // Log error
    error_log('Database Error: ' . $e->getMessage());
    
    // Return error response
    $response['message'] = 'Database Error: ' . $e->getMessage();
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?> 