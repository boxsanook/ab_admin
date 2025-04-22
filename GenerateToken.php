<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Check if the required session variables are set
if (!isset($_SESSION['line_user'], $_SESSION['authenticated'], $_SESSION['isAdmin'], $_SESSION['userActive'])) {
    // Redirect to login if session variables are missing
    header('Location: login.php');
    exit;
}

// Ensure the user is authenticated and is an admin
if (!$_SESSION['authenticated'] || $_SESSION['isAdmin'] != 1) {
    // Redirect to an error page or login if the user is not an admin
    header('Location: error.php');
    exit;
}

include('../webhook/config/config.php');  // Corrected path to the configuration file
include('../webhook/config.php');  // Database connection file
require_once '../webhook/vendor/autoload.php';
use \Firebase\JWT\JWT;


// Function to generate a JWT
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id']) && isset($_POST['expiration_days'])) {
        $user_id = $_POST['user_id'];
        $expiration_days = $_POST['expiration_days'];
        
        // Fetch user details from the database
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate JWT
            $token = generateJwt($user, $expiration_days);
            
            // Check if the user_id already exists in the registration_tokens table
            $stmt = $db->prepare("SELECT * FROM registration_tokens WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $existingToken = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingToken) {
                // Update the existing token
                $stmt = $db->prepare("UPDATE registration_tokens SET token = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? DAY), used = 0, created_at = NOW() WHERE user_id = ?");
                $stmt->execute([$token, $expiration_days, $user_id]);
                $message = "Token updated successfully";
            } else {
                // Insert a new token
                $stmt = $db->prepare("INSERT INTO registration_tokens (user_id, token, expires_at, used, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), 0, NOW())");
                $stmt->execute([$user_id, $token, $expiration_days]);
                $message = "Token generated and inserted successfully";
            }
            
            // Return the generated token
             echo json_encode([  "success" => true,'token' => $token , 'message' => $message]);
            exit;
        } else {
            $error = "User not found";
        }
    } else {
        $error = "User ID and expiration days are required";
    }
    
    // Return error message
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}
?>