<?php
require 'adminAuth.php';
require '../connection.php';

// Category map
$categories = [
    1 => "Vegetables",
    2 => "Fruits",
    3 => "Snacks",
    4 => "Biscuits",
    5 => "Coffee",
    6 => "Eggs",
    7 => "Water",
    8 => "Tea",
    9 => "Cheese",
    10 => "Yoghurts & Curd",
    11 => "Desserts"
];

// 1. Sales by Category
$category_sales_rs = Database::search("
    SELECT i.category_id, SUM(oi.subtotal) AS total_sales 
    FROM order_items oi 
    INNER JOIN items i ON oi.item_id = i.id 
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

// 2. Top Selling Products
$top_products_rs = Database::search("
    SELECT i.name, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_sales 
    FROM order_items oi 
    INNER JOIN items i ON oi.item_id = i.id 
    GROUP BY oi.item_id 
    ORDER BY total_qty DESC 
    LIMIT 5
");

// 3. Recent Customers
$recent_users_rs = Database::search("
    SELECT first_name, last_name, email, phone_number, created_at 
    FROM users 
    WHERE is_admin = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");

// 4. Order Status Breakdown
$status_breakdown_rs = Database::search("
    SELECT COALESCE(order_status, 'pending') AS status, COUNT(id) AS total_count 
    FROM orders 
    GROUP BY order_status
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary - CEC COMART</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
      body {
        background: #f4f6fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      .main-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
      }
      .summary-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
        margin-bottom: 30px;
      }
      .progress-bar-custom {
          background-color: #27b62e;
      }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-container">
      <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
              <h1 class="fw-bold mb-1" style="color: #212529;">Business Summary</h1>
              <p class="text-muted">Analyze your categories, top-performing items, and customer activity logs.</p>
          </div>
      </div>
      
      <div class="row">
          <!-- Left Column: Sales by Category & Status breakdown -->
          <div class="col-lg-6">
              <div class="summary-card">
                  <h4 class="fw-bold mb-4"><i class="bi bi-pie-chart-fill text-success me-2"></i>Sales by Category</h4>
                  
                  <?php 
                  $hasCategorySales = false;
                  foreach ($cat_sales as $id => $data) {
                      if ($data['sales'] > 0) {
                          $hasCategorySales = true;
                          $percent = ($data['sales'] / $max_sales) * 100;
                          ?>
                          <div class="mb-3">
                              <div class="d-flex justify-content-between mb-1">
                                  <span class="fw-semibold text-dark"><?php echo htmlspecialchars($data['name']); ?></span>
                                  <span class="fw-bold text-success">Rs. <?php echo number_format($data['sales'], 2); ?></span>
                              </div>
                              <div class="progress" style="height: 10px; border-radius: 5px;">
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
                  <h4 class="fw-bold mb-4"><i class="bi bi-activity text-primary me-2"></i>Order Status Distribution</h4>
                  <div class="row text-center">
                      <?php 
                      if ($status_breakdown_rs && $status_breakdown_rs->num_rows > 0) {
                          while ($s_row = $status_breakdown_rs->fetch_assoc()) {
                              $status_name = $s_row['status'] ? htmlspecialchars($s_row['status']) : 'pending';
                              $count = (int)$s_row['total_count'];
                              ?>
                              <div class="col-6 mb-3">
                                  <div class="p-3 border rounded-3 bg-light">
                                      <p class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 0.8rem;"><?php echo $status_name; ?></p>
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
                  <h4 class="fw-bold mb-4"><i class="bi bi-star-fill text-warning me-2"></i>Top 5 Best Selling Products</h4>
                  <div class="table-responsive">
                      <table class="table align-middle table-hover">
                          <thead>
                              <tr style="border-bottom: 2px solid #e3e6ed;">
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
                                      <tr style="border-bottom: 1px solid #f1f3f7;">
                                          <td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                                          <td class="text-center fw-bold"><?php echo (int)$row['total_qty']; ?></td>
                                          <td class="text-end text-success fw-bold">Rs. <?php echo number_format((float)$row['total_sales'], 2); ?></td>
                                      </tr>
                                      <?php
                                  }
                              } else {
                                  ?>
                                  <tr>
                                      <td colspan="3" class="text-center py-3 text-muted">No product sales logged.</td>
                                  </tr>
                                  <?php
                              }
                              ?>
                          </tbody>
                      </table>
                  </div>
              </div>
              
              <div class="summary-card">
                  <h4 class="fw-bold mb-4"><i class="bi bi-people-fill text-info me-2"></i>Recent Customers Joined</h4>
                  <div class="table-responsive">
                      <table class="table align-middle table-hover">
                          <thead>
                              <tr style="border-bottom: 2px solid #e3e6ed;">
                                  <th>Name</th>
                                  <th>Email</th>
                                  <th>Date Joined</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php 
                              if ($recent_users_rs && $recent_users_rs->num_rows > 0) {
                                  while ($row = $recent_users_rs->fetch_assoc()) {
                                      ?>
                                      <tr style="border-bottom: 1px solid #f1f3f7;">
                                          <td class="fw-semibold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                          <td><?php echo htmlspecialchars($row['email']); ?></td>
                                          <td class="text-muted"><?php echo date("Y-m-d", strtotime($row['created_at'])); ?></td>
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
</body>
</html>