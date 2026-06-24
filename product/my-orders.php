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
    <!-- Google Fonts - Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
    
    <style>
      :root {
        --primary-green: #27b62e;
        --primary-hover: #1e8e24;
        --bg-gray: #f8fafc;
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --border-color: #f1f5f9;
        --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.03), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
      }

      body {
        background-color: var(--bg-gray);
        font-family: 'Outfit', sans-serif;
        color: var(--text-dark);
      }

      .navbar-container {
        margin-bottom: 30px;
      }

      .orders-section {
        margin-top: 20px;
        margin-bottom: 80px;
      }

      .orders-container {
        background: white;
        border-radius: 20px;
        padding: 35px;
        box-shadow: var(--card-shadow);
        border: 1px solid var(--border-color);
      }

      .orders-header {
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 30px;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 12px;
      }
      .orders-header i {
        color: var(--primary-green);
      }

      .table-wrapper {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border-color);
      }

      .table {
        margin-bottom: 0;
      }
      .table thead th {
        background-color: #f8fafc;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
      }

      .table tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s;
      }
      .table tbody tr:hover {
        background-color: #fafbfc;
      }

      .table td {
        padding: 18px 16px;
        vertical-align: middle;
        font-size: 0.95rem;
      }

      .badge-status {
        padding: 6px 14px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
      }
      .status-pending { background-color: #fef3c7; color: #d97706; }
      .status-processing { background-color: #e0f2fe; color: #0284c7; }
      .status-shipped { background-color: #e0e7ff; color: #4f46e5; }
      .status-delivered { background-color: #dcfce7; color: #16a34a; }
      .status-cancelled { background-color: #fee2e2; color: #dc2626; }
      
      .btn-expand {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid var(--border-color);
        background: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        transition: all 0.2s ease-in-out;
        cursor: pointer;
      }
      .btn-expand:hover {
        background-color: var(--primary-green);
        color: white;
        border-color: var(--primary-green);
        box-shadow: 0 4px 10px rgba(39, 182, 46, 0.2);
      }
      .btn-expand[aria-expanded="true"] {
        transform: rotate(180deg);
        background-color: var(--primary-green);
        color: white;
        border-color: var(--primary-green);
      }
      
      /* Expandable Details */
      .collapse-details {
        max-height: 0;
        overflow: hidden;
        visibility: hidden;
        opacity: 0;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease, visibility 0.4s;
        background-color: #fafbfc;
      }
      .collapse-details.show {
        max-height: 2000px;
        visibility: visible;
        opacity: 1;
        border-bottom: 1px solid var(--border-color);
      }

      .details-content {
        padding: 24px 30px;
      }

      .receipt-card {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.01);
        overflow: hidden;
      }

      .receipt-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        background-color: #fcfdfe;
      }

      .product-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        transition: transform 0.2s;
      }
      .product-img:hover {
        transform: scale(1.1);
      }

      .empty-state {
        text-align: center;
        padding: 60px 20px;
      }
      .empty-icon {
        font-size: 4.5rem;
        color: #cbd5e1;
        margin-bottom: 24px;
        display: inline-block;
        animation: float 3s ease-in-out infinite;
      }
      @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
      }
      
      .btn-shop {
        background-color: var(--primary-green);
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 1rem;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(39, 182, 46, 0.2);
      }
      .btn-shop:hover {
        background-color: var(--primary-hover);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(39, 182, 46, 0.3);
      }
      
      footer {
        background-color: #0f172a;
        color: #94a3b8;
        text-align: center;
        padding: 24px 0;
        font-size: 0.9rem;
        border-top: 1px solid #1e293b;
        margin-top: 100px;
      }
    </style>
  </head>
  <body>
    <!-- Navbar Placeholder -->
    <div id="navbar" class="navbar-container"></div>
    
    <div class="container orders-section">
      <div class="orders-container">
        <h2 class="orders-header">
          <i class="bi bi-bag-check-fill"></i>My Order History
        </h2>
        
        <?php if (!empty($orders)) { ?>
          <div class="table-wrapper">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 70px;" class="text-center">Items</th>
                    <th class="text-center">Order No</th>
                    <th>Date & Time</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-center">Status</th>
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
                      $status_icon = 'bi-clock';
                      if ($status === 'pending') {
                          $status_label = 'Order Placed';
                          $status_icon = 'bi-check2-circle';
                      } else if ($status === 'processing') {
                          $status_label = 'Preparing';
                          $status_icon = 'bi-egg-fried';
                      } else if ($status === 'shipped') {
                          $status_label = 'Dispatched';
                          $status_icon = 'bi-truck';
                      } else if ($status === 'delivered') {
                          $status_label = 'Delivered';
                          $status_icon = 'bi-house-check';
                      } else if ($status === 'cancelled') {
                          $status_label = 'Cancelled';
                          $status_icon = 'bi-x-circle';
                      }
                  ?>
                    <!-- Main Order Row -->
                    <tr>
                      <td class="text-center">
                        <button class="btn-expand" type="button" onclick="toggleOrderDetails(<?php echo $order_id; ?>, this)" aria-expanded="false">
                          <i class="bi bi-chevron-down"></i>
                        </button>
                      </td>
                      <td class="fw-bold text-center text-secondary">#<?php echo $order_no; ?></td>
                      <td class="text-muted"><?php echo $date; ?></td>
                      <td class="fw-bold text-end text-dark">Rs. <?php echo number_format($total, 2); ?></td>
                      <td class="text-center">
                        <span class="badge-status <?php echo $statusClass; ?>">
                          <i class="bi <?php echo $status_icon; ?>"></i>
                          <?php echo $status_label; ?>
                        </span>
                      </td>
                    </tr>
                    
                    <!-- Collapsible Item Details Row -->
                    <tr class="border-0">
                      <td colspan="5" class="p-0 border-0">
                        <div id="order-items-<?php echo $order_id; ?>" class="collapse-details">
                          <div class="details-content">
                            <div class="receipt-card">
                              <div class="receipt-header">
                                <h6 class="fw-bold text-secondary mb-0">
                                  <i class="bi bi-receipt me-2"></i>Items in this Order
                                </h6>
                              </div>
                              <div class="table-responsive">
                                <table class="table align-middle mb-0" style="font-size: 0.92rem;">
                                  <thead class="table-light text-secondary">
                                    <tr>
                                      <th scope="col" style="width: 90px;" class="py-2 text-center">Image</th>
                                      <th scope="col" class="py-2">Product Name</th>
                                      <th scope="col" class="py-2 text-end">Price</th>
                                      <th scope="col" class="py-2 text-center">Quantity</th>
                                      <th scope="col" class="py-2 text-end">Subtotal</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($order['items'] as $item) { ?>
                                      <tr>
                                        <td class="text-center py-2">
                                          <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Product" class="product-img" />
                                        </td>
                                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="text-end">Rs. <?php echo number_format($item['item_price'], 2); ?></td>
                                        <td class="text-center fw-semibold"><?php echo $item['quantity']; ?></td>
                                        <td class="fw-bold text-dark text-end">Rs. <?php echo number_format($item['item_subtotal'], 2); ?></td>
                                      </tr>
                                    <?php } ?>
                                    <tr class="table-light text-secondary">
                                      <td colspan="4" class="text-end fw-semibold py-2">Delivery Fee:</td>
                                      <td class="text-end fw-semibold text-dark">Rs. <?php echo number_format($order['delivery_fee'], 2); ?></td>
                                    </tr>
                                    <tr class="fw-bold text-success" style="background-color: #f8fafc;">
                                      <td colspan="4" class="text-end py-3">Grand Total:</td>
                                      <td class="text-end py-3 text-success" style="font-size: 1.05rem;">Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php } else { ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-cart-x"></i></div>
            <h4 class="fw-bold">No Orders Found</h4>
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
