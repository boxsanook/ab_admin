<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

// Retrieve user data from the session
$user = $_SESSION['line_user'] ?? null;

if (!$user) {
    echo "User data not found.";
    exit;
}

// Connect to the database
require_once 'db_connection.php'; // Ensure this file contains your database connection logic

// Fetch affiliate data for the user
$affiliateData = null;
if (!empty($user['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT a.name, a.code, a.description 
        FROM affiliates a 
        WHERE a.user_id = ?
    ");
    $stmt->execute([$user['user_id']]);
    $affiliateData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Information</title>
</head>

<body>
    <h1>Affiliate Information</h1>
    <?php if ($affiliateData): ?>
        <p><strong>Affiliate Name:</strong> <?php echo htmlspecialchars($affiliateData['name']); ?></p>
        <p><strong>Affiliate Code:</strong> <?php echo htmlspecialchars($affiliateData['code']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($affiliateData['description']); ?></p>
    <?php else: ?>
        <p>No affiliate information available.</p>
    <?php endif; ?>
    <a href="profile.php">Back to Profile</a>
</body>

</html>
