<?php
session_start();
require_once '../webhook/config/config.php';
require_once 'get_jwt.php';
// Ensure the logs directory and file exist
$logDir = '../logs';
$logFile = $logDir . '/oauth_errors.log';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
if (!file_exists($logFile)) {
    touch($logFile);
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function logError($message)
{
    global $logFile;
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $logFile);
}

try {
    // Debugging: Log session and GET data
    logError('Session Data: ' . json_encode($_SESSION));
    logError('GET Data: ' . json_encode($_GET));

    // Validate state and session data
    if (!isset($_SESSION['oauth_state']) || !isset($_GET['code']) || !isset($_GET['state'])) {
        throw new Exception('Missing state or code in the request.');
    }

    if ($_GET['state'] !== $_SESSION['oauth_state']) {
        throw new Exception('State mismatch.');
    }

    if (time() > ($_SESSION['oauth_state_expires'] ?? 0)) {
        throw new Exception('State expired.');
    }

    unset($_SESSION['oauth_state'], $_SESSION['oauth_state_expires']);

    $code = $_GET['code'];
    $tokenUrl = 'https://api.line.me/oauth2/v2.1/token';
    $postData = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => Line_RedirectUri_callback,
        'client_id' => Line_Login_client_id,
        'client_secret' => Line_Login_Channel_Secret,
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Token error: ' . $response);
    }

    $tokenData = json_decode($response, true);
    $accessToken = $tokenData['access_token'];
    $idToken = $tokenData['id_token'] ?? null;
    $refreshToken = $tokenData['refresh_token'] ?? null;
    $expiresIn = $tokenData['expires_in'] ?? 3600;

    $email = '';
    if ($idToken) {
        $parts = explode('.', $idToken);
        if (count($parts) === 3) {
            $payload = $parts[1];
            $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            $email = $decoded['email'] ?? '';
        }
    }

    $profileUrl = 'https://api.line.me/v2/profile';
    $ch = curl_init($profileUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $profileResponse = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Profile error: ' . $profileResponse);
    }

    $profileData = json_decode($profileResponse, true);
    if (!isset($profileData['userId'])) {
        throw new Exception('Invalid profile data.');
    }

    // Save user data to the database
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        throw new Exception('Database connection error: ' . $db->connect_error);
    }

    // Check if the user exists in the database
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
    $stmt->bind_param('s', $profileData['userId']);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    $stmt->bind_result($userExists);
    $stmt->fetch();
    $stmt->close();

    if ($userExists) {
        // Correct the bind_result call to match the selected fields
        $stmt = $db->prepare("SELECT is_admin, active FROM users WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $db->error);
        }
        $stmt->bind_param('s', $profileData['userId']);
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }

        // Ensure the bind_result matches the selected fields
        $stmt->bind_result($isAdmin, $isActive);
        $stmt->fetch();
        $stmt->close();

        $stmt = $db->prepare("UPDATE users SET display_name = ?, email = ?, picture_url = ?, status_message = ?, updated_at = NOW() WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $db->error);
        }

        // Ensure variables are initialized
        $displayName = $profileData['displayName'] ?? '';
        $email = $email ?? '';
        $pictureUrl = $profileData['pictureUrl'] ?? '';
        $statusMessage = $profileData['statusMessage'] ?? '';
        $userId = $profileData['userId'];

        $stmt->bind_param('sssss', $displayName, $email, $pictureUrl, $statusMessage, $userId);
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        // Insert new user data
        $pictureUrl = $profileData['pictureUrl'] ?? '';
        $statusMessage = $profileData['statusMessage'] ?? '';
        $notifyBy = $notifyBy ?? 'telegram';
        $telegramTokenId = $telegramTokenId ?? '';
        $telegramChatId = $telegramChatId ?? '';
        $maxProfile = $maxProfile ?? 3;
        $computerId = $computerId ?? '';
        $name = $profileData['displayName'] ?? '';
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        // Default values for new users
        $isAdmin = 0;
        $isActive = 1;

        $stmt = $db->prepare("INSERT INTO users (user_id, display_name, email, picture_url, status_message, access_token, refresh_token, token_expires_at, created_at, notify_by, telegram_token_id, telegram_chat_id, max_profile, Computer_ID, name, is_admin, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $db->error);
        }

        $stmt->bind_param(
            'ssssssssssisssii',
            $profileData['userId'],
            $profileData['displayName'],
            $email,
            $pictureUrl,
            $statusMessage,
            $accessToken,
            $refreshToken,
            $expiresAt,
            $notifyBy,
            $telegramTokenId,
            $telegramChatId,
            $maxProfile,
            $computerId,
            $name,
            $isAdmin,
            $isActive
        );
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }else{
            auto_gentoken( $db ,$profileData['userId'], 7); // Call the function to generate token
        }
        $stmt->close();
    }

    // Redirect based on admin and active status
    if ($isActive == 0) {
        throw new Exception('User is not active.');
    }

    $_SESSION['line_user'] = $profileData;
    $_SESSION['authenticated'] = true;
    $_SESSION['isAdmin'] = $isAdmin;
    $_SESSION['userActive'] = $isActive;
    $_SESSION['data_admin'] = [
        'user_id' => $profileData['userId'],
        'display_name' => $profileData['displayName'],
        'email' => $email,
        'picture_url' => $profileData['pictureUrl'] ?? '',
        'status_message' => $profileData['statusMessage'] ?? ''
    ];

    // Generate JWT and set cookie
    $jwtToken = NewGenerateJwt($profileData, $isAdmin, $isActive, $email);
    setcookie('login_token', $jwtToken, time() + (86400 * 1), "/", "", false, true); // 1 วัน
    session_regenerate_id(true);

    // Redirect based on role
    if ($isAdmin == 1) {
        header('Location: admin.php');
    } else {
        header('Location: profile.php');
    }
    exit;

    $stmt->close();
    $db->close();

} catch (Exception $e) {
    logError($e->getMessage());
    // Display the error message in a card with a login button
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f9;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .error-card {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 400px;
                width: 100%;
            }
            .error-card h1 {
                color: #e74c3c;
                font-size: 24px;
                margin-bottom: 10px;
            }
            .error-card p {
                color: #333;
                margin-bottom: 20px;
            }
            .error-card button {
                background-color: #3498db;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
            }
            .error-card button:hover {
                background-color: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h1>Error</h1>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <button onclick="window.location.href=\'login.php\'">Go to Login</button>
        </div>
    </body>
    </html>';
    exit;
}

function auto_gentoken($db, $user_id, $expiration_days = 7)
{
    // Fetch user details from the database
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc(); // ✅ ใช้ mysqli ให้ถูกต้อง

    if ($user) {
        // Generate JWT
        $token = generateJwt($user, $expiration_days);

        // Check if the user_id already exists in the registration_tokens table
        $stmt = $db->prepare("SELECT * FROM registration_tokens WHERE user_id = ?");
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingToken = $result->fetch_assoc(); // ✅ ยังใช้ mysqli

        if ($existingToken) {
            // Update the existing token
            $stmt = $db->prepare("UPDATE registration_tokens SET token = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? DAY), used = 0, created_at = NOW() WHERE user_id = ?");
            $stmt->bind_param('sis', $token, $expiration_days, $user_id);
            $stmt->execute();
            $message = "Token updated successfully";
        } else {
            // Insert a new token
            $stmt = $db->prepare("INSERT INTO registration_tokens (user_id, token, expires_at, used, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), 0, NOW())");
            $stmt->bind_param('ssi', $user_id, $token, $expiration_days);
            $stmt->execute();
            $message = "Token generated and inserted successfully";
        }

        // Return the generated token
        // echo json_encode(['token' => $token, 'message' => $message]);
        // exit;
    }
}

