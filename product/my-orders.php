<?php
require '../connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/sign.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Query customer orders
$orders_rs = Database::search("
    SELECT o.id AS order_id, o.order_number, o.created_at, o.order_status, o.total_amount, o.delivery_fee,
           oi.quantity, oi.price AS item_price, oi.subtotal AS item_subtotal,
           i.name AS product_name, i.image_path
    FROM orders o
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN items i ON oi.item_id = i.id
    WHERE o.user_id = '$user_id'
    ORDER BY o.created_at DESC
");

// Group orders by order_id in PHP
$orders = [];
if ($orders_rs && $orders_rs->num_rows > 0) {
    while ($row = $orders_rs->fetch_assoc()) {
        $oid = $row['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'order_id' => $row['order_id'],
                'order_number' => $row['order_number'],
                'created_at' => $row['created_at'],
                'order_status' => $row['order_status'],
                'total_amount' => $row['total_amount'],
                'delivery_fee' => $row['delivery_fee'],
                'items' => []
            ];
        }
        $orders[$oid]['items'][] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'item_price' => $row['item_price'],
            'item_subtotal' => $row['item_subtotal'],
            'image_path' => $row['image_path']
        ];
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Orders - CEC Fresh-Co Mart</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
    
    <style>
      body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      .orders-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-top: 40px;
        margin-bottom: 80px;
      }
      .orders-header {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 25px;
        color: #2e7d32;
        border-bottom: 2px solid #e8f5e9;
        padding-bottom: 15px;
      }
      .table thead th {
        background-color: #e8f5e9;
        color: #2e7d32;
        padding: 14px;
        font-weight: 600;
        text-align: center;
        border: none;
      }
      .table tbody tr {
        border-bottom: 1px solid #f1f3f7;
      }
      .table td {
        padding: 16px;
        vertical-align: middle;
        text-align: center;
      }
      .badge-status {
          padding: 6px 12px;
          border-radius: 20px;
          font-weight: 600;
          font-size: 0.85rem;
          text-transform: capitalize;
          display: inline-block;
      }
      .status-pending { background-color: #ffebee; color: #c62828; }
      .status-processing { background-color: #fff3e0; color: #ef6c00; }
      .status-shipped { background-color: #e3f2fd; color: #1565c0; }
      .status-delivered { background-color: #e8f5e9; color: #2e7d32; }
      
      .btn-expand {
          font-size: 1.25rem;
          color: #2e7d32;
          background: none;
          border: none;
          padding: 4px 10px;
          transition: transform 0.2s;
          cursor: pointer;
      }
      .btn-expand[aria-expanded="true"] {
          transform: rotate(180deg);
      }
      
      .empty-state {
          text-align: center;
          padding: 50px 20px;
      }
      .empty-icon {
          font-size: 4rem;
          color: #a5d6a7;
          margin-bottom: 20px;
      }
      
      .btn-shop {
          background-color: #27b62e;
          color: white;
          border: none;
          padding: 10px 24px;
          border-radius: 25px;
          font-weight: 600;
          text-decoration: none;
          transition: all 0.3s;
      }
      .btn-shop:hover {
          background-color: #1e8e24;
          color: white;
      }
      
      footer {
        background-color: #212529;
        color: white;
        text-align: center;
        padding: 15px 0;
        margin-top: 150px;
      }
    </style>
  </head>
  <body>
    <!-- Navbar placeholder -->
    <div id="navbar" class="navbar-container"></div>
    
    <div class="container">
      <div class="orders-container">
        <h2 class="orders-header"><i class="bi bi-bag-check-fill me-2"></i>My Order History</h2>
        
        <?php if (!empty($orders)) { ?>
          <div class="table-responsive">
            <table class="table align-middle text-center mb-0">
              <thead>
                <tr>
                  <th style="width: 50px;"></th>
                  <th>Order No</th>
                  <th>Date & Time</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order_id => $order) {
                    $order_no = htmlspecialchars($order['order_number'] ?: str_pad($order_id, 5, '0', STR_PAD_LEFT));
                    $date = date("F d, Y h:i A", strtotime($order['created_at']));
                    $total = (float)$order['total_amount'];
                    $status = $order['order_status'] ?: 'pending';
                    $statusClass = 'status-' . $status;
                    
                    // Display status labels nicely
                    $status_label = $status;
                    if ($status === 'pending') $status_label = 'Order Placed';
                    else if ($status === 'processing') $status_label = 'Preparing';
                    else if ($status === 'shipped') $status_label = 'Dispatched';
                    else if ($status === 'delivered') $status_label = 'Delivered';
                ?>
                  <!-- Main Order Row -->
                  <tr>
                    <td>
                      <button class="btn-expand" type="button" onclick="toggleOrderDetails(<?php echo $order_id; ?>, this)" aria-expanded="false">
                        <i class="bi bi-chevron-down"></i>
                      </button>
                    </td>
                    <td class="fw-bold text-dark">#<?php echo $order_no; ?></td>
                    <td class="text-muted"><?php echo $date; ?></td>
                    <td class="fw-bold">Rs. <?php echo number_format($total, 2); ?></td>
                    <td>
                      <span class="badge-status <?php echo $statusClass; ?>">
                        <?php echo $status_label; ?>
                      </span>
                    </td>
                  </tr>
                  
                  <!-- Collapsible Item Details Row -->
                  <tr class="border-0">
                    <td colspan="5" class="p-0 border-0">
                      <div id="order-items-<?php echo $order_id; ?>" class="collapse" style="background-color: #fafbfc;">
                        <div class="p-4 border-start border-end border-bottom" style="border-color: #e3e6ed !important;">
                          <h6 class="fw-bold text-secondary mb-3 text-start">
                            <i class="bi bi-receipt me-2"></i>Items in this Order
                          </h6>
                          <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle text-center bg-white mb-0" style="font-size: 0.9rem; border-color: #e3e6ed;">
                              <thead class="table-light text-secondary">
                                <tr>
                                  <th scope="col" style="width: 80px;" class="py-2">Image</th>
                                  <th scope="col" class="py-2 text-start ps-3">Product Name</th>
                                  <th scope="col" class="py-2">Price</th>
                                  <th scope="col" class="py-2">Quantity</th>
                                  <th scope="col" class="py-2">Subtotal</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($order['items'] as $item) { ?>
                                  <tr>
                                    <td>
                                      <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Product" style="width: 40px; height: auto;" />
                                    </td>
                                    <td class="text-start fw-semibold ps-3 py-2"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td>Rs. <?php echo number_format($item['item_price'], 2); ?></td>
                                    <td class="fw-semibold"><?php echo $item['quantity']; ?></td>
                                    <td class="fw-bold text-dark">Rs. <?php echo number_format($item['item_subtotal'], 2); ?></td>
                                  </tr>
                                <?php } ?>
                                <tr class="table-light fw-semibold text-secondary">
                                  <td colspan="4" class="text-end pe-3 py-2">Delivery Fee:</td>
                                  <td class="text-dark">Rs. <?php echo number_format($order['delivery_fee'], 2); ?></td>
                                </tr>
                                <tr class="table-success fw-bold text-success">
                                  <td colspan="4" class="text-end pe-3 py-2" style="background-color: #e8f5e9;">Grand Total:</td>
                                  <td style="background-color: #e8f5e9;">Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php } else { ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-cart-x"></i></div>
            <h4>No Orders Found</h4>
            <p class="text-muted mb-4">You have not placed any orders with us yet.</p>
            <a href="product.php" class="btn-shop">Start Shopping</a>
          </div>
        <?php } ?>
      </div>
    </div>
    
    <!-- Footer -->
    <footer>© 2026 CEC Fresh-Co Mart. All Rights Reserved.</footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
      function toggleOrderDetails(orderId, button) {
        const detailsDiv = document.getElementById("order-items-" + orderId);
        if (!detailsDiv) return;
        
        const isExpanded = button.getAttribute("aria-expanded") === "true";
        if (isExpanded) {
          detailsDiv.classList.remove("show");
          button.setAttribute("aria-expanded", "false");
        } else {
          detailsDiv.classList.add("show");
          button.setAttribute("aria-expanded", "true");
        }
      }
      
      // Load standard user navigation
      fetch("nav.php")
        .then((response) => response.text())
        .then((data) => {
          document.getElementById("navbar").innerHTML = data;
          const script = document.createElement("script");
          script.src = "navBar.js";
          document.body.appendChild(script);
        });
    </script>
  </body>
</html>
