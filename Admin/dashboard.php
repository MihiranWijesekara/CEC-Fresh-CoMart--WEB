<?php
require 'adminAuth.php';
require '../connection.php';
require 'orderHelpers.php';

// ============================================================================
// 1. ALL-TIME & MONTHLY FINANCIAL METRICS
// ============================================================================
$paid_rev_rs = Database::search("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE payment_status = 'paid'");
$paid_revenue = $paid_rev_rs ? (float)$paid_rev_rs->fetch_assoc()['total'] : 0.00;

$month_rev_rs = Database::search("
    SELECT COALESCE(SUM(total_amount), 0) AS total 
    FROM orders 
    WHERE payment_status = 'paid' 
      AND YEAR(created_at) = YEAR(CURDATE()) 
      AND MONTH(created_at) = MONTH(CURDATE())
");
$month_paid_revenue = $month_rev_rs ? (float)$month_rev_rs->fetch_assoc()['total'] : 0.00;

$unpaid_col_rs = Database::search("
    SELECT 
        COALESCE(SUM(total_amount), 0) AS total,
        COALESCE(SUM(CASE WHEN purchase_method = 'pickup' THEN total_amount ELSE 0 END), 0) AS pickup_unpaid,
        COALESCE(SUM(CASE WHEN purchase_method != 'pickup' OR purchase_method IS NULL THEN total_amount ELSE 0 END), 0) AS cod_unpaid
    FROM orders 
    WHERE payment_status = 'unpaid' AND (order_status NOT IN ('cancelled') OR order_status IS NULL)
");
$unpaid_row = $unpaid_col_rs ? $unpaid_col_rs->fetch_assoc() : ['total' => 0, 'pickup_unpaid' => 0, 'cod_unpaid' => 0];
$pending_collection = (float)$unpaid_row['total'];
$pending_pickup_cash = (float)$unpaid_row['pickup_unpaid'];
$pending_cod_cash = (float)$unpaid_row['cod_unpaid'];

// ============================================================================
// 2. TODAY'S DAILY PULSE NUMBERS (Real-Time Daily Supermarket Metrics)
// ============================================================================
$today_pulse_rs = Database::search("
    SELECT 
        COUNT(id) AS today_total_orders,
        SUM(CASE WHEN purchase_method = 'pickup' THEN 1 ELSE 0 END) AS today_pickup_count,
        SUM(CASE WHEN purchase_method != 'pickup' OR purchase_method IS NULL THEN 1 ELSE 0 END) AS today_cod_count,
        SUM(CASE WHEN order_status IN ('delivered', 'collected') THEN 1 ELSE 0 END) AS today_completed_count,
        SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) AS today_cancelled_count,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS today_paid_revenue,
        COALESCE(SUM(CASE WHEN payment_status = 'unpaid' AND (order_status != 'cancelled' OR order_status IS NULL) THEN total_amount ELSE 0 END), 0) AS today_unpaid_amount
    FROM orders
    WHERE DATE(created_at) = CURDATE()
");
$today_pulse = $today_pulse_rs ? $today_pulse_rs->fetch_assoc() : [];
$today_total_orders    = (int)($today_pulse['today_total_orders'] ?? 0);
$today_pickup_count    = (int)($today_pulse['today_pickup_count'] ?? 0);
$today_cod_count       = (int)($today_pulse['today_cod_count'] ?? 0);
$today_completed_count = (int)($today_pulse['today_completed_count'] ?? 0);
$today_cancelled_count = (int)($today_pulse['today_cancelled_count'] ?? 0);
$today_paid_revenue    = (float)($today_pulse['today_paid_revenue'] ?? 0.00);
$today_unpaid_amount   = (float)($today_pulse['today_unpaid_amount'] ?? 0.00);

// ============================================================================
// 3. ACTIVE FULFILLMENT QUEUE METRICS (COD & STORE PICKUP)
// ============================================================================
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

$all_active_pickup_rs = Database::search("
    SELECT COUNT(id) AS total FROM orders 
    WHERE purchase_method = 'pickup' 
      AND (order_status NOT IN ('collected', 'delivered', 'cancelled') OR order_status IS NULL)
");
$all_active_pickup = $all_active_pickup_rs ? (int)$all_active_pickup_rs->fetch_assoc()['total'] : 0;

$ready_pickup_rs = Database::search("
    SELECT COUNT(id) AS total FROM orders 
    WHERE purchase_method = 'pickup' 
      AND order_status IN ('ready_for_pickup', 'ready')
");
$ready_for_pickup_count = $ready_pickup_rs ? (int)$ready_pickup_rs->fetch_assoc()['total'] : 0;

// ============================================================================
// 4. LAST 7 DAYS DAILY BREAKDOWN (Day-by-Day Order & Revenue Numbers)
// ============================================================================
$daily_7d_rs = Database::search("
    SELECT 
        DATE(created_at) AS order_date,
        COUNT(id) AS total_orders,
        SUM(CASE WHEN purchase_method = 'pickup' THEN 1 ELSE 0 END) AS pickup_orders,
        SUM(CASE WHEN purchase_method != 'pickup' OR purchase_method IS NULL THEN 1 ELSE 0 END) AS cod_orders,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS paid_rev,
        COALESCE(SUM(CASE WHEN payment_status = 'unpaid' AND (order_status != 'cancelled' OR order_status IS NULL) THEN total_amount ELSE 0 END), 0) AS unpaid_rev
    FROM orders
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY order_date DESC
");
$daily_map = [];
$max_daily_val = 1.0;
if ($daily_7d_rs) {
    while ($dr = $daily_7d_rs->fetch_assoc()) {
        $dKey = $dr['order_date'];
        $daily_map[$dKey] = $dr;
        $dayTotalVal = (float)$dr['paid_rev'] + (float)$dr['unpaid_rev'];
        if ($dayTotalVal > $max_daily_val) {
            $max_daily_val = $dayTotalVal;
        }
    }
}
$last_7_days = [];
for ($i = 0; $i < 7; $i++) {
    $dStr = date('Y-m-d', strtotime("-$i days"));
    $last_7_days[$dStr] = $daily_map[$dStr] ?? [
        'order_date'    => $dStr,
        'total_orders'  => 0,
        'pickup_orders' => 0,
        'cod_orders'    => 0,
        'paid_rev'      => 0.00,
        'unpaid_rev'    => 0.00
    ];
}

// ============================================================================
// 5. LOW STOCK ALERTS & RECENT ORDERS
// ============================================================================
$low_stock_rs = Database::search("
    SELECT i.id, i.name, i.stock_quantity, i.unit, i.price, i.status, i.image_path, i.category_id,
           COALESCE(c.name, 'General') AS category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.stock_quantity <= 5
    ORDER BY i.stock_quantity ASC, i.name ASC
    LIMIT 8
");

$recent_orders_rs = Database::search("
    SELECT o.id, o.order_number, u.first_name, u.last_name, o.customer_name, o.customer_phone,
           o.total_amount, o.order_status, o.created_at,
           o.purchase_method, o.payment_status, o.pickup_code, o.pickup_date, o.pickup_time_slot,
           o.street_address1, o.street_address2, o.town
    FROM orders o 
    INNER JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 8
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
            max-width: 1380px;
            margin: 24px auto 48px auto;
            padding: 0 20px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
            color: inherit;
        }
        .stat-icon {
            font-size: 1.6rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
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
            border: 1px solid #e2e8f0;
        }
        .fc-code-badge {
            font-family: monospace;
            font-weight: 800;
            font-size: 0.8rem;
            background: #fef3c7;
            color: #92400e;
            padding: 2px 7px;
            border-radius: 5px;
            border: 1px dashed #f59e0b;
        }
        /* Daily Pulse Banner */
        .daily-pulse-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        }
        .pulse-metric {
            border-right: 1px solid rgba(255, 255, 255, 0.12);
            padding-right: 18px;
        }
        .pulse-metric:last-child {
            border-right: none;
            padding-right: 0;
        }
        @media (max-width: 991px) {
            .pulse-metric { border-right: none; padding-right: 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 12px; }
        }
        /* Quick Pickup Lookup Box */
        .quick-lookup-bar {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 999px;
            padding: 4px 6px 4px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .quick-lookup-bar:focus-within {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }
        .quick-lookup-bar input {
            border: none;
            outline: none;
            font-size: 0.9rem;
            width: 280px;
            background: transparent;
        }
        @media (max-width: 576px) {
            .quick-lookup-bar input { width: 160px; }
        }
    </style>
</head>
<body class="fc-admin">
    <?php include 'navbar.php'; ?>

    <div class="main-container">
        <!-- Top Header + Instant Pickup Code / Order Lookup Bar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h1 class="fw-bold mb-1" style="color: #0f172a; font-size:1.65rem;">Supermarket Operations Center</h1>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calendar-check me-1 text-success"></i><?php echo date('l, d F Y'); ?> &middot; Real-time daily numbers, pickup counter verification, and COD dispatch.
                </p>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- Instant Pickup Counter / Order Lookup -->
                <form action="order.php" method="GET" class="quick-lookup-bar" title="Verify customer Pickup Code or search order">
                    <i class="bi bi-qr-code-scan text-warning fs-5"></i>
                    <input type="text" name="search" placeholder="Enter Pickup Code (PK-...) or Order #" required>
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">Find Order</button>
                </form>
                <a href="ItemAdd.php" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Add Product
                </a>
            </div>
        </div>

        <!-- TODAY'S DAILY PULSE STRIP (Immediate Daily Numbers Identification) -->
        <div class="daily-pulse-bar">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2" style="border-bottom: 1px solid rgba(255,255,255,0.12);">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success rounded-pill px-3 py-1 fw-bold"><i class="bi bi-broadcast me-1"></i>TODAY'S DAILY PULSE</span>
                    <span class="small text-white-50">Live numbers for <?php echo date('d M Y'); ?></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="order.php?filter=pickup" class="btn btn-sm btn-warning text-dark rounded-pill px-3 py-1 fw-bold">
                        <i class="bi bi-shop me-1"></i>Pickup Counter (<?php echo $ready_for_pickup_count; ?> Ready)
                    </a>
                    <a href="summary.php?period=today" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1">
                        <i class="bi bi-bar-chart-line me-1"></i>Today's Full Report
                    </a>
                </div>
            </div>
            <div class="row g-3 align-items-center">
                <div class="col-6 col-lg-3 pulse-metric">
                    <div class="small text-white-50 text-uppercase fw-semibold" style="font-size:0.75rem;">Today's Orders Placed</div>
                    <div class="d-flex align-items-baseline gap-2 mt-1">
                        <span class="fs-3 fw-extrabold text-white"><?php echo $today_total_orders; ?></span>
                        <span class="small text-white-50">
                            (<span class="text-info fw-bold"><?php echo $today_cod_count; ?> COD</span> &middot; 
                             <span class="text-warning fw-bold"><?php echo $today_pickup_count; ?> Pickup</span>)
                        </span>
                    </div>
                </div>
                <div class="col-6 col-lg-3 pulse-metric">
                    <div class="small text-white-50 text-uppercase fw-semibold" style="font-size:0.75rem;">Today's Collected Cash (Paid)</div>
                    <div class="fs-3 fw-extrabold text-success mt-1">Rs. <?php echo number_format($today_paid_revenue, 2); ?></div>
                    <div class="small text-white-50" style="font-size:0.75rem;">Settled from today's orders</div>
                </div>
                <div class="col-6 col-lg-3 pulse-metric">
                    <div class="small text-white-50 text-uppercase fw-semibold" style="font-size:0.75rem;">Today's Cash to Collect</div>
                    <div class="fs-3 fw-extrabold text-warning mt-1">Rs. <?php echo number_format($today_unpaid_amount, 2); ?></div>
                    <div class="small text-white-50" style="font-size:0.75rem;">Awaiting delivery / counter payment</div>
                </div>
                <div class="col-6 col-lg-3 pulse-metric">
                    <div class="small text-white-50 text-uppercase fw-semibold" style="font-size:0.75rem;">Today's Fulfillment Progress</div>
                    <div class="d-flex align-items-center gap-3 mt-1">
                        <div>
                            <span class="fs-4 fw-bold text-white"><?php echo $today_completed_count; ?></span>
                            <span class="small text-success d-block" style="font-size:0.73rem;"><i class="bi bi-check-circle me-1"></i>Completed</span>
                        </div>
                        <div>
                            <span class="fs-4 fw-bold text-white"><?php echo max(0, $today_total_orders - $today_completed_count - $today_cancelled_count); ?></span>
                            <span class="small text-info d-block" style="font-size:0.73rem;"><i class="bi bi-hourglass-split me-1"></i>In Progress</span>
                        </div>
                        <div>
                            <span class="fs-4 fw-bold text-white"><?php echo $today_cancelled_count; ?></span>
                            <span class="small text-danger d-block" style="font-size:0.73rem;"><i class="bi bi-x-circle me-1"></i>Cancelled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Clickable Operation KPI Cards -->
        <div class="row g-3 mb-4">
            <!-- KPI 1: Active COD Deliveries -->
            <div class="col-md-6 col-xl-3">
                <a href="order.php?filter=cod" class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 0.76rem; letter-spacing: 0.5px;">Active COD Deliveries</span>
                        <div class="stat-icon icon-cod"><i class="bi bi-truck"></i></div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"><?php echo $all_active_cod; ?></h2>
                    <div class="d-flex justify-content-between align-items-center small text-muted">
                        <span>Placed Today: <strong class="text-primary"><?php echo $today_pending_cod; ?></strong></span>
                        <span class="text-primary fw-semibold">Manage COD &rarr;</span>
                    </div>
                </a>
            </div>

            <!-- KPI 2: Store Pickup Queue -->
            <div class="col-md-6 col-xl-3">
                <a href="order.php?filter=pickup" class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 0.76rem; letter-spacing: 0.5px;">Active Store Pickups</span>
                        <div class="stat-icon icon-pickup"><i class="bi bi-shop"></i></div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1"><?php echo $all_active_pickup; ?></h2>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="badge bg-warning text-dark"><i class="bi bi-box-seam me-1"></i><?php echo $ready_for_pickup_count; ?> Ready at Counter</span>
                        <span class="text-warning-emphasis fw-semibold">Open Queue &rarr;</span>
                    </div>
                </a>
            </div>

            <!-- KPI 3: Total Revenue (Paid Orders) -->
            <div class="col-md-6 col-xl-3">
                <a href="summary.php" class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 0.76rem; letter-spacing: 0.5px;">Total Revenue (Paid)</span>
                        <div class="stat-icon icon-revenue"><i class="bi bi-cash-stack"></i></div>
                    </div>
                    <h2 class="fw-bold text-success mb-1">Rs. <?php echo number_format($paid_revenue, 2); ?></h2>
                    <div class="small text-muted">
                        This Month: <strong class="text-dark">Rs. <?php echo number_format($month_paid_revenue, 2); ?></strong>
                    </div>
                </a>
            </div>

            <!-- KPI 4: Pending Cash Collection -->
            <div class="col-md-6 col-xl-3">
                <a href="order.php" class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase fw-bold" style="font-size: 0.76rem; letter-spacing: 0.5px;">Pending Cash to Collect</span>
                        <div class="stat-icon icon-unpaid"><i class="bi bi-wallet2"></i></div>
                    </div>
                    <h2 class="fw-bold text-danger mb-1">Rs. <?php echo number_format($pending_collection, 2); ?></h2>
                    <div class="small text-muted" style="font-size:0.78rem;">
                        COD: <strong>Rs. <?php echo number_format($pending_cod_cash, 0); ?></strong> &middot;
                        Pickup: <strong>Rs. <?php echo number_format($pending_pickup_cash, 0); ?></strong>
                    </div>
                </a>
            </div>
        </div>

        <!-- 7-Day Daily Numbers Breakdown (Easy Daily Identification) -->
        <div class="section-card mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-calendar3-week text-success me-2"></i>Last 7 Days – Daily Sales &amp; Order Numbers
                    </h5>
                    <small class="text-muted">Day-by-day comparison of orders placed, COD vs Store Pickup split, collected cash, and pending collection</small>
                </div>
                <a href="summary.php" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">Full Analytics &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table fc-admin-table align-middle table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date / Day</th>
                            <th class="text-center">Total Orders</th>
                            <th class="text-center">Method Split</th>
                            <th class="text-end">Collected (Paid)</th>
                            <th class="text-end">Pending (Unpaid)</th>
                            <th style="width: 22%;">Daily Volume</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($last_7_days as $dKey => $dRow):
                            $isToday = ($dKey === date('Y-m-d'));
                            $isYesterday = ($dKey === date('Y-m-d', strtotime('-1 day')));
                            $dayLabel = $isToday ? 'Today (' . date('d M', strtotime($dKey)) . ')' : ($isYesterday ? 'Yesterday (' . date('d M', strtotime($dKey)) . ')' : date('D, d M Y', strtotime($dKey)));
                            $dayPaid = (float)$dRow['paid_rev'];
                            $dayUnpaid = (float)$dRow['unpaid_rev'];
                            $dayTotalVal = $dayPaid + $dayUnpaid;
                            $barPct = $max_daily_val > 0 ? min(100, round(($dayTotalVal / $max_daily_val) * 100)) : 0;
                        ?>
                            <tr class="<?php echo $isToday ? 'table-success bg-opacity-10' : ''; ?>">
                                <td class="fw-semibold text-dark">
                                    <?php echo htmlspecialchars($dayLabel); ?>
                                    <?php if ($isToday): ?><span class="badge bg-success ms-1" style="font-size:0.65rem;">LIVE</span><?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?php echo (int)$dRow['total_orders']; ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2"><?php echo (int)$dRow['cod_orders']; ?> COD</span>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 ms-1"><?php echo (int)$dRow['pickup_orders']; ?> Pickup</span>
                                </td>
                                <td class="text-end fw-bold text-success">Rs. <?php echo number_format($dayPaid, 2); ?></td>
                                <td class="text-end fw-semibold <?php echo $dayUnpaid > 0 ? 'text-danger' : 'text-muted'; ?>">Rs. <?php echo number_format($dayUnpaid, 2); ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px; border-radius: 99px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $barPct; ?>%"></div>
                                        </div>
                                        <span class="small text-muted fw-semibold" style="min-width: 75px; text-align:right;">Rs. <?php echo number_format($dayTotalVal, 0); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                        <a href="Item.php?stock=low" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Filter Low Stock</a>
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

            <!-- Right: Recent Orders Activity Log with 1-Click Quick Fulfillment -->
            <div class="col-lg-7">
                <div class="section-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-clock-history text-primary me-2"></i>Recent Orders &amp; Quick Fulfillment
                            </h5>
                            <small class="text-muted">Advance order status in 1 click or open in Active Orders</small>
                        </div>
                        <a href="order.php" class="btn btn-outline-success btn-sm rounded-pill px-3">Manage All Active</a>
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
                                    <th class="text-center">Quick Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_orders_rs && $recent_orders_rs->num_rows > 0) {
                                    while ($order = $recent_orders_rs->fetch_assoc()) {
                                        $oid = (int)$order['id'];
                                        $status = $order['order_status'] ? $order['order_status'] : 'pending';
                                        $customerName = !empty(trim($order['customer_name'] ?? '')) ? $order['customer_name'] : ($order['first_name'] . ' ' . $order['last_name']);
                                        $order_no = htmlspecialchars($order['order_number'] ?: str_pad((string)$oid, 5, '0', STR_PAD_LEFT));
                                        $method = fc_order_method($order['purchase_method'] ?? 'cod');
                                        $isPickup = ($method === 'pickup');
                                        $next = fc_next_action($status, $method);
                                        $searchTarget = !empty($order['pickup_code']) ? $order['pickup_code'] : ($order['order_number'] ?: $oid);
                                        ?>
                                        <tr id="dash-order-<?php echo $oid; ?>">
                                            <td>
                                                <div class="fw-bold text-nowrap">#<?php echo $order_no; ?></div>
                                                <div class="text-muted" style="font-size:0.72rem;"><?php echo date('d M H:i', strtotime($order['created_at'])); ?></div>
                                            </td>
                                            <td class="small fw-semibold"><?php echo htmlspecialchars($customerName); ?></td>
                                            <td>
                                                <?php echo fc_method_badge($method); ?>
                                                <?php if ($isPickup && !empty($order['pickup_code'])) { ?>
                                                    <div class="mt-1"><span class="fc-code-badge"><?php echo htmlspecialchars($order['pickup_code']); ?></span></div>
                                                <?php } ?>
                                            </td>
                                            <td class="fw-bold text-dark text-end text-nowrap">Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                            <td><?php echo fc_payment_badge($order['payment_status'] ?? 'unpaid'); ?></td>
                                            <td><?php echo fc_status_badge($status, $method); ?></td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                    <?php if ($next): ?>
                                                        <button type="button" class="btn btn-sm btn-success rounded-pill px-2 py-0 fw-semibold" style="font-size:0.76rem;"
                                                                onclick="quickAdvanceOrder(<?php echo $oid; ?>, '<?php echo $next[0]; ?>', '<?php echo htmlspecialchars($next[1]); ?>')">
                                                            <i class="bi <?php echo $next[3]; ?>"></i><?php echo htmlspecialchars($next[1]); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="<?php echo in_array($status, ['delivered', 'collected', 'cancelled'], true) ? 'completOrder.php' : 'order.php'; ?>?search=<?php echo urlencode((string)$searchTarget); ?>"
                                                       class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-0" style="font-size:0.76rem;">
                                                        Open
                                                    </a>
                                                </div>
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
    <script>
        function quickAdvanceOrder(orderId, nextStatus, actionLabel) {
            if (!confirm('Perform action "' + actionLabel + '" on Order #' + orderId + '?')) {
                return;
            }
            var f = new FormData();
            f.append('order_id', orderId);
            f.append('status', nextStatus);

            fetch('updateOrderStatus.php', {
                method: 'POST',
                body: f,
                credentials: 'same-origin'
            })
            .then(function (r) { return r.text(); })
            .then(function (resp) {
                if (resp.trim() === 'success') {
                    location.reload();
                } else {
                    alert('Error updating order: ' + resp);
                }
            })
            .catch(function (err) {
                alert('Network error: ' + err);
            });
        }
    </script>
</body>
</html>
