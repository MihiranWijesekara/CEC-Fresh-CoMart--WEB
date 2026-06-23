<?php
require 'adminAuth.php';
require '../connection.php';

// Query completed orders
$orders_rs = Database::search("
    SELECT o.id AS order_id, o.order_number, o.created_at, o.order_status, o.total_amount, o.delivery_fee,
           u.first_name, u.last_name, u.street_address1, u.street_address2, u.town, u.phone_number,
           oi.quantity, oi.price AS item_price, oi.subtotal AS item_subtotal,
           i.name AS product_name
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN items i ON oi.item_id = i.id
    WHERE o.order_status = 'delivered'
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
                'customer_name' => $row['first_name'] . ' ' . $row['last_name'],
                'address' => $row['street_address1'] . ", " . $row['street_address2'] . ", " . $row['town'],
                'phone_number' => $row['phone_number'],
                'items' => []
            ];
        }
        $orders[$oid]['items'][] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'item_price' => $row['item_price'],
            'item_subtotal' => $row['item_subtotal']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Orders - CEC COMART</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
      body {
        background: #f4f6fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      .main-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        padding: 32px 28px 24px 28px;
        margin: 40px auto 0 auto;
        max-width: 1200px;
      }
      .table thead th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 1.05rem;
        border-bottom: 2px solid #e3e6ed;
      }
      .table tbody tr {
        transition: background 0.2s;
      }
      .table-hover tbody tr:hover {
        background: #f1f7ff;
      }
      .table td, .table th {
        vertical-align: middle;
      }
      .badge-status {
          padding: 6px 12px;
          border-radius: 20px;
          font-weight: 600;
          font-size: 0.85rem;
          text-transform: capitalize;
      }
      .status-delivered { background-color: #e8f5e9; color: #2e7d32; }
      
      .btn-expand {
          font-size: 1.25rem;
          color: #4b5563;
          background: none;
          border: none;
          padding: 4px 10px;
          transition: transform 0.2s;
      }
      .btn-expand[aria-expanded="true"] {
          transform: rotate(180deg);
      }
      
      @media (max-width: 900px) {
        .main-card { padding: 12px 2px; }
        .table th, .table td { font-size: 0.95rem; }
      }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0" style="font-size:1.5rem;letter-spacing:1px;">Completed Orders Log</h2>
      </div>
      
      <div class="table-responsive">
        <table class="table table-hover align-middle text-center">
          <thead>
            <tr>
              <th scope="col" style="width: 50px;"></th>
              <th scope="col">Order No</th>
              <th scope="col">Customer</th>
              <th scope="col">Address</th>
              <th scope="col">Phone No</th>
              <th scope="col">Total Amount</th>
              <th scope="col">Date</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if (!empty($orders)) {
                foreach ($orders as $order_id => $order) {
                    $order_no = htmlspecialchars($order['order_number'] ?: str_pad($order_id, 5, '0', STR_PAD_LEFT));
                    $customer = htmlspecialchars($order['customer_name']);
                    $address = htmlspecialchars($order['address']);
                    $phone = htmlspecialchars($order['phone_number']);
                    $total = (float)$order['total_amount'];
                    $date = date("Y-m-d H:i", strtotime($order['created_at']));
                    $status = $order['order_status'];
                    $statusClass = 'status-' . $status;
                    ?>
                    <!-- Main Order Row -->
                    <tr style="border-bottom: 1px solid #e3e6ed;">
                      <td>
                        <button class="btn-expand" type="button" data-bs-toggle="collapse" data-bs-target="#order-items-<?php echo $order_id; ?>" aria-expanded="false" aria-controls="order-items-<?php echo $order_id; ?>">
                          <i class="bi bi-chevron-down"></i>
                        </button>
                      </td>
                      <td class="fw-bold">#<?php echo $order_no; ?></td>
                      <td class="fw-semibold text-start"><?php echo $customer; ?></td>
                      <td class="text-start" style="max-width: 250px;"><?php echo $address; ?></td>
                      <td><?php echo $phone; ?></td>
                      <td class="fw-bold">Rs. <?php echo number_format($total, 2); ?></td>
                      <td class="text-muted"><?php echo $date; ?></td>
                      <td>
                          <span class="badge-status <?php echo $statusClass; ?>">
                              <?php echo $status; ?>
                          </span>
                      </td>
                    </tr>
                    
                    <!-- Collapsible Order Items Details Row -->
                    <tr class="border-0">
                      <td colspan="8" class="p-0 border-0">
                        <div id="order-items-<?php echo $order_id; ?>" class="collapse" style="background-color: #fafbfc;">
                          <div class="p-4 border-start border-end border-bottom" style="border-color: #e3e6ed !important;">
                            <h5 class="fw-bold text-secondary mb-3" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                              <i class="bi bi-cart3 me-2"></i>Order Items Details
                            </h5>
                            <div class="table-responsive">
                              <table class="table table-bordered table-sm align-middle text-center bg-white mb-0" style="font-size: 0.9rem; border-color: #e3e6ed;">
                                <thead class="table-light text-secondary">
                                  <tr>
                                    <th scope="col" class="py-2 text-start ps-3">Product Name</th>
                                    <th scope="col" class="py-2">Unit Price</th>
                                    <th scope="col" class="py-2">Quantity</th>
                                    <th scope="col" class="py-2">Subtotal</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($order['items'] as $item) { ?>
                                    <tr>
                                      <td class="text-start fw-semibold ps-3 py-2"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                      <td>Rs. <?php echo number_format($item['item_price'], 2); ?></td>
                                      <td class="fw-semibold"><?php echo $item['quantity']; ?></td>
                                      <td class="fw-bold text-dark">Rs. <?php echo number_format($item['item_subtotal'], 2); ?></td>
                                    </tr>
                                  <?php } ?>
                                  <tr class="table-light fw-semibold text-secondary">
                                    <td colspan="3" class="text-end pe-3 py-2">Delivery Fee:</td>
                                    <td class="text-dark">Rs. <?php echo number_format($order['delivery_fee'], 2); ?></td>
                                  </tr>
                                  <tr class="table-success fw-bold text-success">
                                    <td colspan="3" class="text-end pe-3 py-2" style="background-color: #e8f5e9;">Grand Total:</td>
                                    <td style="background-color: #e8f5e9;">Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No completed orders found.</td>
                </tr>
                <?php
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>