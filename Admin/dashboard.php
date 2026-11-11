<?php
require 'adminAuth.php';
require '../connection.php';
require 'orderHelpers.php';

// 1. Financial Statistics: Paid Revenue vs Pending Collection
$paid_rev_rs = Database::search("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE payment_status = 'paid'");
$paid_revenue = $paid_rev_rs ? (float)$paid_rev_rs->fetch_assoc()['total'] : 0.00;

$unpaid_col_rs = Database::search("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE payment_status = 'unpaid' AND (order_status NOT IN ('cancelled') OR order_status IS NULL)");
$pending_collection = $unpaid_col_rs ? (float)$unpaid_col_rs->fetch_assoc()['total'] : 0.00;

// 2. Today's Pending COD Orders (and total active COD)
$today_cod_rs = Database::search("
    SELECT COUNT(id) AS total FROM orders 
    WHERE (purchase_method = 'cod' OR purchase_method = '' OR purchase_method IS NULL) 
      AND (order_status IN ('pending', 'processing', 'shipped') OR order_status IS NULL)
      AND DATE(created_at) = CURDATE()
");
$today_pending_cod = $today_cod_rs ? (int)$today_cod_rs->fetch_assoc()['total'] : 0;

$all_active_cod_rs = Database::search("
    SELECT COUNT(id) AS total FROM orders 
    WHERE (purchase_method = 'cod' OR purchase_method = '' OR purchase_method IS NULL) 
      AND (order_status IN ('pending', 'processing', 'shipped') OR order_status IS NULL)
");
$all_active_cod = $all_active_cod_rs ? (int)$all_active_cod_rs->fetch_assoc()['total'] : 0;

// 3. Today's Store Pickup Orders (and Ready for Pickup)
$today_pickup_rs = Database::search("
    SELECT COUNT(id) AS total FROM orders 
    WHERE purchase_method = 'pickup' 
      AND (pickup_date = CURDATE() OR DATE(created_at) = CURDATE())
      AND (order_status NOT IN ('collected', 'cancelled') OR order_status IS NULL)
");
$today_pickup_orders = $today_pickup_rs ? (int)$today_pickup_rs->fetch_assoc()['total'] : 0;

$ready_pickup_rs = Database::search("
    SELECT COUNT(id) AS total FROM orders 
    WHERE purchase_method = 'pickup' 
      AND order_status IN ('ready_for_pickup', 'ready')
");
$ready_for_pickup_count = $ready_pickup_rs ? (int)$ready_pickup_rs->fetch_assoc()['total'] : 0;

// 4. General Counts
$total_orders_rs = Database::search("SELECT COUNT(id) AS total FROM orders");
$total_orders = $total_orders_rs ? (int)$total_orders_rs->fetch_assoc()['total'] : 0;

$total_customers_rs = Database::search("SELECT COUNT(id) AS total FROM users WHERE is_admin = 0");
$total_customers = $total_customers_rs ? (int)$total_customers_rs->fetch_assoc()['total'] : 0;

// 5. Low Stock Alert (stock_quantity <= 5)
$low_stock_rs = Database::search("
    SELECT i.id, i.name, i.stock_quantity, i.unit, i.price, i.status, i.image_path, i.category_id,
           COALESCE(c.name, 'General') AS category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.stock_quantity <= 5
    ORDER BY i.stock_quantity ASC, i.name ASC
    LIMIT 8
");

// 6. Recent Orders Log
$recent_orders_rs = Database::search("
    SELECT o.id, o.order_number, u.first_name, u.last_name, o.customer_name, o.total_amount, o.order_status, o.created_at,
           o.purchase_method, o.payment_status, o.pickup_code, o.pickup_date, o.pickup_time_slot,
           o.street_address1, o.street_address2, o.town
    FROM orders o 
    INNER JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Operations Dashboard - Freshcomart</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/custom-ui.css">
    
    <style>
        .main-container {
            max-width: 1340px;
            margin: 28px auto 40px auto;
            padding: 0 20px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 22px;
            border: 1px solid #f1f5f9;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        .stat-icon {
            font-size: 1.8rem;
            padding: 10px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
        }
        .icon-revenue { background: #dcfce7; color: #15803d; }
        .icon-unpaid  { background: #fee2e2; color: #b91c1c; }
        .icon-cod     { background: #e0f2fe; color: #0369a1; }
        .icon-pickup  { background: #fef3c7; color: #b45309; }
        
        .section-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 24px;
            margin-top: 24px;
            border: 1px solid #f1f5f9;
        }
        .fc-code-badge {
            font-family: monospace;
            font-weight: 800;
            font-size: 0.8rem;
            background: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px dashed #f59e0b;
        }
    </style>
</head>
<body class="fc-admin">
    <?php include 'navbar.php'; ?>

    <div class="main-container">
        <!-- Welcome header -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1" style="color: #0f172a; font-size:1.65rem;">Operations Dashboard</h1>
                <p class="text-muted mb-0">Supermarket order fulfillment, cash settlement, and stock tracking.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="order.php" class="btn btn-success action-btn shadow-sm rounded-pill px-3 py-2 fw-semibold">
                    <i class="bi bi-basket me-1"></i>Active Orders (<?php echo $all_active_cod + $today_pickup_orders; ?>)
                </a>
                <a href="ItemAdd.php" class="btn btn-outline-primary action-btn shadow-sm rounded-pill px-3 py-2 fw-semibold">
                    <i class="bi bi-plus-lg me-1"></i>Add Product
                </a>
            </div>
        </div>

        <!-- KPI Cards Grid (4 Operation-Critical Metrics) -->
        <div class="row g-3 mb-4">
            <!-- KPI 1: Today's Pending COD Orders -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Today's Pending COD</span>
                        <div class="stat-icon icon-cod"><i class="bi bi-truck"></i></div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"><?php echo $today_pending_cod; ?></h2>
                    <div class="small text-muted">
                        Total active COD: <span class="fw-bold text-primary"><?php echo $all_active_cod; ?></span>
                    </div>
                </div>
            </div>

            <!-- KPI 2: Today's Store Pickups & Ready for Pickup -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Today's Store Pickups</span>
                        <div class="stat-icon icon-pickup"><i class="bi bi-shop"></i></div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"><?php echo $today_pickup_orders; ?></h2>
                    <div class="small">
                        <span class="badge bg-warning text-dark"><i class="bi bi-box-seam me-1"></i><?php echo $ready_for_pickup_count; ?> Ready for Pickup</span>
                    </div>
                </div>
            </div>

            <!-- KPI 3: Total Revenue (Paid Orders) -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Total Revenue (Paid)</span>
                        <div class="stat-icon icon-revenue"><i class="bi bi-cash-stack"></i></div>
                    </div>
                    <h2 class="fw-bold text-success mb-1">Rs. <?php echo number_format($paid_revenue, 2); ?></h2>
                    <div class="small text-muted">Confirmed collected cash &amp; settlements</div>
                </div>
            </div>

            <!-- KPI 4: Pending Collection (Unpaid COD / Pickup) -->
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Pending Collection</span>
                        <div class="stat-icon icon-unpaid"><i class="bi bi-exclamation-octagon"></i></div>
                    </div>
                    <h2 class="fw-bold text-danger mb-1">Rs. <?php echo number_format($pending_collection, 2); ?></h2>
                    <div class="small text-muted">Uncollected COD cash &amp; counter dues</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left: Low Stock / Out of Stock Alert Table -->
            <div class="col-lg-5">
                <div class="section-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Stock Level Alerts
                            </h5>
                            <small class="text-muted">Items with quantity &le; 5 units needing replenishment</small>
                        </div>
                        <a href="Item.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">View All Stock</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table fc-admin-table align-middle table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($low_stock_rs && $low_stock_rs->num_rows > 0) {
                                    while ($it = $low_stock_rs->fetch_assoc()) {
                                        $stockQty = (float)$it['stock_quantity'];
                                        $isOos = ($stockQty <= 0);
                                        $badgeCls = $isOos ? 'bg-danger text-white' : 'bg-warning text-dark';
                                        $badgeText = $isOos ? 'Out of Stock (0)' : number_format($stockQty, 0) . ' ' . htmlspecialchars($it['unit'] ?: 'units');
                                        ?>
                                        <tr>
                                            <td class="fw-semibold text-truncate" style="max-width: 140px;" title="<?php echo htmlspecialchars($it['name']); ?>">
                                                <?php echo htmlspecialchars($it['name']); ?>
                                            </td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($it['category_name']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $badgeCls; ?> rounded-pill px-2 py-1"><?php echo $badgeText; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <a href="Item.php?search=<?php echo urlencode($it['name']); ?>" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2 small">
                                                    Restock
                                                </a>
                                            </td>
                                        </tr>
                                    <?php }
                                } else { ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-success">
                                            <i class="bi bi-check-circle-fill fs-4 d-block mb-1"></i>All inventory levels are healthy!
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Recent Orders Activity Log -->
            <div class="col-lg-7">
                <div class="section-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-clock-history text-primary me-2"></i>Recent Orders Activity
                            </h5>
                            <small class="text-muted">Live incoming customer orders &amp; status</small>
                        </div>
                        <a href="order.php" class="btn btn-outline-success btn-sm rounded-pill px-3">Manage Active</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table fc-admin-table align-middle table-hover">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_orders_rs && $recent_orders_rs->num_rows > 0) {
                                    while ($order = $recent_orders_rs->fetch_assoc()) {
                                        $status = $order['order_status'] ? $order['order_status'] : 'pending';
                                        $customerName = !empty(trim($order['customer_name'] ?? '')) ? $order['customer_name'] : ($order['first_name'] . ' ' . $order['last_name']);
                                        $order_no = htmlspecialchars($order['order_number'] ?: str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT));
                                        $isPickup = (fc_order_method($order['purchase_method'] ?? 'cod') === 'pickup');
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-nowrap">#<?php echo $order_no; ?></td>
                                            <td class="small fw-semibold"><?php echo htmlspecialchars($customerName); ?></td>
                                            <td>
                                                <?php echo fc_method_badge($order['purchase_method'] ?? 'cod'); ?>
                                                <?php if ($isPickup && !empty($order['pickup_code'])) { ?>
                                                    <div class="mt-1"><span class="fc-code-badge"><?php echo htmlspecialchars($order['pickup_code']); ?></span></div>
                                                <?php } ?>
                                            </td>
                                            <td class="fw-bold text-dark text-end text-nowrap">Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                            <td><?php echo fc_payment_badge($order['payment_status'] ?? 'unpaid'); ?></td>
                                            <td><?php echo fc_status_badge($status, $order['purchase_method'] ?? 'cod'); ?></td>
                                            <td class="text-center">
                                                <a href="order.php" class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-0">View</a>
                                            </td>
                                        </tr>
                                    <?php }
                                } else { ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No recent orders.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
