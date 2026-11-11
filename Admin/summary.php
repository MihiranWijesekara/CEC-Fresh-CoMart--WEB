<?php
require 'adminAuth.php';
require '../connection.php';
require 'orderHelpers.php';

// 1. Fetch categories dynamically from seeded database table
$categories = [];
$catRes = Database::search("SELECT id, name FROM categories ORDER BY id ASC");
if ($catRes && $catRes->num_rows > 0) {
    while ($c = $catRes->fetch_assoc()) {
        $categories[(int)$c['id']] = $c['name'];
    }
} else {
    // Fallback if table empty
    $categories = [
        1 => "Vegetables", 2 => "Fruits", 3 => "Snacks", 4 => "Biscuits", 5 => "Coffee",
        6 => "Eggs", 7 => "Water", 8 => "Tea", 9 => "Cheese", 10 => "Yoghurts & Curd",
        11 => "Desserts", 12 => "Beverages", 13 => "Bakery", 14 => "Meat & Seafood",
        15 => "Pantry Staples", 16 => "Dairy Staples", 17 => "Frozen Foods",
        18 => "Household & Cleaning", 19 => "Personal Care & Baby Care"
    ];
}

$period = isset($_GET['period']) && in_array($_GET['period'], ['today', '7d', 'month', 'all'], true) ? $_GET['period'] : 'all';
$dateWhereO = "";
$periodLabel = "All Time";
if ($period === 'today') {
    $dateWhereO = " AND DATE(o.created_at) = CURDATE() ";
    $periodLabel = "Today (" . date('d M Y') . ")";
} elseif ($period === '7d') {
    $dateWhereO = " AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) ";
    $periodLabel = "Last 7 Days";
} elseif ($period === 'month') {
    $dateWhereO = " AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) ";
    $periodLabel = "This Month (" . date('F Y') . ")";
}

