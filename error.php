<?php
session_start();

// Check if an error message is set in the session
$errorMessage = $_SESSION['error_message'] ?? 'An unknown error occurred.';
unset($_SESSION['error_message']); // Clear the error message after displaying it
?>
<!DOCTYPE html>
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
        <p><?= htmlspecialchars($errorMessage) ?></p>
        <button onclick="window.location.href='login.php'">Go to Login</button>
    </div>
</body>
</html>
