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

// Check if user_id is provided
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    $response['message'] = 'User ID is required';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$userId = $_POST['user_id'];

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

    // Start transaction
    $pdo->beginTransaction();

    // Delete related records if needed (example: user tokens, user profile, etc.)
    // $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?");
    // $stmt->execute([$userId]);

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $result = $stmt->execute([$userId]);

    if ($result) {
        // Commit transaction
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'User deleted successfully';
    } else {
        // Rollback transaction
        $pdo->rollBack();
        
        $response['message'] = 'Failed to delete user';
    }

} catch (PDOException $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log('Database Error: ' . $e->getMessage());
    
    // Return error response
    $response['message'] = 'Database Error: ' . $e->getMessage();
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?> 