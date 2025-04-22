<?php
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is an admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Function to check if user is an affiliate
function isAffiliate() {
    return isset($_SESSION['is_affiliate']) && $_SESSION['is_affiliate'] == 1;
}

// Check if user is logged in, if not redirect to login page
if (!isLoggedIn()) {
    // Store the current URL in session to redirect back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}
if (!isAdmin()) {
    // Store the current URL in session to redirect back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: profile.php");
    exit();
}


// // Get user data from session
// $user_id = $_SESSION['user_id'];
// $username = $_SESSION['username'] ?? '';
// $email = $_SESSION['email'] ?? '';
// $is_admin = $_SESSION['is_admin'] ?? 0;
// $is_affiliate = $_SESSION['is_affiliate'] ?? 0;

// // Function to get user's affiliate code if they are an affiliate
// function getUserAffiliateCode($user_id) {
//     global $conn;
//     $stmt = $conn->prepare("SELECT affiliate_code FROM users WHERE user_id = ?");
//     $stmt->bind_param("s", $user_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     if ($row = $result->fetch_assoc()) {
//         return $row['affiliate_code'];
//     }
//     return null;
// }

// // Set affiliate code in session if user is an affiliate
// if ($is_affiliate && !isset($_SESSION['affiliate_code'])) {
//     $_SESSION['affiliate_code'] = getUserAffiliateCode($user_id);
// }
?> 