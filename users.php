    <?php
    session_start();
    require_once '../webhook/config/config.php';

    // Check if the required session variables are set
    if (!isset($_SESSION['line_user'], $_SESSION['authenticated'], $_SESSION['isAdmin'], $_SESSION['userActive'])) {
        // Redirect to login if session variables are missing
        header('Location: login.php');
        exit;
    }


    // Database connection
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Handle AJAX requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        switch ($_POST['action']) {
            case 'toggle_affiliate':
                $user_id = $_POST['user_id'];
                $is_affiliate = $_POST['is_affiliate'];
                try {
                    if ($is_affiliate == '1') {
                        // Generate unique affiliate code
                        do {
                            $code = strtoupper(substr(md5(uniqid()), 0, 8));
                            $exists = $pdo->query("SELECT COUNT(*) FROM users WHERE affiliate_code = '$code'")->fetchColumn();
                        } while ($exists > 0);
                        
                        // Get default commission rate
                        $default_rate = $pdo->query("SELECT setting_value FROM affiliate_settings WHERE setting_key = 'default_commission_rate'")->fetchColumn();
                        
                        // Create affiliate record
                        $stmt = $pdo->prepare("INSERT INTO affiliates (user_id, code, commission_rate) VALUES (?, ?, ?)");
                        $stmt->execute([$user_id, $code, $default_rate]);
                        
                        // Update user
                        $stmt = $pdo->prepare("UPDATE users SET affiliate = '1', affiliate_code = ? WHERE user_id = ?");
                        $stmt->execute([$code, $user_id]);
                    } else {
                        // Deactivate affiliate
                        $stmt = $pdo->prepare("UPDATE users SET affiliate = '0' WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Set affiliate record to inactive
                        $stmt = $pdo->prepare("UPDATE affiliates SET status = 'inactive' WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                    }
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;
                
            case 'update_user':
                $user_id = $_POST['user_id'];
                $active = $_POST['active'];
                try {
                    $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE user_id = ?");
                    $stmt->execute([$active, $user_id]);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            case 'get_user_details':
                $user_id = $_POST['user_id'];
                try {
                    // Get user details with additional information
                    $stmt = $pdo->prepare("
                        SELECT u.*, 
                            COALESCE(a.total_referrals, 0) as total_referrals,
                            COALESCE(a.total_earnings, 0) as total_earnings,
                            a.commission_rate,
                            a.status as affiliate_status,
                            (SELECT COUNT(*) FROM token_purchases WHERE user_id = u.user_id) as total_purchases,
                            (SELECT SUM(amount) FROM token_purchases WHERE user_id = u.user_id AND payment_status = 'completed') as total_spent,
                            (SELECT SUM(tokens) FROM token_purchases WHERE user_id = u.user_id AND payment_status = 'completed') as total_tokens,
                            (SELECT created_at FROM token_purchases WHERE user_id = u.user_id ORDER BY created_at DESC LIMIT 1) as last_purchase
                        FROM users u
                        LEFT JOIN affiliates a ON u.user_id = a.user_id
                        WHERE u.user_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Get purchase statistics by payment method
                    $stmt = $pdo->prepare("
                        SELECT 
                            payment_method,
                            COUNT(*) as total_transactions,
                            SUM(amount) as total_amount,
                            SUM(tokens) as total_tokens
                        FROM token_purchases
                        WHERE user_id = ? AND payment_status = 'completed'
                        GROUP BY payment_method
                    ");
                    $stmt->execute([$user_id]);
                    $payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Get recent token purchases with buyer and affiliate information
                    $stmt = $pdo->prepare("
                        SELECT 
                            tp.id,
                            tp.amount,
                            tp.tokens,
                            tp.payment_method,
                            tp.payment_status,
                            tp.created_at,
                            tp.referral_code,
                            tp.commission_paid,
                            u.display_name AS buyer_name,
                            u.email AS buyer_email,
                            a.display_name AS affiliate_name,
                            a.email AS affiliate_email
                        FROM token_purchases tp
                        LEFT JOIN users u ON tp.user_id = u.user_id
                        LEFT JOIN users a ON tp.referral_code = a.affiliate_code
                        WHERE tp.user_id = ?
                        ORDER BY tp.created_at DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$user_id]);
                    $recent_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Get affiliate transactions if user is an affiliate
                    $affiliate_transactions = [];
                    if ($user['affiliate'] == '1') {
                        $stmt = $pdo->prepare("
                            SELECT at.*, u.display_name as referred_user
                            FROM affiliate_transactions at
                            LEFT JOIN users u ON at.referral_id = u.user_id
                            WHERE at.affiliate_id = ?
                            ORDER BY at.created_at DESC
                            LIMIT 5
                        ");
                        $stmt->execute([$user_id]);
                        $affiliate_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    echo json_encode([
                        'success' => true,
                        'user' => $user,
                        'payment_stats' => $payment_stats,
                        'recent_purchases' => $recent_purchases,
                        'affiliate_transactions' => $affiliate_transactions
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            case 'update_user_details':
                $user_id = $_POST['user_id'];
                $display_name = trim($_POST['display_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $commission_rate = !empty($_POST['commission_rate']) ? floatval($_POST['commission_rate']) : null;

                try {
                    // Start transaction
                    $pdo->beginTransaction();

                    // Update user details
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET display_name = ?,
                            email = ?,
                            phone = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$display_name, $email, $phone, $user_id]);

                    // Update commission rate if user is an affiliate
                    if ($commission_rate !== null) {
                        $stmt = $pdo->prepare("
                            UPDATE affiliates 
                            SET commission_rate = ?
                            WHERE user_id = ? AND status = 'active'
                        ");
                        $stmt->execute([$commission_rate, $user_id]);
                    }

                    // Commit transaction
                    $pdo->commit();

                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollBack();
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            case 'get_user_purchases':
                $user_id = $_POST['user_id'];
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            tp.*,
                            u.display_name as affiliate_name
                        FROM token_purchases tp
                        LEFT JOIN users u ON tp.referral_code = u.affiliate_code
                        WHERE tp.user_id = ?
                        ORDER BY tp.created_at DESC
                    ");
                    $stmt->execute([$user_id]);
                    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'purchases' => $purchases
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            case 'get_token_packages':
                try {
                    $stmt = $pdo->prepare("
                        SELECT *
                        FROM token_packages
                        WHERE status = 'active'
                        ORDER BY tokens ASC
                    ");
                    $stmt->execute();
                    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'packages' => $packages
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            case 'create_token_purchase':
                $user_id = $_POST['user_id'];
                $package_id = $_POST['package_id'];
                $payment_method = $_POST['payment_method'];
                $referral_code = !empty($_POST['referral_code']) ? trim($_POST['referral_code']) : null;

                try {
                    // Start transaction
                    $pdo->beginTransaction();

                    // Get package details
                    $stmt = $pdo->prepare("SELECT * FROM token_packages WHERE id = ?");
                    $stmt->execute([$package_id]);
                    $package = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$package) {
                        throw new Exception("Invalid package selected");
                    }

                    // Validate referral code if provided
                    if ($referral_code) {
                        $stmt = $pdo->prepare("
                            SELECT user_id 
                            FROM users 
                            WHERE affiliate_code = ? AND affiliate = '1'
                        ");
                        $stmt->execute([$referral_code]);
                        $affiliate = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$affiliate) {
                            throw new Exception("Invalid referral code");
                        }
                    }

                    // Create purchase record
                    $stmt = $pdo->prepare("
                        INSERT INTO token_purchases (
                            user_id, package_id, amount, tokens, 
                            payment_method, payment_status, referral_code
                        ) VALUES (?, ?, ?, ?, ?, 'pending', ?)
                    ");
                    $stmt->execute([
                        $user_id,
                        $package_id,
                        $package['price'],
                        $package['tokens'],
                        $payment_method,
                        $referral_code
                    ]);

                    $purchase_id = $pdo->lastInsertId();

                    // Commit transaction
                    $pdo->commit();

                    echo json_encode([
                        'success' => true,
                        'purchase_id' => $purchase_id,
                        'message' => 'Purchase created successfully'
                    ]);
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollBack();
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;
                
            case 'confirm_purchase':
                $purchase_id = $_POST['purchase_id'];
                
                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Get purchase details
                    $stmt = $pdo->prepare("
                        SELECT tp.*, u.user_id, tp.referral_code 
                        FROM token_purchases tp
                        JOIN users u ON tp.user_id = u.user_id
                        WHERE tp.id = ?
                    ");
                    $stmt->execute([$purchase_id]);
                    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$purchase) {
                        throw new Exception("Purchase not found");
                    }
                    
                    // Update purchase status to completed
                    $stmt = $pdo->prepare("
                        UPDATE token_purchases 
                        SET payment_status = 'completed', 
                            updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$purchase_id]);
                    
                    // Add tokens to user's balance
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET balance = balance + ? 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$purchase['tokens'], $purchase['user_id']]);
                    
                    // Process affiliate commission if applicable
                    if (!empty($purchase['referral_code']) && $purchase['commission_paid'] != '1') {
                        // Get affiliate user_id and commission rate
                        $stmt = $pdo->prepare("
                            SELECT u.user_id, a.commission_rate 
                            FROM users u
                            JOIN affiliates a ON u.user_id = a.user_id
                            WHERE u.affiliate_code = ? AND u.affiliate = '1' AND a.status = 'active'
                        ");
                        $stmt->execute([$purchase['referral_code']]);
                        $affiliate = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($affiliate) {
                            // Calculate commission amount
                            $commission_amount = $purchase['amount'] * ($affiliate['commission_rate'] / 100);
                            
                            // Create affiliate transaction
                            $stmt = $pdo->prepare("
                                INSERT INTO affiliate_transactions (
                                    affiliate_id, referral_id, purchase_id, amount, status
                                ) VALUES (?, ?, ?, ?, 'completed')
                            ");
                            $stmt->execute([
                                $affiliate['user_id'],
                                $purchase['user_id'],
                                $purchase_id,
                                $commission_amount
                            ]);
                            
                            // Mark commission as paid in purchase record
                            $stmt = $pdo->prepare("
                                UPDATE token_purchases 
                                SET commission_paid = '1' 
                                WHERE id = ?
                            ");
                            $stmt->execute([$purchase_id]);
                            
                            // Update affiliate total earnings
                            $stmt = $pdo->prepare("
                                UPDATE affiliates 
                                SET total_earnings = total_earnings + ?, 
                                    total_referrals = total_referrals + 1
                                WHERE user_id = ?
                            ");
                            $stmt->execute([$commission_amount, $affiliate['user_id']]);
                        }
                    }
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Purchase confirmed successfully'
                    ]);
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollBack();
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            case 'cancel_purchase':
                $purchase_id = $_POST['purchase_id'];
                
                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Get purchase details
                    $stmt = $pdo->prepare("
                        SELECT * FROM token_purchases WHERE id = ?
                    ");
                    $stmt->execute([$purchase_id]);
                    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$purchase) {
                        throw new Exception("Purchase not found");
                    }
                    
                    // Check if purchase is already completed
                    if ($purchase['payment_status'] === 'completed') {
                        throw new Exception("Cannot cancel a completed purchase");
                    }
                    
                    // Update purchase status to canceled
                    $stmt = $pdo->prepare("
                        UPDATE token_purchases 
                        SET payment_status = 'canceled', 
                            updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$purchase_id]);
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Purchase canceled successfully'
                    ]);
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollBack();
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;

            case 'view_purchase_history':
                $user_id = $_POST['user_id'];
                $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
                $date_from = isset($_POST['date_from']) ? $_POST['date_from'] : null;
                $date_to = isset($_POST['date_to']) ? $_POST['date_to'] : null;
                
                try {
                    // Base query
                    $query = "
                        SELECT 
                            tp.*,
                            tp.amount as total_amount,
                            tp.tokens as total_tokens,
                            u.display_name as buyer_name,
                            u.email as buyer_email,
                            pkg.name as package_name,
                            a.display_name as affiliate_name,
                            a.email as affiliate_email
                        FROM token_purchases tp
                        LEFT JOIN users u ON tp.user_id = u.user_id
                        LEFT JOIN token_packages pkg ON tp.package_id = pkg.id
                        LEFT JOIN users a ON tp.referral_code = a.affiliate_code
                        WHERE tp.user_id = ?
                    ";
                    
                    $params = [$user_id];
                    
                    // Apply status filter
                    if ($filter !== 'all') {
                        $query .= " AND tp.payment_status = ?";
                        $params[] = $filter;
                    }
                    
                    // Apply date filters
                    if ($date_from) {
                        $query .= " AND tp.created_at >= ?";
                        $params[] = $date_from . ' 00:00:00';
                    }
                    
                    if ($date_to) {
                        $query .= " AND tp.created_at <= ?";
                        $params[] = $date_to . ' 23:59:59';
                    }
                    
                    // Order by date
                    $query .= " ORDER BY tp.created_at DESC";
                    
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Get summary statistics
                    $summary_query = "
                        SELECT 
                            COUNT(*) as total_purchases,
                            SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_purchases,
                            SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_purchases,
                            SUM(CASE WHEN payment_status = 'canceled' THEN 1 ELSE 0 END) as canceled_purchases,
                            SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as total_spent,
                            SUM(CASE WHEN payment_status = 'completed' THEN tokens ELSE 0 END) as total_tokens
                        FROM token_purchases
                        WHERE user_id = ?
                    ";
                    
                    $params = [$user_id];
                    
                    // Apply date filters to summary as well
                    if ($date_from) {
                        $summary_query .= " AND created_at >= ?";
                        $params[] = $date_from . ' 00:00:00';
                    }
                    
                    if ($date_to) {
                        $summary_query .= " AND created_at <= ?";
                        $params[] = $date_to . ' 23:59:59';
                    }
                    
                    $stmt = $pdo->prepare($summary_query);
                    $stmt->execute($params);
                    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get payment method breakdown
                    $payment_query = "
                        SELECT 
                            payment_method,
                            COUNT(*) as total_transactions,
                            SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as total_amount,
                            SUM(CASE WHEN payment_status = 'completed' THEN tokens ELSE 0 END) as total_tokens
                        FROM token_purchases
                        WHERE user_id = ? AND payment_status = 'completed'
                    ";
                    
                    $params = [$user_id];
                    
                    // Apply date filters to payment methods as well
                    if ($date_from) {
                        $payment_query .= " AND created_at >= ?";
                        $params[] = $date_from . ' 00:00:00';
                    }
                    
                    if ($date_to) {
                        $payment_query .= " AND created_at <= ?";
                        $params[] = $date_to . ' 23:59:59';
                    }
                    
                    $payment_query .= " GROUP BY payment_method";
                    
                    $stmt = $pdo->prepare($payment_query);
                    $stmt->execute($params);
                    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Get user details
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'purchases' => $purchases,
                        'summary' => $summary,
                        'payment_methods' => $payment_methods,
                        'user' => $user
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
                exit;
        }
    }

    // Fetch all users with their affiliate status
    $users = $pdo->query("
        SELECT u.*, 
            COALESCE(a.total_referrals, 0) as total_referrals,
            COALESCE(a.total_earnings, 0) as total_earnings,
            a.status as affiliate_status,
            (SELECT COUNT(*) FROM token_purchases WHERE user_id = u.user_id) as total_purchases
        FROM users u
        LEFT JOIN affiliates a ON u.user_id = a.user_id
        ORDER BY u.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Management - AB Ai Management</title>
        
        <!-- AdminLTE CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <!-- DataTables -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
        <!-- SweetAlert2 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <style>
            .user-details-modal .modal-body {
                max-height: 70vh;
                overflow-y: auto;
            }
            .status-badge {
                min-width: 80px;
                text-align: center;
            }
            .affiliate-stats {
                background: #f8f9fa;
                border-radius: 5px;
                padding: 15px;
                margin-bottom: 15px;
            }
            .purchase-history {
                margin-top: 20px;
            }
            .transaction-item {
                border-left: 3px solid #007bff;
                margin-bottom: 10px;
                padding: 10px;
                background: #f8f9fa;
            }
            /* Fix for DataTable sorting */
            table.dataTable thead th {
                position: relative;
                z-index: 1 !important;
                cursor: pointer !important;
            }
            table.dataTable thead .sorting:after,
            table.dataTable thead .sorting_asc:after,
            table.dataTable thead .sorting_desc:after {
                position: absolute;
                z-index: 2 !important;
                pointer-events: none;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                z-index: 1 !important;
            }
            /* Responsive DataTables styles */
            .dtr-details {
                width: 100%;
            }
            .dtr-title {
                font-weight: bold;
                padding-right: 10px;
            }
            table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before, 
            table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
                background-color: #007bff;
            }
            table.dataTable.dtr-inline.collapsed>tbody>tr.parent>td.dtr-control:before, 
            table.dataTable.dtr-inline.collapsed>tbody>tr.parent>th.dtr-control:before {
                background-color: #dc3545;
            }
            /* Improve table display on small screens */
            @media (max-width: 767px) {
                .table-responsive {
                    border: 0;
                }
                .dataTables_wrapper .dataTables_length,
                .dataTables_wrapper .dataTables_filter {
                    text-align: left;
                    margin-bottom: 10px;
                }
                .dataTables_wrapper .dataTables_filter {
                    margin-top: 5px;
                }
            }
            
            /* Ensure action buttons are always visible */
            .actions-column {
                white-space: nowrap;
                min-width: 120px;
            }
            
            /* Make action buttons accessible in responsive mode */
            table.dataTable.dtr-inline.collapsed>tbody>tr>td.actions-column,
            table.dataTable.dtr-inline.collapsed>tbody>tr>td:last-child {
                display: table-cell !important;
            }
            
            /* Keep action buttons visible in child rows */
            .child .actions-column {
                display: flex !important;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            /* Make sure actions are clickable in responsive view */
            .dtr-data .btn {
                margin: 2px;
            }
            
            /* Fix for responsive tables */
            .table-responsive .dataTables_wrapper .row {
                margin: 0;
            }
            
            /* Make buttons easier to tap on mobile */
            @media (max-width: 767px) {
                .actions-column .btn {
                    padding: 0.375rem 0.5rem;
                    margin-bottom: 0.25rem;
                }
                
                /* Increase spacing between buttons in responsive view */
                .dtr-data .btn {
                    margin: 3px;
                    min-width: 38px;
                    height: 35px;
                }
                
                /* Ensure the table responsive wrapper has enough height */
                .table-responsive {
                    min-height: 300px;
                }
            }
        </style>
    </head>
    <body class="hold-transition sidebar-mini">
        <div class="wrapper">
            <!-- Navbar & Sidebar -->
            <?php include 'includes/navbar.php'; ?>
            <?php include 'includes/sidebar.php'; ?>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <!-- Content Header -->
                <section class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1>User Management</h1>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="container-fluid">
                        <div class="card">
                            <div class="card-body">
                                <table id="usersTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Contact</th>
                                            <th>Affiliate Status</th>
                                            <th>Referrals</th>
                                            <th>Earnings</th>
                                            <th>Purchases</th>
                                            <th>Status</th>
                                            <th class="actions-column">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($user['picture_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($user['picture_url']); ?>" 
                                                            class="img-circle mr-2" alt="" style="width: 40px; height: 40px;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <div><?php echo htmlspecialchars($user['display_name']); ?></div>
                                                        <small class="text-muted">ID: <?php echo htmlspecialchars($user['user_id']); ?></small>
                                                        <?php if ($user['affiliate'] == '1'): ?>
                                                            <br>
                                                            <small class="text-muted">Code: <?php echo htmlspecialchars($user['affiliate_code']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($user['email']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['phone'] ?? 'No phone'); ?></small>
                                            </td>
                                            <td>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input affiliate-toggle" 
                                                        id="affiliate<?php echo $user['user_id']; ?>"
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        <?php echo $user['affiliate'] == '1' ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="affiliate<?php echo $user['user_id']; ?>">
                                                        <?php if ($user['affiliate'] == '1'): ?>
                                                            <span class="badge badge-<?php 
                                                                echo $user['affiliate_status'] == 'active' ? 'success' : 
                                                                    ($user['affiliate_status'] == 'inactive' ? 'warning' : 'danger'); 
                                                            ?> status-badge">
                                                                <?php echo ucfirst($user['affiliate_status']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary status-badge">Not Affiliate</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td><?php echo $user['total_referrals']; ?></td>
                                            <td>฿<?php echo number_format($user['total_earnings'], 2); ?></td>
                                            <td><?php echo $user['total_purchases']; ?></td>
                                            <td>
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input user-status" 
                                                        id="status<?php echo $user['user_id']; ?>"
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        <?php echo $user['active'] == '1' ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="status<?php echo $user['user_id']; ?>">
                                                        <span class="badge badge-<?php echo $user['active'] == '1' ? 'success' : 'danger'; ?> status-badge">
                                                            <?php echo $user['active'] == '1' ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info view-user" 
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        title="View Details" aria-label="View user details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($user['affiliate'] == '1'): ?>
                                                    <button type="button" class="btn btn-sm btn-success view-affiliate" 
                                                            data-user-id="<?php echo $user['user_id']; ?>"
                                                            title="View Affiliate Stats" aria-label="View affiliate statistics">
                                                        <i class="fas fa-chart-line"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-warning generate-token"
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        title="Generate Token" aria-label="Generate token">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary view-purchase-history"
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        title="View Purchase History" aria-label="View purchase history">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Footer -->
            <?php include 'includes/footer.php'; ?>
        </div>

        <!-- User Details Modal -->
        <div class="modal fade user-details-modal" id="userDetailsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">User Details</h5>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm mr-2 edit-user-btn">
                                <i class="fas fa-edit"></i> Edit User
                            </button>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div id="loading-spinner" class="text-center d-none">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                        </div>
                        <div id="user-details-content">
                            <div class="text-center mb-3">
                                <img src="" alt="" class="user-avatar img-circle" style="width: 100px; height: 100px;">
                                <h4 class="user-name mt-2 mb-0"></h4>
                                <p class="user-email text-muted"></p>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info"><i class="fas fa-shopping-cart"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Purchases</span>
                                            <span class="info-box-number total-purchases">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Spent</span>
                                            <span class="info-box-number total-spent">฿0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Purchase Statistics -->
                            <div class="purchase-stats mb-4">
                                <h5><i class="fas fa-chart-bar"></i> Purchase Statistics</h5>
                                <div class="card">
                                    <div class="card-body p-0">
                                        <div class="row">
                                            <div class="col-md-4 text-center border-right">
                                                <div class="p-3">
                                                    <h3 class="total-purchases mb-0">0</h3>
                                                    <small class="text-muted">Total Transactions</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-center border-right">
                                                <div class="p-3">
                                                    <h3 class="total-tokens mb-0">0</h3>
                                                    <small class="text-muted">Total Tokens</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <div class="p-3">
                                                    <h3 class="total-spent mb-0">฿0.00</h3>
                                                    <small class="text-muted">Total Amount</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method Breakdown -->
                                <div class="payment-methods mt-3">
                                    <h6 class="text-muted mb-3">Payment Method Breakdown</h6>
                                    <div class="payment-stats-list">
                                        <!-- Payment stats will be populated here -->
                                    </div>
                                </div>
                            </div>

                            <!-- User Purchases Table -->
                            <div class="user-purchases mb-4">
                                <h5>
                                    <i class="fas fa-list"></i> Purchase History
                                    <div class="float-right">
                                        <button type="button" class="btn btn-sm btn-success" id="buyTokens">
                                            <i class="fas fa-plus"></i> Buy Tokens
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="refreshPurchases">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </h5>
                                <div class="table-responsive">
                                    <table id="purchasesTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Tokens</th>
                                                <th>Payment Method</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Purchases will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Buy Tokens Modal -->
                            <div class="modal fade" id="buyTokensModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Buy Tokens</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div id="packagesList" class="row">
                                                <!-- Packages will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Purchase Confirmation Modal -->
                            <div class="modal fade" id="confirmPurchaseModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirm Purchase</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form id="purchaseForm">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="create_token_purchase">
                                                <input type="hidden" name="user_id" id="purchase_user_id">
                                                <input type="hidden" name="package_id" id="purchase_package_id">
                                                
                                                <div class="package-details mb-4">
                                                    <h6>Package Details</h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p><strong>Name:</strong> <span class="package-name"></span></p>
                                                            <p><strong>Tokens:</strong> <span class="package-tokens"></span></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Price:</strong> <span class="package-price"></span></p>
                                                            <p><strong>Validity:</strong> <span class="package-validity"></span></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="payment_method">Payment Method</label>
                                                    <select class="form-control" id="payment_method" name="payment_method" required>
                                                        <option value="">Select Payment Method</option>
                                                        <option value="bank_transfer">Bank Transfer</option>
                                                        <option value="credit_card">Credit Card</option>
                                                        <option value="promptpay">PromptPay</option>
                                                    </select>
                                                </div>

                                                <div class="referral-section">
                                                    <div class="form-group">
                                                        <label for="referral_code">Referral Code (Optional)</label>
                                                        <input type="text" class="form-control" id="referral_code" name="referral_code" placeholder="Enter referral code">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Confirm Purchase</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="affiliate-stats d-none">
                                <h5><i class="fas fa-user-tag"></i> Affiliate Information</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p><strong>Code:</strong> <span class="affiliate-code"></span></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Commission Rate:</strong> <span class="commission-rate"></span>%</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Total Earnings:</strong> ฿<span class="total-earnings"></span></p>
                                    </div>
                                </div>
                            </div>

                            <div class="purchase-history">
                                <h5><i class="fas fa-history"></i> Recent Purchases</h5>
                                <div class="recent-purchases-list">
                                    <!-- Recent purchases will be populated here -->
                                </div>
                            </div>

                            <div class="affiliate-transactions d-none">
                                <h5 class="mt-4"><i class="fas fa-exchange-alt"></i> Recent Affiliate Transactions</h5>
                                <div class="affiliate-transactions-list">
                                    <!-- Affiliate transactions will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="editUserForm">
                        <div class="modal-body">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <input type="hidden" name="action" value="update_user_details">
                            
                            <div class="form-group">
                                <label for="edit_display_name">Display Name</label>
                                <input type="text" class="form-control" id="edit_display_name" name="display_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_phone">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone">
                            </div>

                            <div class="form-group">
                                <label for="edit_commission_rate">Commission Rate (%)</label>
                                <input type="number" class="form-control" id="edit_commission_rate" name="commission_rate" min="0" max="100" step="0.01">
                                <small class="form-text text-muted">Only applies if user is an affiliate</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Purchase History Modal -->
        <div class="modal fade" id="purchaseHistoryModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Purchase History</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="purchase-history-loading" class="text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                            <p class="mt-2">Loading purchase history...</p>
                        </div>
                        
                        <div id="purchase-history-content" class="d-none">
                            <div class="user-info mb-4">
                                <div class="d-flex align-items-center">
                                    <img src="" alt="" class="purchase-history-avatar img-circle mr-3" style="width: 60px; height: 60px;">
                                    <div>
                                        <h4 class="purchase-history-username mb-0"></h4>
                                        <p class="purchase-history-email text-muted mb-0"></p>
                                        <div class="purchase-history-balance"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-9">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Filter Purchases</h3>
                                        </div>
                                        <div class="card-body">
                                            <form id="purchase-filter-form" class="form-inline">
                                                <div class="form-group mr-3 mb-2">
                                                    <label for="status-filter" class="mr-2">Status:</label>
                                                    <select class="form-control" id="status-filter">
                                                        <option value="all">All</option>
                                                        <option value="completed">Completed</option>
                                                        <option value="pending">Pending</option>
                                                        <option value="canceled">Canceled</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group mr-3 mb-2">
                                                    <label for="date-from" class="mr-2">From:</label>
                                                    <input type="date" class="form-control" id="date-from">
                                                </div>
                                                
                                                <div class="form-group mr-3 mb-2">
                                                    <label for="date-to" class="mr-2">To:</label>
                                                    <input type="date" class="form-control" id="date-to">
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary mb-2">Apply Filters</button>
                                                <button type="button" id="reset-filters" class="btn btn-secondary mb-2 ml-2">Reset</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3 id="total-purchase-amount">฿0.00</h3>
                                            <p>Total Spent</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary"><i class="fas fa-shopping-cart"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Purchases</span>
                                            <span class="info-box-number" id="total-purchases">0</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Completed</span>
                                            <span class="info-box-number" id="completed-purchases">0</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Pending</span>
                                            <span class="info-box-number" id="pending-purchases">0</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Canceled</span>
                                            <span class="info-box-number" id="canceled-purchases">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Purchase Transactions</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="historyTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Date</th>
                                                    <th>Package</th>
                                                    <th>Amount</th>
                                                    <th>Tokens</th>
                                                    <th>Payment Method</th>
                                                    <th>Status</th>
                                                    <th>Referral</th>
                                                    <th class="actions-column">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Purchases will be loaded here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Payment Methods</h3>
                                        </div>
                                        <div class="card-body">
                                            <div id="payment-methods-chart-container">
                                                <canvas id="payment-methods-chart" height="200"></canvas>
                                            </div>
                                            <div id="payment-methods-list" class="mt-3">
                                                <!-- Payment methods will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Purchase Status</h3>
                                        </div>
                                        <div class="card-body">
                                            <div id="status-chart-container">
                                                <canvas id="status-chart" height="200"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
        <script>
            $(document).ready(function() {
                // Initialize DataTable
                $('#usersTable').DataTable({
                    "responsive": {
                        details: {
                            display: $.fn.dataTable.Responsive.display.childRow,
                            type: 'column',
                            target: 'tr'
                        }
                    },
                    "autoWidth": false,
                    "order": [[0, 'asc']],
                    "pageLength": 25,
                    "ordering": true,
                    "orderCellsTop": true,
                    "language": {
                        "search": "Search users:",
                        "lengthMenu": "Show _MENU_ users per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ users",
                        "infoEmpty": "No users available",
                        "infoFiltered": "(filtered from _MAX_ total users)"
                    },
                    "columnDefs": [
                        {
                            "targets": -1, // Last column (Actions)
                            "className": 'actions-column',
                            "responsivePriority": 1 // Highest priority to keep visible
                        }
                    ],
                    "drawCallback": function() {
                        // Re-initialize tooltips after table is redrawn
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                });

                // Handle all button clicks using event delegation
                $('#usersTable').on('click', '.view-user, .view-affiliate, .generate-token, .view-purchase-history', function(e) {
                    // Prevent bubbling that might interfere with DataTables
                    e.stopPropagation();
                    
                    // Get the user ID from the button
                    const userId = $(this).data('user-id');
                    
                    // Determine which button was clicked based on class
                    if ($(this).hasClass('view-user')) {
                        // View user details
                        const modal = $('#userDetailsModal');
                        
                        // Show loading state
                        $('#loading-spinner').removeClass('d-none');
                        $('#user-details-content').addClass('d-none');
                        modal.modal('show');
                        
                        // Fetch user details
                        $.post('', {
                            action: 'get_user_details',
                            user_id: userId
                        }, function(response) {
                            $('#loading-spinner').addClass('d-none');
                            $('#user-details-content').removeClass('d-none');
                            
                            if (response.success) {
                                const user = response.user;
                                
                                // Update user info
                                modal.find('.user-avatar').attr('src', user.picture_url || 'path/to/default/avatar.png');
                                modal.find('.user-name').text(user.display_name);
                                modal.find('.user-email').text(user.email);
                                modal.find('.total-purchases').text(user.total_purchases || 0);
                                modal.find('.total-spent').text('฿' + parseFloat(user.total_spent || 0).toLocaleString(undefined, {minimumFractionDigits: 2}));
                                modal.find('.total-tokens').text(parseFloat(user.total_tokens || 0).toLocaleString());
        
                                // Update payment method stats
                                const paymentStatsList = modal.find('.payment-stats-list');
                                paymentStatsList.empty();
                                if (response.payment_stats && response.payment_stats.length > 0) {
                                    response.payment_stats.forEach(stat => {
                                        const percentage = (stat.total_amount / user.total_spent * 100).toFixed(1);
                                        paymentStatsList.append(`
                                            <div class="payment-stat-item mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-muted">${stat.payment_method}</span>
                                                    <span class="text-bold">฿${parseFloat(stat.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                                </div>
                                                <div class="progress" style="height: 3px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                        style="width: ${percentage}%" 
                                                        aria-valuenow="${percentage}" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    ${stat.total_transactions} transactions · ${parseFloat(stat.total_tokens).toLocaleString()} tokens
                                                </small>
                                            </div>
                                        `);
                                    });
                                } else {
                                    paymentStatsList.append('<p class="text-muted">No payment statistics available</p>');
                                }
        
                                // Update affiliate section
                                if (user.affiliate == '1') {
                                    modal.find('.affiliate-stats').removeClass('d-none');
                                    modal.find('.affiliate-code').text(user.affiliate_code);
                                    modal.find('.commission-rate').text(user.commission_rate || 0);
                                    modal.find('.total-earnings').text(parseFloat(user.total_earnings || 0).toLocaleString(undefined, {minimumFractionDigits: 2}));
                                } else {
                                    modal.find('.affiliate-stats').addClass('d-none');
                                }
        
                                // Update recent purchases
                                const purchasesList = modal.find('.recent-purchases-list');
                                purchasesList.empty();
                                if (response.recent_purchases && response.recent_purchases.length > 0) {
                                    response.recent_purchases.forEach(purchase => {
                                        purchasesList.append(`
                                            <div class="transaction-item">
                                                <div class="d-flex justify-content-between">
                                                    <strong>Purchase ID: ${purchase.id}</strong>
                                                    <span class="badge badge-${purchase.payment_status === 'completed' ? 'success' : 'warning'}">${purchase.payment_status}</span>
                                                </div>
                                                <div>Amount: ฿${parseFloat(purchase.amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                                <div>Tokens: ${purchase.tokens || 0}</div>
                                                <div>Payment Method: ${purchase.payment_method || 'N/A'}</div>
                                                ${purchase.referral_code ? `
                                                    <div class="text-muted">
                                                        <small>Referred by: ${purchase.affiliate_name || purchase.referral_code}</small>
                                                        ${purchase.commission_paid ? '<span class="badge badge-info ml-2">Commission Paid</span>' : ''}
                                                    </div>
                                                ` : ''}
                                                <small class="text-muted">${new Date(purchase.created_at).toLocaleString()}</small>
                                            </div>
                                        `);
                                    });
                                } else {
                                    purchasesList.append('<p class="text-muted">No recent purchases</p>');
                                }
        
                                // Update affiliate transactions
                                const transactionsList = modal.find('.affiliate-transactions-list');
                                if (user.affiliate == '1' && response.affiliate_transactions && response.affiliate_transactions.length > 0) {
                                    modal.find('.affiliate-transactions').removeClass('d-none');
                                    transactionsList.empty();
                                    response.affiliate_transactions.forEach(transaction => {
                                        transactionsList.append(`
                                            <div class="transaction-item">
                                                <div class="d-flex justify-content-between">
                                                    <strong>Referred User: ${transaction.referred_user || 'Unknown'}</strong>
                                                    <span class="badge badge-success">฿${parseFloat(transaction.amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                                </div>
                                                <small class="text-muted">${new Date(transaction.created_at).toLocaleString()}</small>
                                            </div>
                                        `);
                                    });
                                } else {
                                    modal.find('.affiliate-transactions').addClass('d-none');
                                }
        
                                // Store user data for edit form
                                modal.data('userData', user);
                            } else {
                                $('#user-details-content').html(`<div class="alert alert-danger">Error loading user details: ${response.error}</div>`);
                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            $('#loading-spinner').addClass('d-none');
                            $('#user-details-content').html(`
                                <div class="alert alert-danger">
                                    Error loading user details. Please try again later.<br>
                                    <small>${errorThrown}</small>
                                </div>
                            `).removeClass('d-none');
                        });
                    } else if ($(this).hasClass('view-affiliate')) {
                        // Handle view affiliate click
                        const modal = $('#userDetailsModal');
                        
                        // Show loading state
                        $('#loading-spinner').removeClass('d-none');
                        $('#user-details-content').addClass('d-none');
                        modal.modal('show');
                        
                        // Fetch user details with a focus on affiliate data
                        $.post('', {
                            action: 'get_user_details',
                            user_id: userId
                        }, function(response) {
                            $('#loading-spinner').addClass('d-none');
                            $('#user-details-content').removeClass('d-none');
                            
                            if (response.success) {
                                const user = response.user;
                                
                                // Update user info
                                modal.find('.user-avatar').attr('src', user.picture_url || 'path/to/default/avatar.png');
                                modal.find('.user-name').text(user.display_name);
                                modal.find('.user-email').text(user.email);
                                
                                // Show affiliate stats section and scroll to it
                                if (user.affiliate == '1') {
                                    modal.find('.affiliate-stats').removeClass('d-none');
                                    modal.find('.affiliate-code').text(user.affiliate_code);
                                    modal.find('.commission-rate').text(user.commission_rate || 0);
                                    modal.find('.total-earnings').text(parseFloat(user.total_earnings || 0).toLocaleString(undefined, {minimumFractionDigits: 2}));
                                    
                                    // Update affiliate transactions
                                    const transactionsList = modal.find('.affiliate-transactions-list');
                                    if (response.affiliate_transactions && response.affiliate_transactions.length > 0) {
                                        modal.find('.affiliate-transactions').removeClass('d-none');
                                        transactionsList.empty();
                                        response.affiliate_transactions.forEach(transaction => {
                                            transactionsList.append(`
                                                <div class="transaction-item">
                                                    <div class="d-flex justify-content-between">
                                                        <strong>Referred User: ${transaction.referred_user || 'Unknown'}</strong>
                                                        <span class="badge badge-success">฿${parseFloat(transaction.amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                                    </div>
                                                    <small class="text-muted">${new Date(transaction.created_at).toLocaleString()}</small>
                                                </div>
                                            `);
                                        });
                                        
                                        // Scroll to affiliate section after a slight delay to ensure modal is fully loaded
                                        setTimeout(function() {
                                            const affiliateSection = modal.find('.affiliate-stats');
                                            modal.find('.modal-body').animate({
                                                scrollTop: affiliateSection.offset().top - modal.find('.modal-body').offset().top
                                            }, 500);
                                        }, 300);
                                    } else {
                                        modal.find('.affiliate-transactions').addClass('d-none');
                                        transactionsList.html('<p class="text-muted">No affiliate transactions found</p>');
                                    }
                                } else {
                                    modal.find('.affiliate-stats').addClass('d-none');
                                    modal.find('.affiliate-transactions').addClass('d-none');
                                    
                                    // Show message if user is not an affiliate
                                    Swal.fire({
                                        title: 'Not an Affiliate',
                                        text: 'This user is not registered as an affiliate.',
                                        icon: 'info'
                                    });
                                }
                                
                                // Store user data for edit form
                                modal.data('userData', user);
                            } else {
                                $('#user-details-content').html(`<div class="alert alert-danger">Error loading affiliate details: ${response.error}</div>`);
                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            $('#loading-spinner').addClass('d-none');
                            $('#user-details-content').html(`
                                <div class="alert alert-danger">
                                    Error loading affiliate details. Please try again later.<br>
                                    <small>${errorThrown}</small>
                                </div>
                            `).removeClass('d-none');
                        });
                    } else if ($(this).hasClass('generate-token')) {
                        // Handle generate token click
                        window.location.href = `token_management.php?user_id=${userId}`;
                    } else if ($(this).hasClass('view-purchase-history')) {
                        // Handle view purchase history
                        const modal = $('#purchaseHistoryModal');
                        
                        // Show loading state
                        $('#purchase-history-loading').removeClass('d-none');
                        $('#purchase-history-content').addClass('d-none');
                        modal.modal('show');
                        
                        // Set the user ID on the modal for later use
                        $('.purchase-history-username').data('user-id', userId);
                        
                        // Load purchase history data
                        loadPurchaseHistory(userId);
                    }
                });

                // Handle affiliate toggle
                $('.affiliate-toggle').on('change', function() {
                    const userId = $(this).data('user-id');
                    const isAffiliate = $(this).prop('checked') ? '1' : '0';
                    
                    Swal.fire({
                        title: isAffiliate == '1' ? 'Make User Affiliate?' : 'Remove Affiliate Status?',
                        text: isAffiliate == '1' ? 
                            'This will create an affiliate account for the user.' : 
                            'This will deactivate the user\'s affiliate account.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, proceed!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('', {
                                action: 'toggle_affiliate',
                                user_id: userId,
                                is_affiliate: isAffiliate
                            }, function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Success!',
                                        'User affiliate status has been updated.',
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Failed to update affiliate status: ' + response.error,
                                        'error'
                                    );
                                    $(this).prop('checked', !isAffiliate);
                                }
                            });
                        } else {
                            $(this).prop('checked', !isAffiliate);
                        }
                    });
                });

                // Handle user status toggle
                $('.user-status').on('change', function() {
                    const userId = $(this).data('user-id');
                    const isActive = $(this).prop('checked') ? '1' : '0';
                    
                    Swal.fire({
                        title: isActive == '1' ? 'Activate User?' : 'Deactivate User?',
                        text: isActive == '1' ? 
                            'This will allow the user to access the system.' : 
                            'This will prevent the user from accessing the system.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, proceed!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('', {
                                action: 'update_user',
                                user_id: userId,
                                active: isActive
                            }, function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Success!',
                                        'User status has been updated.',
                                        'success'
                                    );
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Failed to update user status: ' + response.error,
                                        'error'
                                    );
                                    $(this).prop('checked', !isActive);
                                }
                            });
                        } else {
                            $(this).prop('checked', !isActive);
                        }
                    });
                });

                // Handle generate token button
                $('.generate-token').on('click', function() {
                    const userId = $(this).data('user-id');
                    window.location.href = `token_management.php?user_id=${userId}`;
                });

                // Handle edit user button
                $('.edit-user-btn').on('click', function() {
                    const userData = $('#userDetailsModal').data('userData');
                    if (userData) {
                        // Populate edit form
                        $('#edit_user_id').val(userData.user_id);
                        $('#edit_display_name').val(userData.display_name);
                        $('#edit_email').val(userData.email);
                        $('#edit_phone').val(userData.phone);
                        $('#edit_commission_rate').val(userData.commission_rate);
                        
                        // Show edit modal
                        $('#editUserModal').modal('show');
                    }
                });

                // Handle edit form submission
                $('#editUserForm').on('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = $(this).serialize();
                    
                    $.post('', formData, function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'User details have been updated.',
                                icon: 'success'
                            }).then(() => {
                                $('#editUserModal').modal('hide');
                                // Refresh the user details modal
                                $('.view-user[data-user-id="' + $('#edit_user_id').val() + '"]').click();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update user details: ' + response.error,
                                icon: 'error'
                            });
                        }
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to update user details. Please try again later.',
                            icon: 'error'
                        });
                    });
                });

                // Initialize tooltips
                $('[data-toggle="tooltip"]').tooltip();

                // Initialize DataTable for purchases
                let purchasesTable = null;

                function initializePurchasesTable(userId) {
                    if (purchasesTable) {
                        purchasesTable.destroy();
                    }

                    purchasesTable = $('#purchasesTable').DataTable({
                        processing: true,
                        serverSide: false,
                        order: [[1, 'desc']],
                        pageLength: 10,
                        ordering: true,
                        orderCellsTop: true,
                        responsive: {
                            details: {
                                display: $.fn.dataTable.Responsive.display.childRow,
                                type: 'column',
                                target: 'tr'
                            }
                        },
                        language: {
                            search: "Search purchases:",
                            emptyTable: "No purchases found"
                        },
                        columns: [
                            { data: 'id', responsivePriority: 3 },
                            { 
                                data: 'created_at',
                                responsivePriority: 2,
                                render: function(data) {
                                    return new Date(data).toLocaleString();
                                }
                            },
                            { 
                                data: 'amount',
                                responsivePriority: 1,
                                render: function(data) {
                                    return '฿' + parseFloat(data).toLocaleString(undefined, {minimumFractionDigits: 2});
                                }
                            },
                            { 
                                data: 'tokens',
                                responsivePriority: 1,
                                render: function(data) {
                                    return parseFloat(data).toLocaleString();
                                }
                            },
                            { data: 'payment_method', responsivePriority: 4 },
                            { 
                                data: 'payment_status',
                                responsivePriority: 1,
                                render: function(data) {
                                    const badgeClass = data === 'completed' ? 'success' : (data === 'canceled' ? 'danger' : 'warning');
                                    return `<span class="badge badge-${badgeClass}">${data}</span>`;
                                }
                            },
                            {
                                data: null,
                                className: 'actions-column',
                                responsivePriority: 1,
                                render: function(data) {
                                    let buttons = `
                                        <button type="button" class="btn btn-sm btn-info view-purchase" data-purchase='${JSON.stringify(data)}'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    `;
                                    
                                    // Add confirm button for pending purchases
                                    if (data.payment_status === 'pending') {
                                        buttons += `
                                            <button type="button" class="btn btn-sm btn-success confirm-purchase ml-1" data-purchase-id="${data.id}">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger cancel-purchase ml-1" data-purchase-id="${data.id}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        `;
                                    }
                                    
                                    return buttons;
                                }
                            }
                        ]
                    });

                    // Load purchase data
                    loadPurchases(userId);
                }

                function loadPurchases(userId) {
                    $.post('', {
                        action: 'get_user_purchases',
                        user_id: userId
                    }, function(response) {
                        if (response.success) {
                            purchasesTable.clear().rows.add(response.purchases).draw();
                        } else {
                            Swal.fire('Error', 'Failed to load purchases: ' + response.error, 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'Failed to load purchases. Please try again later.', 'error');
                    });
                }

                // Handle view purchase button
                $('#purchasesTable').on('click', '.view-purchase', function() {
                    const purchaseId = $(this).data('purchase-id');
                    
                    // Find the purchase data from the DataTable
                    const purchase = purchasesTable.row($(this).closest('tr')).data();
                    
                    Swal.fire({
                        title: 'Purchase Details',
                        html: `
                            <div class="text-left">
                                <p><strong>Purchase ID:</strong> ${purchase.id}</p>
                                <p><strong>Date:</strong> ${new Date(purchase.created_at).toLocaleString()}</p>
                                <p><strong>Amount:</strong> ฿${parseFloat(purchase.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                                <p><strong>Tokens:</strong> ${parseFloat(purchase.tokens).toLocaleString()}</p>
                                <p><strong>Payment Method:</strong> ${purchase.payment_method || 'N/A'}</p>
                                <p>
                                    <strong>Status:</strong> 
                                    <span class="badge badge-${purchase.payment_status === 'completed' ? 'success' : (purchase.payment_status === 'canceled' ? 'danger' : 'warning')}">
                                        ${purchase.payment_status}
                                    </span>
                                </p>
                                ${purchase.referral_code ? `
                                    <p><strong>Referral Code:</strong> ${purchase.referral_code}</p>
                                    <p><strong>Commission Paid:</strong> ${purchase.commission_paid == '1' ? 'Yes' : 'No'}</p>
                                ` : ''}
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonText: 'Close'
                    });
                });

                // Handle refresh button
                $('#refreshPurchases').on('click', function() {
                    const userId = $('#userDetailsModal').data('userData').user_id;
                    loadPurchases(userId);
                });

                // Initialize purchases table when user details modal is shown
                $('#userDetailsModal').on('shown.bs.modal', function() {
                    const userId = $(this).data('userData').user_id;
                    initializePurchasesTable(userId);
                });

                // Cleanup when user details modal is hidden
                $('#userDetailsModal').on('hidden.bs.modal', function() {
                    if (purchasesTable) {
                        purchasesTable.destroy();
                        purchasesTable = null;
                    }
                });

                // Handle buy tokens button
                $('#buyTokens').on('click', function() {
                    // Load token packages
                    $.post('', {
                        action: 'get_token_packages'
                    }, function(response) {
                        if (response.success) {
                            const packagesList = $('#packagesList');
                            packagesList.empty();

                            response.packages.forEach(package => {
                                packagesList.append(`
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">${package.name}</h5>
                                                <div class="text-center mb-3">
                                                    <h3 class="text-primary mb-0">${package.tokens}</h3>
                                                    <small class="text-muted">Tokens</small>
                                                </div>
                                                <p class="card-text">
                                                    <strong>Price:</strong> ฿${parseFloat(package.price).toLocaleString(undefined, {minimumFractionDigits: 2})}<br>
                                                    <strong>Validity:</strong> ${package.validity_days} days
                                                </p>
                                                <button type="button" class="btn btn-primary btn-block select-package" 
                                                        data-package='${JSON.stringify(package)}'>
                                                    Select Package
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `);
                            });

                            $('#buyTokensModal').modal('show');
                        } else {
                            Swal.fire('Error', 'Failed to load token packages: ' + response.error, 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'Failed to load token packages. Please try again later.', 'error');
                    });
                });

                // Handle package selection
                $('#packagesList').on('click', '.select-package', function() {
                    const package = $(this).data('package');
                    const modal = $('#confirmPurchaseModal');
                    
                    $('#purchase_user_id').val($('#userDetailsModal').data('userData').user_id);
                    $('#purchase_package_id').val(package.id);
                    
                    modal.find('.package-name').text(package.name);
                    modal.find('.package-tokens').text(package.tokens);
                    modal.find('.package-price').text('฿' + parseFloat(package.price).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    modal.find('.package-validity').text(package.validity_days + ' days');
                    
                    $('#buyTokensModal').modal('hide');
                    modal.modal('show');
                });

                // Handle purchase form submission
                $('#purchaseForm').on('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = $(this).serialize();
                    
                    $.post('', formData, function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Purchase created successfully. The user will be notified about payment instructions.',
                                icon: 'success'
                            }).then(() => {
                                $('#confirmPurchaseModal').modal('hide');
                                loadPurchases($('#userDetailsModal').data('userData').user_id);
                            });
                        } else {
                            Swal.fire('Error', 'Failed to create purchase: ' + response.error, 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Error', 'Failed to create purchase. Please try again later.', 'error');
                    });
                });
                
                // Handle confirm purchase action
                $('#purchasesTable').on('click', '.confirm-purchase', function() {
                    const purchaseId = $(this).data('purchase-id');
                    const userId = $('#userDetailsModal').data('userData').user_id;
                    
                    Swal.fire({
                        title: 'Confirm Purchase?',
                        text: 'This will mark the purchase as completed and add tokens to the user\'s account.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, confirm it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('', {
                                action: 'confirm_purchase',
                                purchase_id: purchaseId
                            }, function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Purchase has been confirmed successfully.',
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload purchases table
                                        loadPurchases(userId);
                                    });
                                } else {
                                    Swal.fire('Error', 'Failed to confirm purchase: ' + response.error, 'error');
                                }
                            }).fail(function() {
                                Swal.fire('Error', 'Failed to confirm purchase. Please try again later.', 'error');
                            });
                        }
                    });
                });
                
                // Handle cancel purchase action
                $('#purchasesTable').on('click', '.cancel-purchase', function() {
                    const purchaseId = $(this).data('purchase-id');
                    const userId = $('#userDetailsModal').data('userData').user_id;
                    
                    Swal.fire({
                        title: 'Cancel Purchase?',
                        text: 'This will mark the purchase as canceled. This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, cancel it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('', {
                                action: 'cancel_purchase',
                                purchase_id: purchaseId
                            }, function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Purchase has been canceled successfully.',
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload purchases table
                                        loadPurchases(userId);
                                    });
                                } else {
                                    Swal.fire('Error', 'Failed to cancel purchase: ' + response.error, 'error');
                                }
                            }).fail(function() {
                                Swal.fire('Error', 'Failed to cancel purchase. Please try again later.', 'error');
                            });
                        }
                    });
                });
                
                // Handle View Purchase History button
                $('.view-purchase-history').on('click', function() {
                    const userId = $(this).data('user-id');
                    const modal = $('#purchaseHistoryModal');
                    
                    // Show loading state
                    $('#purchase-history-loading').removeClass('d-none');
                    $('#purchase-history-content').addClass('d-none');
                    modal.modal('show');
                    
                    // Load purchase history data
                    loadPurchaseHistory(userId);
                });
                
                // Function to load purchase history data
                function loadPurchaseHistory(userId, filter = 'all', dateFrom = null, dateTo = null) {
                    $.post('', {
                        action: 'view_purchase_history',
                        user_id: userId,
                        filter: filter,
                        date_from: dateFrom,
                        date_to: dateTo
                    }, function(response) {
                        $('#purchase-history-loading').addClass('d-none');
                        $('#purchase-history-content').removeClass('d-none');
                        
                        if (response.success) {
                            // Update user info
                            const user = response.user;
                            $('.purchase-history-avatar').attr('src', user.picture_url || 'path/to/default/avatar.png');
                            $('.purchase-history-username').text(user.display_name || user.name);
                            $('.purchase-history-email').text(user.email);
                            $('.purchase-history-balance').html(`<span class="badge badge-info">Balance: ${user.balance} tokens</span>`);
                            
                            // Update summary statistics
                            const summary = response.summary;
                            $('#total-purchases').text(summary.total_purchases);
                            $('#completed-purchases').text(summary.completed_purchases);
                            $('#pending-purchases').text(summary.pending_purchases);
                            $('#canceled-purchases').text(summary.canceled_purchases);
                            $('#total-purchase-amount').text('฿' + parseFloat(summary.total_spent || 0).toLocaleString(undefined, {minimumFractionDigits: 2}));
                            
                            // Initialize history table
                            if ($.fn.DataTable.isDataTable('#historyTable')) {
                                $('#historyTable').DataTable().destroy();
                            }
                            
                            const historyTable = $('#historyTable').DataTable({
                                data: response.purchases,
                                ordering: true,
                                orderCellsTop: true,
                                responsive: {
                                    details: {
                                        display: $.fn.dataTable.Responsive.display.childRow,
                                        type: 'column',
                                        target: 'tr'
                                    }
                                },
                                language: {
                                    search: "Search purchases:",
                                    emptyTable: "No purchases found"
                                },
                                columns: [
                                    { data: 'id' },
                                    { 
                                        data: 'created_at',
                                        render: function(data) {
                                            return new Date(data).toLocaleString();
                                        }
                                    },
                                    { data: 'package_name' },
                                    { 
                                        data: 'total_amount',
                                        render: function(data) {
                                            return '฿' + parseFloat(data).toLocaleString(undefined, {minimumFractionDigits: 2});
                                        }
                                    },
                                    { 
                                        data: 'total_tokens',
                                        render: function(data) {
                                            return parseFloat(data).toLocaleString();
                                        }
                                    },
                                    { data: 'payment_method' },
                                    { 
                                        data: 'payment_status',
                                        render: function(data) {
                                            const badgeClass = data === 'completed' ? 'success' : (data === 'canceled' ? 'danger' : 'warning');
                                            return `<span class="badge badge-${badgeClass}">${data}</span>`;
                                        }
                                    },
                                    { 
                                        data: null,
                                        render: function(data) {
                                            if (data.referral_code) {
                                                return data.affiliate_name || data.referral_code;
                                            }
                                            return 'None';
                                        }
                                    },
                                    {
                                        data: null,
                                        className: 'actions-column',
                                        responsivePriority: 1,
                                        render: function(data) {
                                            let buttons = `
                                                <button type="button" class="btn btn-sm btn-info view-purchase-details" data-purchase-id="${data.id}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            `;
                                            
                                            // Add action buttons based on status
                                            if (data.payment_status === 'pending') {
                                                buttons += `
                                                    <button type="button" class="btn btn-sm btn-success confirm-purchase-history ml-1" data-purchase-id="${data.id}">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger cancel-purchase-history ml-1" data-purchase-id="${data.id}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                `;
                                            }
                                            
                                            return buttons;
                                        }
                                    }
                                ],
                                order: [[1, 'desc']],
                                pageLength: 10
                            });
                            
                            // Initialize payment methods chart if we have data
                            if (response.payment_methods && response.payment_methods.length > 0) {
                                // Prepare chart data
                                const labels = [];
                                const data = [];
                                const backgroundColors = [
                                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                                ];
                                
                                // Populate payment methods list
                                const paymentMethodsList = $('#payment-methods-list');
                                paymentMethodsList.empty();
                                
                                response.payment_methods.forEach((method, index) => {
                                    labels.push(method.payment_method);
                                    data.push(parseFloat(method.total_amount));
                                    
                                    const percentage = summary.total_spent > 0 ? 
                                        (method.total_amount / summary.total_spent * 100).toFixed(1) : 0;
                                    
                                    paymentMethodsList.append(`
                                        <div class="payment-method-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span>${method.payment_method}</span>
                                                <span class="text-bold">฿${parseFloat(method.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                            </div>
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar" style="width: ${percentage}%; background-color: ${backgroundColors[index % backgroundColors.length]}"></div>
                                            </div>
                                            <small class="text-muted">
                                                ${method.total_transactions} transactions · ${parseInt(method.total_tokens).toLocaleString()} tokens
                                            </small>
                                        </div>
                                    `);
                                });
                                
                                // Create payment methods chart
                                const paymentMethodsCtx = document.getElementById('payment-methods-chart').getContext('2d');
                                if (window.paymentMethodsChart) {
                                    window.paymentMethodsChart.destroy();
                                }
                                
                                window.paymentMethodsChart = new Chart(paymentMethodsCtx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            data: data,
                                            backgroundColor: backgroundColors,
                                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                                        }]
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        tooltips: {
                                            callbacks: {
                                                label: function(tooltipItem, data) {
                                                    const value = data.datasets[0].data[tooltipItem.index];
                                                    return data.labels[tooltipItem.index] + ': ฿' + parseFloat(value).toLocaleString(undefined, {minimumFractionDigits: 2});
                                                }
                                            }
                                        },
                                        legend: {
                                            position: 'right'
                                        }
                                    }
                                });
                                
                                // Create status chart
                                const statusCtx = document.getElementById('status-chart').getContext('2d');
                                if (window.statusChart) {
                                    window.statusChart.destroy();
                                }
                                
                                window.statusChart = new Chart(statusCtx, {
                                    type: 'pie',
                                    data: {
                                        labels: ['Completed', 'Pending', 'Canceled'],
                                        datasets: [{
                                            data: [
                                                summary.completed_purchases,
                                                summary.pending_purchases,
                                                summary.canceled_purchases
                                            ],
                                            backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                                        }]
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        legend: {
                                            position: 'right'
                                        }
                                    }
                                });
                            }
                        } else {
                            Swal.fire('Error', 'Failed to load purchase history: ' + response.error, 'error');
                        }
                    }).fail(function() {
                        $('#purchase-history-loading').addClass('d-none');
                        Swal.fire('Error', 'Failed to load purchase history. Please try again later.', 'error');
                    });
                }
                
                // Handle purchase history filter form submission
                $('#purchase-filter-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    const userId = $('.purchase-history-username').data('user-id');
                    const filter = $('#status-filter').val();
                    const dateFrom = $('#date-from').val();
                    const dateTo = $('#date-to').val();
                    
                    loadPurchaseHistory(userId, filter, dateFrom, dateTo);
                });
                
                // Handle filter reset
                $('#reset-filters').on('click', function() {
                    $('#status-filter').val('all');
                    $('#date-from').val('');
                    $('#date-to').val('');
                    
                    const userId = $('.purchase-history-username').data('user-id');
                    loadPurchaseHistory(userId);
                });
                
                // Handle confirm purchase from history table
                $('#historyTable').on('click', '.confirm-purchase-history', function() {
                    const purchaseId = $(this).data('purchase-id');
                    const userId = $('.purchase-history-username').data('user-id');
                    
                    Swal.fire({
                        title: 'Confirm Purchase?',
                        text: 'This will mark the purchase as completed and add tokens to the user\'s account.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, confirm it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('', {
                                action: 'confirm_purchase',
                                purchase_id: purchaseId
                            }, function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Purchase has been confirmed successfully.',
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload purchase history
                                        loadPurchaseHistory(userId);
                                    });
                                } else {
                                    Swal.fire('Error', 'Failed to confirm purchase: ' + response.error, 'error');
                                }
                            }).fail(function() {
                                Swal.fire('Error', 'Failed to confirm purchase. Please try again later.', 'error');
                            });
                        }
                    });
                });
                
                // Handle cancel purchase from history table
                $('#historyTable').on('click', '.cancel-purchase-history', function() {
                    const purchaseId = $(this).data('purchase-id');
                    const userId = $('.purchase-history-username').data('user-id');
                    
                    Swal.fire({
                        title: 'Cancel Purchase?',
                        text: 'This will mark the purchase as canceled. This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, cancel it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('', {
                                action: 'cancel_purchase',
                                purchase_id: purchaseId
                            }, function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Purchase has been canceled successfully.',
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload purchase history
                                        loadPurchaseHistory(userId);
                                    });
                                } else {
                                    Swal.fire('Error', 'Failed to cancel purchase: ' + response.error, 'error');
                                }
                            }).fail(function() {
                                Swal.fire('Error', 'Failed to cancel purchase. Please try again later.', 'error');
                            });
                        }
                    });
                });

                // Handle view purchase details button
                $('#historyTable').on('click', '.view-purchase-details', function() {
                    const purchaseId = $(this).data('purchase-id');
                    
                    // Find the purchase data from the DataTable
                    const purchase = historyTable.row($(this).closest('tr')).data();
                    
                    Swal.fire({
                        title: 'Purchase Details',
                        html: `
                            <div class="text-left">
                                <p><strong>Purchase ID:</strong> ${purchase.id}</p>
                                <p><strong>Date:</strong> ${new Date(purchase.created_at).toLocaleString()}</p>
                                <p><strong>Package:</strong> ${purchase.package_name}</p>
                                <p><strong>Amount:</strong> ฿${parseFloat(purchase.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                                <p><strong>Tokens:</strong> ${parseFloat(purchase.total_tokens).toLocaleString()}</p>
                                <p><strong>Payment Method:</strong> ${purchase.payment_method}</p>
                                <p>
                                    <strong>Status:</strong> 
                                    <span class="badge badge-${purchase.payment_status === 'completed' ? 'success' : (purchase.payment_status === 'canceled' ? 'danger' : 'warning')}">
                                        ${purchase.payment_status}
                                    </span>
                                </p>
                                ${purchase.referral_code ? `
                                    <p><strong>Referral:</strong> ${purchase.affiliate_name || purchase.referral_code}</p>
                                ` : ''}
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonText: 'Close'
                    });
                });
            });
        </script>
    </body>
    </html> 