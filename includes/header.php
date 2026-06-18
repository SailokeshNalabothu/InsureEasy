<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base_url = "/InsureEasy/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InsureEasy - Online Insurance Management Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="<?php echo $base_url; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="ambient-glow"></div>
    <div class="ambient-glow-2"></div>

    <!-- Main Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-light custom-nav sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_url; ?>index.php">
                <span class="fs-4 fw-bold gradient-text"><i class="bi bi-shield-check-fill"></i> InsureEasy</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    
                    <?php if (isset($_SESSION['admin_id'])): ?>
                        <!-- Admin Navigation links -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/manage_agents.php"><i class="bi bi-people-fill"></i> Manage Agents</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>admin/reports.php"><i class="bi bi-graph-up-arrow"></i> Reports</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-outline-danger btn-sm" href="<?php echo $base_url; ?>admin/logout.php"><i class="bi bi-box-arrow-right"></i> Logout (Admin)</a>
                        </li>

                    <?php elseif (isset($_SESSION['agent_id'])): ?>
                        <!-- Agent Navigation links -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>agent/dashboard.php"><i class="bi bi-grid-fill"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>agent/manage_customers.php"><i class="bi bi-people"></i> Customers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>agent/manage_policies.php"><i class="bi bi-file-earmark-text"></i> Policies</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>agent/send_sms.php"><i class="bi bi-chat-left-dots"></i> Send SMS</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-outline-danger btn-sm" href="<?php echo $base_url; ?>agent/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                        </li>

                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <!-- Customer Navigation links -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/dashboard.php"><i class="bi bi-house-door-fill"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/my_policies.php"><i class="bi bi-file-lock2"></i> My Policies</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/payment_history.php"><i class="bi bi-credit-card-2-back"></i> Payments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/recommendations.php"><i class="bi bi-lightbulb-fill"></i> Policy Finder</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>user/profile.php"><i class="bi bi-person-bounding-box"></i> Profile</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-outline-danger btn-sm" href="<?php echo $base_url; ?>user/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                        </li>

                    <?php else: ?>
                        <!-- Guest Navigation links -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>index.php#about">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>index.php#contact">Contact</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo $base_url; ?>login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn gradient-btn btn-sm" href="<?php echo $base_url; ?>register.php">Agent Register</a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Theme Switcher Button -->
                    <li class="nav-item ms-2">
                        <button id="theme-toggle-btn" class="theme-switch-btn btn" title="Toggle Light/Dark Mode">
                            <i class="bi bi-moon-stars-fill"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-4">
