<?php
// No need to start session here as it's already started in header.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
        <img src="assets/img/logo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Admin Panel</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="assets/img/user-avatar.png" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">Admin Name</a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>



                <!-- User Management -->
                <li
                    class="nav-item <?php echo in_array($current_page, ['user-management.php', 'users.php', 'token_management.php']) ? 'menu-open' : ''; ?>">
                    <a href="#"
                        class="nav-link <?php echo in_array($current_page, ['user-management.php', 'users.php', 'token_management.php']) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            User Management
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="users.php"
                                class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>All Users</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="user-management.php"
                                class="nav-link <?php echo $current_page === 'user-management.php' ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>User Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="token_management.php"
                                class="nav-link <?php echo $current_page === 'token_management.php' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-coins"></i>
                                <p>Token Management</p>
                            </a>
                        </li>
                        
                    </ul>
                </li>

                <!-- Affiliate System -->
                <li
                    class="nav-item <?php echo in_array($current_page, ['affiliate-system.php', 'affiliate_management.php', 'affiliate_payouts.php']) ? 'menu-open' : ''; ?>">
                    <a href="#"
                        class="nav-link <?php echo in_array($current_page, ['affiliate-system.php', 'affiliate_management.php', 'affiliate_payouts.php']) ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-handshake"></i>
                        <p>
                            Affiliate System
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="affiliate-system.php"
                                class="nav-link <?php echo $current_page === 'affiliate-system.php' ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Overview</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="affiliate_management.php"
                                class="nav-link <?php echo $current_page === 'affiliate_management.php' ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Management</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="affiliate_payouts.php"
                                class="nav-link <?php echo $current_page === 'affiliate_payouts.php' ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Payouts</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Reports -->
                <li class="nav-item">
                    <a href="reports.php"
                        class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Reports</p>
                    </a>
                </li>

                <!-- Settings -->
                <li class="nav-item">
                    <a href="settings.php"
                        class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Settings</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>