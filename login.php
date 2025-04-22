<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '../webhook/config/config.php';
require_once 'get_jwt.php';
require_once '../webhook/vendor/autoload.php';
use \Firebase\JWT\JWT;

$channelId = Line_Login_client_id;
$redirectUri = Line_RedirectUri_callback;

if (isset($_COOKIE['login_token'])) {
    try {
        $key = SECRET_KEY;
        $decoded = JWT::decode($_COOKIE['login_token'], new \Firebase\JWT\Key($key, 'HS256'));

        $userData = (array)$decoded->data;
        $_SESSION['line_user'] = [
            'userId' => $userData['user_id'],
            'displayName' => $userData['display_name'],
            'pictureUrl' => $userData['picture_url'] ?? '',
            'statusMessage' => $userData['status_message'] ?? '',
        ];
        $_SESSION['authenticated'] = true;
        $_SESSION['isAdmin'] = $userData['is_admin'];
        $_SESSION['userActive'] = $userData['is_active'];
        $_SESSION['data_admin'] = $userData;

        if ($_SESSION['userActive'] == 0) {
            throw new Exception('User is not active.');
        }

        if ($_SESSION['isAdmin'] == 1) {
            header('Location: admin.php');
        } else {
            header('Location: profile.php');
        }
        exit;
    } catch (Exception $e) {
        error_log('JWT validation failed: ' . $e->getMessage());
        header('Location: logout.php');
        exit;
    }
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_state_expires'] = time() + 600;

$authUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $channelId,
    'redirect_uri' => $redirectUri,
    'state' => $state,
    'scope' => 'profile openid email',
    'nonce' => bin2hex(random_bytes(16)),
]);

if (!isset($_GET['code'])) {
    // Generate a random state for security
    $state = bin2hex(random_bytes(16)); 
    
    // Store state in session with expiration time (10 minutes)
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_state_expires'] = time() + 600;
    
    // Create authorization URL with proper scopes
    $authUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => $channelId,
        'redirect_uri' => $redirectUri,
        'state' => $state,
        'scope' => 'profile openid email',
        'nonce' => bin2hex(random_bytes(16)),
    ]);
    
    // Display login page with AdminLTE UI
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - AB Ai Management</title>
        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <!-- Theme style -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
        <style>
            .login-page {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .login-box {
                margin-top: 10vh;
            }
            .login-logo a {
                color: white;
            }
            .line-button {
                background-color: #00B900;
                border-color: #00B900;
                color: white;
            }
            .line-button:hover {
                background-color: #009900;
                border-color: #009900;
                color: white;
            }
            .card {
                border-radius: 15px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            .login-card-body {
                border-radius: 15px;
                padding: 30px;
            }
        </style>
    </head>
    <body class="hold-transition login-page">
        <div class="login-box">
            <div class="login-logo">
                <a href="#"><b>AB</b> Ai Management</a>
            </div>
            <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">Sign in with your LINE account</p>
                    <div class="row">
                        <div class="col-12">
                            <a href="<?php echo htmlspecialchars($authUrl); ?>" class="btn btn-block line-button">
                                <i class="fab fa-line mr-2"></i> Login with LINE
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Bootstrap 4 -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- AdminLTE App -->
        <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}
?>