<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../connection.php';

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

// Live operational badge counts for Admin Navbar
$nav_active_orders = 0;
$nav_low_stock = 0;
$nav_inquiries = 0;
try {
    $ao_rs = Database::search("SELECT COUNT(id) AS c FROM orders WHERE order_status NOT IN ('delivered', 'collected', 'cancelled') OR order_status IS NULL");
    if ($ao_rs && $r = $ao_rs->fetch_assoc()) $nav_active_orders = (int)$r['c'];

    $ls_rs = Database::search("SELECT COUNT(id) AS c FROM items WHERE stock_quantity <= 5");
    if ($ls_rs && $r = $ls_rs->fetch_assoc()) $nav_low_stock = (int)$r['c'];

    $inq_rs = Database::search("SELECT COUNT(id) AS c FROM contact_messages");
    if ($inq_rs && $r = $inq_rs->fetch_assoc()) $nav_inquiries = (int)$r['c'];
} catch (Exception $e) {
    // Ignore if table not migrated yet
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="../assets/css/custom-ui.css">
<script>document.body.classList.add('fc-admin');</script>

<style>
    .navbar-custom {
        background-color: #15803d !important;
        padding-top: 10px;
        padding-bottom: 10px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
    }
    .navbar-brand img {
        height: 46px;
        width: auto;
    }
    .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.92) !important;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 999px;
        padding: 8px 16px !important;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .navbar-nav .nav-link:hover, 
    .navbar-nav .nav-link.active {
        background-color: rgba(255, 255, 255, 0.18) !important;
        color: #ffffff !important;
    }
    .nav-pill-badge {
        font-size: 0.72rem;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 999px;
        line-height: 1.2;
    }
    /* Profile Avatar */
    .mt-logo {
        width: 40px;
        height: 40px;
        background: #0f172a;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        color: white;
        font-size: 14px;
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
        background: #4ade80;
        border: 1.5px solid #0f172a;
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

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid px-4">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <img src="../assets/images/logo/logo-freshco.png" alt="Freshcomart Logo">
            <span class="badge bg-warning text-dark rounded-pill fw-bold d-none d-sm-inline-block" style="font-size:0.7rem;">ADMIN OPS</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'order.php') ? 'active' : ''; ?>" href="order.php">
                        <i class="bi bi-basket2"></i>Active Orders
                        <?php if ($nav_active_orders > 0): ?>
                            <span class="nav-pill-badge bg-warning text-dark"><?php echo $nav_active_orders; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'Item.php' || $current_page === 'ItemAdd.php') ? 'active' : ''; ?>" href="Item.php">
                        <i class="bi bi-box-seam"></i>Inventory
                        <?php if ($nav_low_stock > 0): ?>
                            <span class="nav-pill-badge bg-danger text-white" title="<?php echo $nav_low_stock; ?> low/out-of-stock items"><?php echo $nav_low_stock; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'completOrder.php') ? 'active' : ''; ?>" href="completOrder.php">
                        <i class="bi bi-archive"></i>Order Archive
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'summary.php') ? 'active' : ''; ?>" href="summary.php">
                        <i class="bi bi-bar-chart-line"></i>Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'inquiries.php') ? 'active' : ''; ?>" href="inquiries.php">
                        <i class="bi bi-envelope"></i>Inquiries
                        <?php if ($nav_inquiries > 0): ?>
                            <span class="nav-pill-badge bg-light text-dark"><?php echo $nav_inquiries; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <!-- Right Side Admin Controls -->
            <div class="d-flex align-items-center gap-2">
                <a href="../index.php" target="_blank" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold text-success" title="Open Customer Storefront">
                    <i class="bi bi-shop me-1"></i>View Store
                </a>
                <div class="mt-logo" title="Logged in as Admin">
                    <?php echo htmlspecialchars($logo_initials); ?>
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