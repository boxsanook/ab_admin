<?php
session_start();

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

require_once '../webhook/config/config.php';

$host = DB_HOST;
$password = DB_PASS;
$username = DB_USER;
$dbname = DB_NAME;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['line_user']['userId'] ?? null;

    if ($userId) {
        $notifyBy = $_POST['notifyBy'] ?? null;
        $maxProfile = $_POST['maxProfile'] ?? null;
        $computerId = $_POST['computerId'] ?? null;
        $telegramTokenId = $_POST['telegramTokenId'] ?? null;
        $telegramChatId = $_POST['telegramChatId'] ?? null;
        $statusMessage = $_POST['statusMessage'] ?? null;
        $name = $_POST['name'] ?? null;

        $stmt = $pdo->prepare("
            UPDATE users 
            SET notify_by = :notify_by,               
                telegram_token_id = :telegram_token_id,
                telegram_chat_id = :telegram_chat_id, 
                name = :name
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            'notify_by' => $notifyBy,           
            'telegram_token_id' => $telegramTokenId,
            'telegram_chat_id' => $telegramChatId, 
            'name' => $name,
            'user_id' => $userId
        ]);

        header('Location: settings.php');
        exit;
    }
}
?>
