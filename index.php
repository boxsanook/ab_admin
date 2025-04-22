<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Check if the required session variables are set
if (!isset($_SESSION['line_user'], $_SESSION['authenticated'], $_SESSION['isAdmin'], $_SESSION['userActive'])) {
    // Redirect to login if session variables are missing
    header('Location: login.php');
    exit;
}
include 'auth_check.php';
require_once '../webhook/config/config.php';
$host = DB_HOST;
$user = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getTotalUsers() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM users WHERE active = 1";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

function getActiveAffiliates() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM users WHERE affiliate = 1 AND active = 1";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

function getMonthlyRevenue() {
    global $conn;
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM affiliate_transactions 
              WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
              AND YEAR(created_at) = YEAR(CURRENT_DATE())
              AND status = 'completed'";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}

function getActivityData() {
    global $conn;
    // Get last 6 months of data
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_users,
                SUM(CASE WHEN affiliate = 1 THEN 1 ELSE 0 END) as new_affiliates
              FROM users 
              WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month ASC";
    
    $result = $conn->query($query);
    $data = [
        'labels' => [],
        'new_users' => [],
        'new_affiliates' => []
    ];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $month = date('M', strtotime($row['month'] . '-01'));
            $data['labels'][] = $month;
            $data['new_users'][] = (int)$row['new_users'];
            $data['new_affiliates'][] = (int)$row['new_affiliates'];
        }
    }
    
    // If less than 6 months of data, pad with empty months
    $months_needed = 6 - count($data['labels']);
    if ($months_needed > 0) {
        $last_date = empty($data['labels']) ? date('Y-m-01') : date('Y-m-01', strtotime('-' . count($data['labels']) . ' months'));
        for ($i = 0; $i < $months_needed; $i++) {
            array_unshift($data['labels'], date('M', strtotime($last_date . " -" . ($months_needed - $i) . " months")));
            array_unshift($data['new_users'], 0);
            array_unshift($data['new_affiliates'], 0);
        }
    }
    
    return $data;
}

// Get all stats at once
function getDashboardStats() {
    return [
        'total_users' => getTotalUsers(),
        'active_affiliates' => getActiveAffiliates(),
        'monthly_revenue' => getMonthlyRevenue()
    ];
}

$stats = getDashboardStats();
$activity_data = getActivityData();
?> 
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin AB Ai Management</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
       <?php include 'includes/navbar.php'; ?>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <?php include 'includes/sidebar.php'; ?>
        <!-- Content Wrapper -->
        <div class="content-wrapper">

            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Dashboard</h1>
                        </div>
                        <div class="col-sm-6 text-right">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                                    <i class="fas fa-calendar"></i> This week
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">

                <!-- Info boxes -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Users</span>
                                <span class="info-box-number"><?php echo number_format($stats['total_users']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Tokens</span>
                                <span class="info-box-number">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-handshake"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active Affiliates</span>
                                <span class="info-box-number"><?php echo number_format($stats['active_affiliates']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Monthly Revenue</span>
                                <span class="info-box-number">$<?php echo number_format($stats['monthly_revenue'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Activity Overview</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="activityChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

            </section>

            <!-- Chart.js -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                var ctx = document.getElementById('activityChart').getContext('2d');
                var activityData = <?php echo json_encode($activity_data); ?>;
                var myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: activityData.labels,
                        datasets: [{
                            label: 'New Users',
                            data: activityData.new_users,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1,
                            fill: false
                        },
                        {
                            label: 'New Affiliates',
                            data: activityData.new_affiliates,
                            borderColor: 'rgb(255, 159, 64)',
                            tension: 0.1,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: 'User Growth Over Time'
                            }
                        }
                    }
                });
            </script>
        </div>
        <!-- /.content-wrapper -->

        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                AB Ai Management
            </div>
            <strong>&copy; <?php echo date('Y'); ?> <a href="#">AB Ai Management</a>.</strong> All rights reserved.
        </footer>
    </div>
    <!-- ./wrapper -->


    <!-- jQuery (load only once) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- AdminLTE JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>



</body>

</html>