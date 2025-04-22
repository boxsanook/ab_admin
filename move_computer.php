<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

require_once '../webhook/config/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$computerId = $input['computerId'] ?? null;
$userId = $_SESSION['line_user']['userId'] ?? null;

if (!$computerId || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update computer ID (or do your logic here)
    $stmt = $pdo->prepare("UPDATE users SET computer_id =null  WHERE user_id = :user_id and computer_id  = :computer_id ");
    $stmt->execute([  'user_id' => $userId,'computer_id' => $computerId]);

    echo json_encode(['success' => true, 'message' => 'Computer ID moved!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
