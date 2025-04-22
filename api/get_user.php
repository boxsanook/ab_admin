<?php
// Include database configuration
require_once '../webhook/config/config.php';

// Initialize response
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

// Check if user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    $response['message'] = 'User ID is required';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$userId = $_GET['user_id'];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user data
    $stmt = $pdo->prepare("SELECT user_id, username, email, role, status, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $response['success'] = true;
        $response['data'] = $user;
    } else {
        $response['message'] = 'User not found';
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