// 2. Sales by Category (excluding cancelled orders)
$category_sales_rs = Database::search("
    SELECT i.category_id, SUM(oi.subtotal) AS total_sales 
    FROM order_items oi 
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN items i ON oi.item_id = i.id 
    WHERE (o.order_status != 'cancelled' OR o.order_status IS NULL) $dateWhereO
    GROUP BY i.category_id
");
$cat_sales = [];
foreach ($categories as $id => $name) {
    $cat_sales[$id] = ['name' => $name, 'sales' => 0.00];
}
if ($category_sales_rs) {
    while ($row = $category_sales_rs->fetch_assoc()) {
        $cid = (int)$row['category_id'];
        if (isset($cat_sales[$cid])) {
            $cat_sales[$cid]['sales'] = (float)$row['total_sales'];
        }
    }
}

// Find max sales for progress bar width sizing
$max_sales = 0.01;
foreach ($cat_sales as $item) {
    if ($item['sales'] > $max_sales) {
        $max_sales = $item['sales'];
    }
}

// 3. Top Selling Products (excluding cancelled orders)
$top_products_rs = Database::search("
    SELECT i.name, i.unit, i.category_id, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_sales 
    FROM order_items oi 
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN items i ON oi.item_id = i.id 
    WHERE (o.order_status != 'cancelled' OR o.order_status IS NULL) $dateWhereO
    GROUP BY oi.item_id 
    ORDER BY total_qty DESC 
    LIMIT 6
");

// 4. Order Status Breakdown
$status_breakdown_rs = Database::search("
    SELECT COALESCE(o.order_status, 'pending') AS status, COUNT(o.id) AS total_count 
    FROM orders o
    WHERE 1=1 $dateWhereO
    GROUP BY o.order_status
");

// 5. Orders by purchase method & payment status breakdown
$method_rs = Database::search("
    SELECT 
        COALESCE(o.purchase_method, 'cod') AS p_method,
        o.payment_status,
        o.order_status,
        COUNT(o.id) AS total_count,
        COALESCE(SUM(o.total_amount), 0) AS total_value
    FROM orders o
    WHERE 1=1 $dateWhereO
    GROUP BY o.purchase_method, o.payment_status, o.order_status
");

$methodStats = [
    'cod' => ['count' => 0, 'sales' => 0.0, 'paid' => 0.0, 'unpaid' => 0.0],
    'pickup' => ['count' => 0, 'sales' => 0.0, 'paid' => 0.0, 'unpaid' => 0.0]
];

if ($method_rs) {
    while ($m = $method_rs->fetch_assoc()) {
        $mKey = fc_order_method($m['p_method']);
        $cnt = (int)$m['total_count'];
        $val = (float)$m['total_value'];
        $pStatus = strtolower(trim((string)$m['payment_status']));
        $oStatus = strtolower(trim((string)$m['order_status']));

        if ($oStatus !== 'cancelled') {
            $methodStats[$mKey]['count'] += $cnt;
            $methodStats[$mKey]['sales'] += $val;
            if ($pStatus === 'paid') {
                $methodStats[$mKey]['paid'] += $val;
            } else {
                $methodStats[$mKey]['unpaid'] += $val;
            }
        }
    }
}

// 6. Recent Customers
$recent_users_rs = Database::search("
    SELECT first_name, last_name, email, phone_number, created_at 
    FROM users 
    WHERE is_admin = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business &amp; Operations Summary - Freshcomart Admin</title>
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
      .summary-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid #f1f5f9;
      }
      .progress-bar-custom {
          background-color: #16a34a;
      }
    </style>
</head>
<body class="fc-admin">
    <?php include 'navbar.php'; ?>

    <div class="main-container">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
          <div>
              <h1 class="fw-bold mb-1" style="color: #0f172a; font-size:1.65rem;">Business Analytics &amp; Operations Summary</h1>
              <p class="text-muted mb-0">Showing data for: <strong class="text-success"><?php echo htmlspecialchars($periodLabel); ?></strong> &middot; Excludes cancelled orders from revenue totals.</p>
          </div>
          <div class="d-flex align-items-center flex-wrap gap-2">
              <div class="btn-group btn-group-sm" role="group" aria-label="Period Filter">
                  <a href="summary.php?period=today" class="btn <?php echo $period === 'today' ? 'btn-success fw-bold' : 'btn-outline-secondary'; ?>">Today</a>
                  <a href="summary.php?period=7d" class="btn <?php echo $period === '7d' ? 'btn-success fw-bold' : 'btn-outline-secondary'; ?>">Last 7 Days</a>
                  <a href="summary.php?period=month" class="btn <?php echo $period === 'month' ? 'btn-success fw-bold' : 'btn-outline-secondary'; ?>">This Month</a>
                  <a href="summary.php?period=all" class="btn <?php echo $period === 'all' ? 'btn-success fw-bold' : 'btn-outline-secondary'; ?>">All Time</a>
              </div>
              <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-2 fw-semibold">
                  <i class="bi bi-printer me-1"></i>Print Report
              </button>
              <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-semibold">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
              </a>
          </div>
      </div>
      
      <!-- Top Row: Purchasing Method Performance Cards -->
      <div class="row g-3 mb-4">
          <!-- COD Channel Card -->
          <div class="col-md-6">
              <div class="summary-card h-100">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                          <h5 class="fw-bold mb-0 text-dark">
                              <i class="bi bi-truck text-primary me-2"></i>Cash on Delivery (COD) Channel
                          </h5>
                          <small class="text-muted">Home deliveries with cash collection at doorstep</small>
                      </div>
                      <span class="badge bg-primary text-white rounded-pill px-3 py-2"><?php echo $methodStats['cod']['count']; ?> Orders</span>
                  </div>
                  <div class="row g-2 text-center mt-2">
                      <div class="col-4">
                          <div class="p-2 border rounded-3 bg-light">
                              <small class="text-muted d-block">Total Value</small>
                              <span class="fw-bold text-dark">Rs. <?php echo number_format($methodStats['cod']['sales'], 2); ?></span>
                          </div>
                      </div>
                      <div class="col-4">
                          <div class="p-2 border rounded-3" style="background:#f0fdf4;">
                              <small class="text-muted d-block">Collected (Paid)</small>
                              <span class="fw-bold text-success">Rs. <?php echo number_format($methodStats['cod']['paid'], 2); ?></span>
                          </div>
                      </div>
                      <div class="col-4">
                          <div class="p-2 border rounded-3" style="background:#fef2f2;">
                              <small class="text-muted d-block">In Transit (Unpaid)</small>
                              <span class="fw-bold text-danger">Rs. <?php echo number_format($methodStats['cod']['unpaid'], 2); ?></span>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Store Pickup Channel Card -->
          <div class="col-md-6">
              <div class="summary-card h-100">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                          <h5 class="fw-bold mb-0 text-dark">
                              <i class="bi bi-shop text-warning me-2"></i>In-Store Purchase / Pickup Channel
                          </h5>
                          <small class="text-muted">Orders collected &amp; paid at supermarket store counter</small>
                      </div>
                      <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><?php echo $methodStats['pickup']['count']; ?> Orders</span>
                  </div>
                  <div class="row g-2 text-center mt-2">
                      <div class="col-4">
                          <div class="p-2 border rounded-3 bg-light">
                              <small class="text-muted d-block">Total Value</small>
                              <span class="fw-bold text-dark">Rs. <?php echo number_format($methodStats['pickup']['sales'], 2); ?></span>
                          </div>
                      </div>
                      <div class="col-4">
                          <div class="p-2 border rounded-3" style="background:#f0fdf4;">
                              <small class="text-muted d-block">Collected (Paid)</small>
                              <span class="fw-bold text-success">Rs. <?php echo number_format($methodStats['pickup']['paid'], 2); ?></span>
                          </div>
                      </div>
                      <div class="col-4">
                          <div class="p-2 border rounded-3" style="background:#fef2f2;">
                              <small class="text-muted d-block">Awaiting Pickup</small>
                              <span class="fw-bold text-danger">Rs. <?php echo number_format($methodStats['pickup']['unpaid'], 2); ?></span>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="row">
          <!-- Left Column: Sales by Category & Order Status breakdown -->
          <div class="col-lg-6">
              <div class="summary-card">
                  <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-success me-2"></i>Sales by Supermarket Category</h5>
                  
                  <?php 
                  $hasCategorySales = false;
                  foreach ($cat_sales as $id => $data) {
                      if ($data['sales'] > 0) {
                          $hasCategorySales = true;
                          $percent = ($data['sales'] / $max_sales) * 100;
                          ?>
                          <div class="mb-3">
                              <div class="d-flex justify-content-between mb-1">
                                  <span class="fw-semibold text-dark small"><?php echo htmlspecialchars($data['name']); ?></span>
                                  <span class="fw-bold text-success small">Rs. <?php echo number_format($data['sales'], 2); ?></span>
                              </div>
                              <div class="progress" style="height: 8px; border-radius: 999px;">
                                  <div class="progress-bar progress-bar-custom" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                              </div>
                          </div>
                          <?php
                      }
                  }
                  if (!$hasCategorySales) {
                      echo "<p class='text-muted py-3 text-center'>No category sales recorded yet.</p>";
                  }
                  ?>
              </div>
              
              <div class="summary-card">
                  <h5 class="fw-bold mb-3"><i class="bi bi-activity text-primary me-2"></i>Order Status Distribution</h5>
                  <div class="row g-2">
                      <?php 
                      if ($status_breakdown_rs && $status_breakdown_rs->num_rows > 0) {
                          while ($s_row = $status_breakdown_rs->fetch_assoc()) {
                              $status_name = $s_row['status'] ? htmlspecialchars($s_row['status']) : 'pending';
                              $count = (int)$s_row['total_count'];
                              ?>
                              <div class="col-6 col-sm-4">
                                  <div class="p-3 border rounded-3 bg-light text-center">
                                      <p class="mb-1"><?php echo fc_status_badge($s_row['status'] ?: 'pending'); ?></p>
                                      <h3 class="fw-bold mb-0 text-dark"><?php echo $count; ?></h3>
                                  </div>
                              </div>
                              <?php
                          }
                      } else {
                          echo "<p class='text-muted py-2 text-center w-100'>No status details available.</p>";
                      }
                      ?>
                  </div>
              </div>
          </div>
          
          <!-- Right Column: Top Products & Recent Sign-ups -->
          <div class="col-lg-6">
              <div class="summary-card">
                  <h5 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Top Selling Supermarket Items</h5>
                  <div class="table-responsive">
                      <table class="table align-middle table-hover table-sm">
                          <thead class="table-light">
                              <tr>
                                  <th>Product</th>
                                  <th class="text-center">QTY Sold</th>
                                  <th class="text-end">Sales Value</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php 
                              if ($top_products_rs && $top_products_rs->num_rows > 0) {
                                  while ($row = $top_products_rs->fetch_assoc()) {
                                      ?>
                                      <tr>
                                          <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                                          <td class="text-center fw-bold">
                                              <?php echo htmlspecialchars((string)Database::formatQuantity($row['total_qty'], $row['unit'] ?? 'unit', $row['category_id'] ?? 0)); ?>
                                          </td>
                                          <td class="text-end text-success fw-bold">Rs. <?php echo number_format((float)$row['total_sales'], 2); ?></td>
                                      </tr>
                                      <?php
                                  }
                              } else {
                                  ?>
                                  <tr>
                                      <td colspan="3" class="text-center py-3 text-muted">No product sales logged yet.</td>
                                  </tr>
                                  <?php
                              }
                              ?>
                          </tbody>
                      </table>
                  </div>
              </div>
              
              <div class="summary-card">
                  <h5 class="fw-bold mb-3"><i class="bi bi-people-fill text-info me-2"></i>Recent Customers Joined</h5>
                  <div class="table-responsive">
                      <table class="table align-middle table-hover table-sm">
                          <thead class="table-light">
                              <tr>
                                  <th>Customer Name</th>
                                  <th>Phone / Email</th>
                                  <th>Joined</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php 
                              if ($recent_users_rs && $recent_users_rs->num_rows > 0) {
                                  while ($row = $recent_users_rs->fetch_assoc()) {
                                      ?>
                                      <tr>
                                          <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                          <td class="small">
                                              <?php echo htmlspecialchars($row['phone_number'] ?: $row['email']); ?>
                                          </td>
                                          <td class="text-muted small"><?php echo date("Y-m-d", strtotime($row['created_at'])); ?></td>
                                      </tr>
                                      <?php
                                  }
                              } else {
                                  ?>
                                  <tr>
                                      <td colspan="3" class="text-center py-3 text-muted">No customers registered yet.</td>
                                  </tr>
                                  <?php
                              }
                              ?>
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
