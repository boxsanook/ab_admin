<?php
// Define the required directories
$directories = [
    'assets',
    'assets/images',
    'config',
    'includes',
    'uploads',
    'uploads/profile',
    'uploads/temp'
];

// Create directories if they don't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Create .htaccess to protect config directory
$htaccess_content = "Order deny,allow\nDeny from all";
file_put_contents('config/.htaccess', $htaccess_content);

// Create default avatar if it doesn't exist
$default_avatar = 'assets/images/default-avatar.png';
if (!file_exists($default_avatar)) {
    // You would typically copy a real default avatar here
    // For now, we'll just create a placeholder
    copy('https://via.placeholder.com/150', $default_avatar);
}

// Create logo if it doesn't exist
$logo = 'assets/images/logo.png';
if (!file_exists($logo)) {
    // You would typically copy a real logo here
    // For now, we'll just create a placeholder
    copy('https://via.placeholder.com/150', $logo);
}

echo "Initialization completed successfully!\n";
echo "Please make sure to:\n";
echo "1. Update config/config.php with your database credentials\n";
echo "2. Replace default-avatar.png and logo.png with your actual images\n";
echo "3. Set appropriate permissions on the directories\n";
?> 