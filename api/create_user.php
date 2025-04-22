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
$requiredFields = ['username', 'email', 'password', 'role'];
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
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$role = $_POST['role'];

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

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetchColumn() > 0) {
        $response['message'] = 'Username or email already exists';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
    $result = $stmt->execute([$username, $email, $hashedPassword, $role]);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'User created successfully';
        $response['user_id'] = $pdo->lastInsertId();
    } else {
        $response['message'] = 'Failed to create user';
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