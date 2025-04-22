<?php
include('../webhook/config/config.php');  // Corrected path to the configuration file
include('../webhook/config.php');  // Database connection file
require_once '../webhook/vendor/autoload.php';
use \Firebase\JWT\JWT;


// Function to generate a JWT
function generateJwt($user, $days = 1) {
    $key =  SECRET_KEY; // Replace with your secret key
    $issuedAt = time();
    $expirationTime = $issuedAt + ($days * 86400); // Token valid for $days
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'data' => [
            'user_id' => $user['userId']?? null,
            'display_name' => $user['displayName']?? 'Unknown',
            'email' => $user['email'] ?? '',
            'is_admin' => $user['isAdmin'] ?? 0,
            'is_active' => $user['userActive'] ?? 1
        ]
    ];

    return \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
}

// ฟังก์ชันสร้าง JWT
function NewGenerateJwt($profileData, $isAdmin, $isActive, $email) {
    $key = SECRET_KEY;
    $issuedAt = time();
    $expirationTime = $issuedAt + (86400); // 1 วัน

    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'data' => [
            'user_id' => $profileData['userId'],
            'display_name' => $profileData['displayName'],
            'email' => $email,
            'picture_url' => $profileData['pictureUrl'] ?? '',
            'status_message' => $profileData['statusMessage'] ?? '',
            'is_admin' => $isAdmin,
            'is_active' => $isActive
        ]
    ];

    return \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
}

/**
 * Generate JWT token for user authentication
 * This function is used by user-management.php
 */
function generate_jwt_token($userData, $expiration_days = 30) {
    try {
        if (!defined('SECRET_KEY')) {
            throw new Exception("SECRET_KEY not defined");
        }
        
        $key = SECRET_KEY;
        $issuedAt = time();
        $expirationTime = $issuedAt + ($expiration_days * 86400); // Convert days to seconds
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => $userData
        ];
        
        return JWT::encode($payload, $key, 'HS256');
    } catch (Exception $e) {
        error_log("JWT generation error: " . $e->getMessage());
        throw $e;
    }
}
