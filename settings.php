<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}


require_once '../webhook/config/config.php';
$host = DB_HOST;
$password = DB_PASS;
$username = DB_USER; // Correct variable for username
$dbname = DB_NAME;   // Correct variable for database name

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Retrieve user data from the database
$userId = $_SESSION['line_user']['userId'] ?? null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt_token = $pdo->prepare("SELECT * FROM registration_tokens WHERE user_id = :user_id");
    $stmt_token->execute(['user_id' => $userId]);
    $registration_tokens = $stmt_token->fetch(PDO::FETCH_ASSOC);

}

if (!$user) {
    header('Location: profile.php');
    exit;
}


$Admin_menu = '';

if ($_SESSION['isAdmin'] == 1) {
    $Admin_menu = ' <li class="nav-item">
    <a href="admin.php" class="nav-link ">
        <i class="nav-icon fas fa-users"></i>
        <p>Manage Users</p>
    </a>
</li>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="profile.php" class="nav-link">Home</a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="profile.php" class="brand-link">
                <span class="brand-text font-weight-light">AB Ai Management</span>
            </a>
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <?php echo $Admin_menu; ?>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Profile</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="settings.php" class="nav-link active">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Settings</h1>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <img class="profile-user-img img-fluid img-circle"
                                    src="<?php echo htmlspecialchars($user['picture_url'] ?? ''); ?>"
                                    alt="User profile picture">
                            </div>

                            <form method="post" action="update_settings.php">
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Name</label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="displayName">Display Name In Line</label>
                                                <input type="text" class="form-control" id="displayName"
                                                    name="displayName"
                                                    value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="statusMessage">App Token</label>
                                                <textarea class="form-control" id="registration_tokens"
                                                    name="registration_tokens" rows="3"
                                                    readonly><?php echo htmlspecialchars($registration_tokens['token'] ?? ''); ?></textarea>

                                                <button type="button" onclick="copyToClipboard()"
                                                    class="btn btn-info btn-block btn-flat"><i
                                                        class="fa fa-clone"></i>Copy to Clipboard</button>

                                            </div>
                                            <div class="form-group">
                                                <label for="computerId">Token Expires</label>
                                                <input type="text" class="form-control" id="expires_at"
                                                    name="expires_at"
                                                    value="<?php echo htmlspecialchars($registration_tokens['expires_at'] ?? ''); ?>"
                                                    readonly>
                                            </div>

                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="notifyBy">Notify By</label>
                                                <input type="text" class="form-control" id="notifyBy" name="notifyBy"
                                                    value="<?php echo htmlspecialchars($user['notify_by'] ?? ''); ?>"
                                                    readonly>
                                            </div>

                                            <div class="form-group">
                                                <label for="telegramTokenId">Telegram Token ID</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="telegramTokenId"
                                                        name="telegramTokenId"
                                                        value="<?php echo htmlspecialchars($user['telegram_token_id'] ?? ''); ?>">
                                                    <div class="input-group-append">
                                                        <div class="input-group-text">
                                                            <input type="checkbox" id="defaultTelegramTokenId"
                                                                onclick="setDefaultTelegramTokenId()">
                                                            <label for="defaultTelegramTokenId"
                                                                class="ml-2">Default</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="telegramChatId">Telegram Chat ID</label>
                                                <input type="text" class="form-control" id="telegramChatId"
                                                    name="telegramChatId"
                                                    value="<?php echo htmlspecialchars($user['telegram_chat_id'] ?? ''); ?>">
                                            </div>


                                            <div class="form-group">
                                                <label for="maxProfile">Max Profile</label>
                                                <input type="number" class="form-control" id="maxProfile"
                                                    name="maxProfile"
                                                    value="<?php echo htmlspecialchars($user['max_profile'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" id="computerId"
                                                        name="computerId"
                                                        value="<?php echo htmlspecialchars($user['Computer_ID'] ?? ''); ?>"
                                                        readonly>
                                                    <span class="input-group-append">
                                                        <button type="button" class="btn btn-info btn-flat"
                                                            id="moveComputerBtn">Move Computer Id!</button>
                                                    </span>
                                                </div>

                                            </div>


                                        </div>
                                    </div>

                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                        <!-- /.card-body -->
                    </div>


            </section>
        </div>
        <!-- /.content-wrapper -->

        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                AB Ai Management System
            </div>
            <strong>&copy; <?php echo date('Y'); ?> AB Ai Management. All rights reserved.</strong>
        </footer>
    </div>
    <!-- ./wrapper -->
    <!-- Add this modal structure at the end of the body -->
    <div class="modal fade" id="defaultTelegramModal" tabindex="-1" role="dialog"
        aria-labelledby="defaultTelegramModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="defaultTelegramModalLabel">Set Default Telegram Token</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <p>หากต้องการตั้งค่า Telegram Token เริ่มต้น ให้ไปที่ลิงก์ต่อไปนี้:</p>
                    <a href="https://t.me/BBoxs_bot" target="_blank">https://t.me/BBoxs_bot</a>
                    <div class=" text-center" id="qrcode" class="mt-3"></div> <!-- QR Code will be generated here -->
                    <div class="text-center mt-4">
                        <p>เพิ่มเพื่อนแล้วส่งข้อความนี้ <span>
                                <h3 id="randomCode" class="font-weight-bold"> </h3>
                            </span></p>
                        <p>ภายใน <span id="countdown">30</span> seconds...</p>
                    </div>
                </div>
                <div class="modal-footer">
                       <button type="button" class="btn btn-info btn-block btn-flat" onclick="showManualHelp();" data-dismiss="modal">Get ID</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- Include jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Then include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Include qrcode.js -->

    <script>
        function copyToClipboard() {
            const tokenField = document.getElementById('registration_tokens');
            tokenField.select();
            tokenField.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand('copy');
            alert('Token copied to clipboard!');
        }
    </script>

    <script>
        let countdown = 60;
        let countdownInterval = null; // เก็บ interval เอาไว้เพื่อหยุดภายหลัง
        const countdownEl = document.getElementById('countdown');
        const randomCodeEl = document.getElementById('randomCode');

        function generateRandomCode() {
            const randomNum = Math.floor(100000 + Math.random() * 900000); // สุ่ม 6 หลัก
            randomCodeEl.textContent = randomNum;
        }

        function startCountdown() {
            countdown = 60
            generateRandomCode(); // สร้างครั้งแรก 
            countdownInterval = setInterval(() => {
                countdown--;
                countdownEl.textContent = countdown;

                if (countdown <= 0) {
                    let code = randomCodeEl.textContent;
                    postCodeToCheck(code)
                }
            }, 1000);
        }
  function showManualHelp() {
          let code = randomCodeEl.textContent;
                    postCodeToCheck(code)
    }
        function stopCountdown() {
            clearInterval(countdownInterval);
            countdownInterval = null;
            countdownEl.textContent = '⏹';
            randomCodeEl.textContent = '------';
        }

        function makeCode() {
            const qrCodeContainer = document.getElementById('qrcode');
            qrCodeContainer.innerHTML = ''; // เคลียร์ก่อน
            const qrcode = new QRCode(qrCodeContainer);
            qrcode.makeCode('https://t.me/BBoxs_bot');
        }

        function setDefaultTelegramTokenId() {
            const checkbox = document.getElementById('defaultTelegramTokenId');
            const telegramTokenIdField = document.getElementById('telegramTokenId');
            if (checkbox.checked) {
                makeCode();
                $('#defaultTelegramModal').modal('show');
                telegramTokenIdField.value = '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U';
            } else {
                telegramTokenIdField.value = '';
                $('#defaultTelegramModal').modal('hide');
            }
        }

        // 📌 เริ่มเมื่อ modal ถูกแสดง
        $('#defaultTelegramModal').on('shown.bs.modal', function () {
            startCountdown();
        });

        // 📌 หยุดเมื่อ modal ถูกปิด
        $('#defaultTelegramModal').on('hidden.bs.modal', function () {
            stopCountdown();
        });

        function postCodeToCheck(code) {
            fetch('callTelegram.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.match) {
                        document.getElementById('telegramChatId').value = data.chat_id;
                        console.log('✅ Code matched! Chat ID:', data.chat_id);
                    } else {
                        console.log('❌ No match found.');
                    }

                    // Close the modal after checking the code
                    $('#defaultTelegramModal').modal('hide');
                })
                .catch(err => console.error('Error:', err));
        }


    </script>
    <script>
        document.getElementById('moveComputerBtn').addEventListener('click', function () {
            const computerId = document.getElementById('computerId').value;

            if (!computerId) {
                alert("Computer ID is empty.");
                return;
            }

            fetch('move_computer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ computerId: computerId })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("✅ Move successful: " + data.message);
                    } else {
                        alert("❌ Move failed: " + data.message);
                    }
                    window.location.reload(); // Refresh the page to see changes
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("❌ Something went wrong.");
                });
        });
    </script>


</body>

</html>