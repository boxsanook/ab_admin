<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

header('Content-Type: application/json');

// Handle POST requests for test messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_message') {
    $chat_id = isset($_POST['chat_id']) ? $_POST['chat_id'] : null;
    $bot_token = isset($_POST['bot_token']) ? $_POST['bot_token'] : null;
    
    if (!$chat_id || !$bot_token) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing chat_id or bot_token'
        ]);
        exit;
    }
    
    // Send a test message
    $message = "✅ This is a test message from AB Ai Management. Your Telegram notifications are working correctly!";
    $result = sendTelegramMessage($bot_token, $chat_id, $message);
    
    echo json_encode($result);
    exit;
}

// Original code for checking codes
$data = json_decode(file_get_contents('php://input'), true);
$codeToCheck = $data['code'];

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.telegram.org/bot7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U/getUpdates',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10,
));

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

$matchedChatId = null;

if ($result['ok']) {
    foreach ($result['result'] as $update) {
        if (isset($update['message']['text']) && $update['message']['text'] === $codeToCheck) {
            $matchedChatId = $update['message']['chat']['id'];
            break;
        }
    }
}

if ($matchedChatId) {
    echo json_encode([
        'match' => true,
        'chat_id' => $matchedChatId
    ]);
} else {
    echo json_encode([
        'match' => false
    ]);
}

/**
 * Send a message to Telegram
 * 
 * @param string $bot_token The Telegram bot token
 * @param string $chat_id The Telegram chat ID
 * @param string $message The message to send
 * @return array Result with success status and error message if any
 */
function sendTelegramMessage($bot_token, $chat_id, $message) {
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => "cURL Error: $error"
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($result['ok']) {
        return [
            'success' => true
        ];
    } else {
        return [
            'success' => false,
            'error' => isset($result['description']) ? $result['description'] : 'Unknown error occurred'
        ];
    }
}
