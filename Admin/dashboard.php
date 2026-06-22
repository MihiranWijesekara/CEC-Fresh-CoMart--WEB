<?php
require 'adminAuth.php';
require '../connection.php';

// Fetch Statistics
$sales_rs = Database::search("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders");
$total_sales = $sales_rs ? (float)$sales_rs->fetch_assoc()['total'] : 0.00;

$orders_rs = Database::search("SELECT COUNT(id) AS total FROM orders");
$total_orders = $orders_rs ? (int)$orders_rs->fetch_assoc()['total'] : 0;

$users_rs = Database::search("SELECT COUNT(id) AS total FROM users WHERE is_admin = 0");
$total_customers = $users_rs ? (int)$users_rs->fetch_assoc()['total'] : 0;

$products_rs = Database::search("SELECT COUNT(id) AS total FROM items");
$total_products = $products_rs ? (int)$products_rs->fetch_assoc()['total'] : 0;

$pending_rs = Database::search("SELECT COUNT(id) AS total FROM orders WHERE order_status IN ('pending', 'processing') OR order_status = ''");
$pending_orders = $pending_rs ? (int)$pending_rs->fetch_assoc()['total'] : 0;

// Fetch Recent 5 Orders
$recent_orders_rs = Database::search("
    SELECT o.id, o.order_number, u.first_name, u.last_name, o.total_amount, o.order_status, o.created_at 
    FROM orders o 
    INNER JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CEC COMART</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f4f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 24px;
            border: none;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        .stat-icon {
            font-size: 2.2rem;
            padding: 12px;
            border-radius: 12px;
            width: fit-content;
        }
        .sales-icon { background: #e8f5e9; color: #2e7d32; }
        .orders-icon { background: #e3f2fd; color: #1565c0; }
        .pending-icon { background: #fff8e1; color: #f57f17; }
        .customers-icon { background: #f3e5f5; color: #6a1b9a; }
        .products-icon { background: #f9f9f9; color: #37474f; }
        
        .recent-orders-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-top: 30px;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: capitalize;
        }
        .status-pending { background-color: #ffebee; color: #c62828; }
        .status-processing { background-color: #fff3e0; color: #ef6c00; }
        .status-shipped { background-color: #e3f2fd; color: #1565c0; }
        .status-delivered { background-color: #e8f5e9; color: #2e7d32; }
        
        .action-btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-container">
        <!-- Welcome banner -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1" style="color: #212529;">Dashboard</h1>
                <p class="text-muted">Welcome back, administrator. Here is your store overview today.</p>
            </div>
            <div>
                <a href="ItemAdd.php" class="btn btn-primary action-btn me-2 shadow-sm"><i class="bi bi-plus-lg me-2"></i>Add Product</a>
                <a href="order.php" class="btn btn-success action-btn shadow-sm"><i class="bi bi-cart me-2"></i>Orders List</a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row g-4">
            <!-- Stat 1 -->
            <div class="col-md-4 col-lg-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon sales-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-1 text-uppercase fw-semibold" style="font-size: 0.85rem; letter-spacing: 0.5px;">Total Revenue</p>
                    <h3 class="fw-bold mb-0">Rs. <?php echo number_format($total_sales, 2); ?></h3>
                </div>
            </div>
            <!-- Stat 2 -->
            <div class="col-md-4 col-lg-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon orders-icon">
                            <i class="bi bi-cart-check"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-1 text-uppercase fw-semibold" style="font-size: 0.85rem; letter-spacing: 0.5px;">Total Orders</p>
                    <h3 class="fw-bold mb-0"><?php echo $total_orders; ?></h3>
                </div>
            </div>
            <!-- Stat 3 -->
            <div class="col-md-4 col-lg-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon pending-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-1 text-uppercase fw-semibold" style="font-size: 0.85rem; letter-spacing: 0.5px;">Pending Orders</p>
                    <h3 class="fw-bold mb-0"><?php echo $pending_orders; ?></h3>
                </div>
            </div>
            <!-- Stat 4 -->
            <div class="col-md-4 col-lg-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon customers-icon">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-1 text-uppercase fw-semibold" style="font-size: 0.85rem; letter-spacing: 0.5px;">Total Customers</p>
                    <h3 class="fw-bold mb-0"><?php echo $total_customers; ?></h3>
                </div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="recent-orders-card">
            <h4 class="fw-bold mb-4">Recent Orders Log</h4>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr style="border-bottom: 2px solid #e3e6ed;">
                            <th class="py-3">Order Number</th>
                            <th class="py-3">Customer</th>
                            <th class="py-3">Amount</th>
                            <th class="py-3">Date & Time</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_orders_rs && $recent_orders_rs->num_rows > 0) {
                            while ($order = $recent_orders_rs->fetch_assoc()) {
                                $status = $order['order_status'] ? $order['order_status'] : 'pending';
                                $statusClass = 'status-' . $status;
                                ?>
                                <tr style="border-bottom: 1px solid #f1f3f7;">
                                    <td class="fw-bold">#<?php echo htmlspecialchars($order['order_number'] ?: str_pad($order['id'], 5, '0', STR_PAD_LEFT)); ?></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td class="fw-semibold">Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                    <td class="text-muted"><?php echo date("F d, Y h:i A", strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo $statusClass; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="order.php" class="btn btn-outline-primary btn-sm px-3 rounded-pill">Manage</a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No orders found in database.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>