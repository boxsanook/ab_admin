<?php
// Include database configuration
require_once '../webhook/config/config.php';

// Initialize response
$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get total users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $totalUsers = $stmt->fetchColumn();

    // Get active users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stmt->execute();
    $activeUsers = $stmt->fetchColumn();

    // Get new users (this month)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute();
    $newUsers = $stmt->fetchColumn();

    // Get inactive users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'inactive' OR status = 'suspended'");
    $stmt->execute();
    $inactiveUsers = $stmt->fetchColumn();

    // Prepare response
    $response['success'] = true;
    $response['data'] = [
        'total_users' => $totalUsers,
        'active_users' => $activeUsers,
        'new_users' => $newUsers,
        'inactive_users' => $inactiveUsers
    ];

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