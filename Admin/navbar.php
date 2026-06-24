<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle Admin Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_logout'])) {
    unset($_SESSION['admin_users']);
    unset($_SESSION['admin_user_id']);
    unset($_SESSION['admin_user_email']);
    unset($_SESSION['admin_is_admin']);
    header("Location: login.php");
    exit();
}

// Set initials for avatar
$logo_initials = 'AD';
if (isset($_SESSION['admin_users'])) {
    $first = isset($_SESSION['admin_users']['first_name']) ? strtoupper(substr($_SESSION['admin_users']['first_name'], 0, 1)) : '';
    $last = isset($_SESSION['admin_users']['last_name']) ? strtoupper(substr($_SESSION['admin_users']['last_name'], 0, 1)) : '';
    $logo_initials = $first . $last;
    if ($logo_initials === '') $logo_initials = 'AD';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .navbar-custom {
            background-color: #27b62e !important;
            padding-top: 10px;
            padding-bottom: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.1);
        }
        .navbar-brand img {
            height: 50px;
            width: auto;
        }
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            font-size: 1.05rem;
            border-radius: 20px;
            padding: 8px 20px !important;
            transition: all 0.2s ease;
        }
        .navbar-nav .nav-link:hover, 
        .navbar-nav .nav-link.active {
            background-color: #1e8e24 !important;
            color: white !important;
        }
        
        /* Profile Avatar */
        .mt-logo {
            width: 43px;
            height: 43px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            color: white;
            font-size: 15px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .mt-status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            position: absolute;
            top: 1px;
            right: 1px;
            background: GreenYellow;
            border: 1.5px solid #007bff;
        }
        
        @media (max-width: 991px) {
            .navbar-nav {
                padding-top: 10px;
                gap: 5px;
            }
            .mt-logo {
                width: 36px;
                height: 36px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid px-4">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../assets/images/logo/logo-freshco.png" alt="Fresh-Co Mart Logo">
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto gap-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'Item.php' || basename($_SERVER['PHP_SELF']) == 'ItemAdd.php') ? 'active' : ''; ?>" href="Item.php">Items</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'order.php') ? 'active' : ''; ?>" href="order.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'completOrder.php') ? 'active' : ''; ?>" href="completOrder.php">Completed Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'summary.php') ? 'active' : ''; ?>" href="summary.php">Summary</a>
                    </li>
                </ul>
                
                <!-- Right Side Admin Controls -->
                <div class="d-flex align-items-center gap-3">
                    <div class="mt-logo" title="Logged in as Admin">
                        <?php echo $logo_initials; ?>
                        <div class="mt-status-dot"></div>
                    </div>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to logout?');">
                        <button type="submit" name="admin_logout" class="btn btn-outline-light rounded-pill btn-sm px-3 fw-bold">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>