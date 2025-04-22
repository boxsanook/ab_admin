<?php
session_start();

// Clear the login_token cookie
setcookie('login_token', '', time() - 3600, "/", "", false, true);

// Destroy the session
session_unset();
session_destroy();

// Redirect to the login page
header('Location: login.php');
exit;
