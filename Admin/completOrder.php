<?php
require 'adminAuth.php';
require '../connection.php';

// Query orders that are completed (delivered)
$orders_rs = Database::search("
    SELECT o.id AS order_id, o.order_number, o.created_at, o.order_status,
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
              <th scope="col">Order No</th>
              <th scope="col">Address</th>
              <th scope="col">Phone No</th>
              <th scope="col">Product Name</th>
              <th scope="col">QTY</th>
              <th scope="col">Date</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($orders_rs && $orders_rs->num_rows > 0) {
                while ($row = $orders_rs->fetch_assoc()) {
                    $order_id = $row['order_id'];
                    $order_no = htmlspecialchars($row['order_number'] ?: str_pad($order_id, 5, '0', STR_PAD_LEFT));
                    $address = htmlspecialchars($row['street_address1'] . ", " . $row['street_address2'] . ", " . $row['town']);
                    $phone = htmlspecialchars($row['phone_number']);
                    $product = htmlspecialchars($row['product_name']);
                    $qty = (int)$row['quantity'];
                    $date = date("Y-m-d H:i", strtotime($row['created_at']));
                    $status = $row['order_status'];
                    $statusClass = 'status-' . $status;
                    ?>
                    <tr>
                      <td class="fw-bold">#<?php echo $order_no; ?></td>
                      <td class="text-start" style="max-width: 250px;"><?php echo $address; ?></td>
                      <td><?php echo $phone; ?></td>
                      <td class="fw-semibold text-start"><?php echo $product; ?></td>
                      <td><?php echo $qty; ?></td>
                      <td class="text-muted"><?php echo $date; ?></td>
                      <td>
                          <span class="badge-status <?php echo $statusClass; ?>">
                              <?php echo $status; ?>
                          </span>
                      </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No completed orders found.</td>
                </tr>
                <?php
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
</body>
</html>