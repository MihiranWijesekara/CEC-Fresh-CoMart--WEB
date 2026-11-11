<?php
require '../connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/sign.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Handle customer self-service cancellation for pending orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $cancel_order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    if ($cancel_order_id > 0) {
        Database::setUpConnection();
        $db = Database::$connection;
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("SELECT id, order_number, order_status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
            $stmt->bind_param("ii", $cancel_order_id, $user_id);
            $stmt->execute();
            $ordRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($ordRow && $ordRow['order_status'] === 'pending') {
                $ordNo = $ordRow['order_number'] ?: ('ORD-' . $cancel_order_id);
                // Restore stock for each item in the order
                $itemsStmt = $db->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->bind_param("i", $cancel_order_id);
                $itemsStmt->execute();
                $itemsRes = $itemsStmt->get_result();
                $itemsList = [];
                while ($ir = $itemsRes->fetch_assoc()) {
                    $itemsList[] = $ir;
                }
                $itemsStmt->close();

                foreach ($itemsList as $oi) {
                    $iid = (int)$oi['item_id'];
                    $qty = (float)$oi['quantity'];
                    $lockItem = $db->prepare("SELECT stock_quantity FROM items WHERE id = ? FOR UPDATE");
                    $lockItem->bind_param("i", $iid);
                    $lockItem->execute();
                    $itemCur = $lockItem->get_result()->fetch_assoc();
                    $lockItem->close();

                    if ($itemCur) {
                        $prevStock = (float)$itemCur['stock_quantity'];
                        $newStock  = $prevStock + $qty;
                        $upItem = $db->prepare("UPDATE items SET stock_quantity = ? WHERE id = ?");
                        $upItem->bind_param("di", $newStock, $iid);
                        $upItem->execute();
                        $upItem->close();

                        $reason = "Order #$ordNo Cancelled by Customer";
                        $logTx = $db->prepare("INSERT INTO stock_transactions (item_id, transaction_type, quantity, previous_stock, new_stock, reason) VALUES (?, 'in', ?, ?, ?, ?)");
                        $logTx->bind_param("iddds", $iid, $qty, $prevStock, $newStock, $reason);
                        $logTx->execute();
                        $logTx->close();
                    }
                }

                $upOrd = $db->prepare("UPDATE orders SET order_status = 'cancelled', payment_status = 'cancelled' WHERE id = ?");
                $upOrd->bind_param("i", $cancel_order_id);
                $upOrd->execute();
                $upOrd->close();

                $db->commit();
                header("Location: my-orders.php?msg=cancelled");
                exit();
            } else {
                $db->rollback();
                header("Location: my-orders.php?err=cannot_cancel");
                exit();
            }
        } catch (Exception $e) {
            $db->rollback();
            header("Location: my-orders.php?err=cancel_failed");
            exit();
        }
    }
}

$filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';

// Query customer orders with full COD & Store Pickup metadata
$orders_rs = Database::search("
    SELECT o.id AS order_id, o.order_number, o.created_at, o.order_status, o.total_amount, o.delivery_fee,
           o.purchase_method, o.payment_status, o.customer_name, o.customer_phone,
           o.street_address1, o.street_address2, o.town, o.delivery_notes,
           o.pickup_date, o.pickup_time_slot, o.pickup_code,
           oi.quantity, oi.price AS item_price, oi.subtotal AS item_subtotal,
           i.name AS product_name, i.image_path, i.unit, i.category_id
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
                'purchase_method' => $row['purchase_method'] ?: 'cod',
                'payment_status' => $row['payment_status'] ?: 'unpaid',
                'customer_name' => $row['customer_name'],
                'customer_phone' => $row['customer_phone'],
                'street_address1' => $row['street_address1'],
                'street_address2' => $row['street_address2'],
                'town' => $row['town'],
                'delivery_notes' => $row['delivery_notes'],
                'pickup_date' => $row['pickup_date'],
                'pickup_time_slot' => $row['pickup_time_slot'],
                'pickup_code' => $row['pickup_code'],
                'items' => []
            ];
        }
        $orders[$oid]['items'][] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit' => $row['unit'],
            'category_id' => $row['category_id'],
            'item_price' => $row['item_price'],
            'item_subtotal' => $row['item_subtotal'],
            'image_path' => $row['image_path']
        ];
    }
}

// Apply filter tab if selected
$filtered_orders = [];
foreach ($orders as $oid => $ord) {
    $st = $ord['order_status'] ?: 'pending';
    $pm = $ord['purchase_method'] ?: 'cod';
    if ($filter === 'active' && in_array($st, ['delivered', 'collected', 'cancelled'], true)) continue;
    if ($filter === 'pickup' && $pm !== 'pickup') continue;
    if ($filter === 'cod' && $pm !== 'cod') continue;
    if ($filter === 'completed' && !in_array($st, ['delivered', 'collected'], true)) continue;
    if ($filter === 'cancelled' && $st !== 'cancelled') continue;
    $filtered_orders[$oid] = $ord;
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
    <link rel="stylesheet" href="../assets/css/custom-ui.css" />
    <style>
      body.fc-store .orders-section { margin-top: calc(var(--fc-header-h) + var(--fc-catbar-h) + 24px); }
      @media (max-width: 991.98px) { body.fc-store .orders-section { margin-top: 150px; } }
      body.fc-store .orders-container { border-radius: var(--fc-radius); }
      .badge-status.status-ready, .badge-status.status-ready_for_pickup { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
      .badge-status.status-collected { background: #dcfce7; color: #16a34a; }
      .method-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;
      }
      .method-cod { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
      .method-pickup { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
      .pickup-code-pill {
        display: inline-block; background: #1e293b; color: #fbbf24;
        font-family: monospace; font-weight: 800; font-size: 0.82rem;
        padding: 3px 10px; border-radius: 6px; letter-spacing: 0.8px; margin-top: 4px;
      }
      .order-filter-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 22px; }
      .order-filter-tab {
        padding: 8px 16px; border-radius: 999px; font-size: 0.86rem; font-weight: 600;
        text-decoration: none; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0;
        transition: all 0.2s;
      }
      .order-filter-tab:hover { background: #e2e8f0; color: #0f172a; }
      .order-filter-tab.active { background: #16a34a; color: #fff; border-color: #16a34a; }
      /* 4-step progress tracker */
      .order-tracker {
        display: flex; justify-content: space-between; position: relative;
        margin: 8px 0 22px; padding: 16px 20px; background: #fff;
        border: 1px solid #e2e8f0; border-radius: 12px;
      }
      .tracker-step { flex: 1; text-align: center; position: relative; z-index: 2; }
      .tracker-dot {
        width: 34px; height: 34px; border-radius: 50%; margin: 0 auto 6px;
        display: flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #94a3b8; font-weight: 700; border: 2px solid #cbd5e1;
      }
      .tracker-step.done .tracker-dot { background: #dcfce7; color: #15803d; border-color: #16a34a; }
      .tracker-step.active .tracker-dot { background: #16a34a; color: #fff; border-color: #15803d; box-shadow: 0 0 0 4px rgba(22,163,74,0.18); }
      .tracker-label { font-size: 0.78rem; font-weight: 700; color: #64748b; }
      .tracker-step.done .tracker-label, .tracker-step.active .tracker-label { color: #0f172a; }
    </style>
  </head>
  <body class="fc-store">
    <!-- Navbar Placeholder -->
    <div id="navbar" class="navbar-container"></div>
    
    <div class="container orders-section">
      <div class="orders-container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
          <h2 class="orders-header mb-0">
            <i class="bi bi-bag-check-fill"></i>My Order History
          </h2>
          <a href="product.php" class="btn btn-outline-success rounded-pill px-3 fw-semibold btn-sm">
            <i class="bi bi-cart-plus me-1"></i>Continue Shopping
          </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
          <div class="alert alert-success d-flex align-items-center rounded-3 mb-3 py-2" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>Your order has been cancelled and reserved items have been returned to inventory.</div>
          </div>
        <?php elseif (isset($_GET['err'])): ?>
          <div class="alert alert-warning d-flex align-items-center rounded-3 mb-3 py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>Only orders in <strong>Order Placed (Pending)</strong> status can be cancelled online. Please contact our store team for assistance.</div>
          </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="order-filter-tabs">
          <a href="my-orders.php?filter=all" class="order-filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Orders (<?php echo count($orders); ?>)</a>
          <a href="my-orders.php?filter=active" class="order-filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>"><i class="bi bi-lightning-charge me-1"></i>Active / In Progress</a>
          <a href="my-orders.php?filter=pickup" class="order-filter-tab <?php echo $filter === 'pickup' ? 'active' : ''; ?>"><i class="bi bi-shop me-1"></i>Store Pickup</a>
          <a href="my-orders.php?filter=cod" class="order-filter-tab <?php echo $filter === 'cod' ? 'active' : ''; ?>"><i class="bi bi-truck me-1"></i>Home Delivery (COD)</a>
          <a href="my-orders.php?filter=completed" class="order-filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>"><i class="bi bi-check2-all me-1"></i>Completed</a>
          <a href="my-orders.php?filter=cancelled" class="order-filter-tab <?php echo $filter === 'cancelled' ? 'active' : ''; ?>"><i class="bi bi-x-circle me-1"></i>Cancelled</a>
        </div>
        
        <?php if (!empty($filtered_orders)) { ?>
          <div class="table-wrapper">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 60px;" class="text-center">Details</th>
                    <th>Order No &amp; Date</th>
                    <th>Fulfillment Method</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-center">Order Status</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($filtered_orders as $order_id => $order) {
                      $order_no = htmlspecialchars($order['order_number'] ?: str_pad($order_id, 5, '0', STR_PAD_LEFT));
                      $date = date("M d, Y h:i A", strtotime($order['created_at']));
                      $total = (float)$order['total_amount'];
                      $status = $order['order_status'] ?: 'pending';
                      $method = $order['purchase_method'] ?: 'cod';
                      $payStatus = $order['payment_status'] ?: 'unpaid';
                      $statusClass = 'status-' . $status;
                      
                      // Display status labels nicely
                      $status_label = $status;
                      $status_icon = 'bi-clock';
                      if ($status === 'pending') {
                          $status_label = 'Order Placed';
                          $status_icon = 'bi-check2-circle';
                      } else if ($status === 'processing') {
                          $status_label = 'Preparing';
                          $status_icon = 'bi-box-seam';
                      } else if ($status === 'shipped') {
                          $status_label = 'Out for Delivery';
                          $status_icon = 'bi-truck';
                      } else if ($status === 'delivered') {
                          $status_label = 'Delivered';
                          $status_icon = 'bi-house-check';
                      } else if ($status === 'ready' || $status === 'ready_for_pickup') {
                          $status_label = 'Ready for Pickup';
                          $status_icon = 'bi-bag-check-fill';
                      } else if ($status === 'collected') {
                          $status_label = 'Collected at Store';
                          $status_icon = 'bi-check2-all';
                      } else if ($status === 'cancelled') {
                          $status_label = 'Cancelled';
                          $status_icon = 'bi-x-circle';
                      }

                      // Determine step index (1..4) for progress tracker
                      $stepIndex = 1;
                      if ($status === 'processing') $stepIndex = 2;
                      elseif (in_array($status, ['shipped', 'ready', 'ready_for_pickup'], true)) $stepIndex = 3;
                      elseif (in_array($status, ['delivered', 'collected'], true)) $stepIndex = 4;
                  ?>
                    <!-- Main Order Row -->
                    <tr>
                      <td class="text-center">
                        <button class="btn-expand" type="button" onclick="toggleOrderDetails(<?php echo $order_id; ?>, this)" aria-expanded="false" title="View Order Details">
                          <i class="bi bi-chevron-down"></i>
                        </button>
                      </td>
                      <td>
                        <div class="fw-bold text-dark">#<?php echo $order_no; ?></div>
                        <div class="text-muted small"><?php echo $date; ?></div>
                      </td>
                      <td>
                        <?php if ($method === 'pickup'): ?>
                          <span class="method-badge method-pickup"><i class="bi bi-shop"></i> Store Pickup</span>
                          <?php if (!empty($order['pickup_code'])): ?>
                            <div><span class="pickup-code-pill">Code: <?php echo htmlspecialchars($order['pickup_code']); ?></span></div>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="method-badge method-cod"><i class="bi bi-truck"></i> Cash on Delivery</span>
                          <?php if (!empty($order['town'])): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($order['town']); ?></div>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <div class="fw-bold text-dark">Rs. <?php echo number_format($total, 2); ?></div>
                        <?php if ($payStatus === 'paid'): ?>
                          <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size:0.7rem;">Paid</span>
                        <?php elseif ($payStatus === 'cancelled'): ?>
                          <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size:0.7rem;">Cancelled</span>
                        <?php else: ?>
                          <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill" style="font-size:0.7rem;">
                            <?php echo $method === 'pickup' ? 'Pay at Store' : 'Cash on Delivery'; ?>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <span class="badge-status <?php echo $statusClass; ?>">
                          <i class="bi <?php echo $status_icon; ?>"></i>
                          <?php echo $status_label; ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                          <a href="../order-confirmation.php?order_id=<?php echo (int)$order_id; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" title="View Receipt / Pickup Pass">
                            <i class="bi bi-printer"></i> <?php echo $method === 'pickup' ? 'Pickup Pass' : 'Receipt'; ?>
                          </a>
                          <?php if ($status === 'pending'): ?>
                            <form method="POST" action="my-orders.php" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel Order #<?php echo $order_no; ?>? Reserved stock will be restored immediately.');">
                              <input type="hidden" name="action" value="cancel_order">
                              <input type="hidden" name="order_id" value="<?php echo (int)$order_id; ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="Cancel Pending Order">
                                <i class="bi bi-x-circle"></i> Cancel
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                    
                    <!-- Collapsible Item Details Row -->
                    <tr class="border-0">
                      <td colspan="6" class="p-0 border-0">
                        <div id="order-items-<?php echo $order_id; ?>" class="collapse-details">
                          <div class="details-content">
                            <?php if ($status !== 'cancelled'): ?>
                              <!-- Visual 4-Step Order Progress Tracker -->
                              <div class="order-tracker">
                                <div class="tracker-step <?php echo $stepIndex > 1 ? 'done' : ($stepIndex === 1 ? 'active' : ''); ?>">
                                  <div class="tracker-dot"><i class="bi bi-check2"></i></div>
                                  <div class="tracker-label">1. Order Placed</div>
                                </div>
                                <div class="tracker-step <?php echo $stepIndex > 2 ? 'done' : ($stepIndex === 2 ? 'active' : ''); ?>">
                                  <div class="tracker-dot"><i class="bi bi-box-seam"></i></div>
                                  <div class="tracker-label">2. Preparing</div>
                                </div>
                                <div class="tracker-step <?php echo $stepIndex > 3 ? 'done' : ($stepIndex === 3 ? 'active' : ''); ?>">
                                  <div class="tracker-dot"><i class="bi <?php echo $method === 'pickup' ? 'bi-bag-check' : 'bi-truck'; ?>"></i></div>
                                  <div class="tracker-label">3. <?php echo $method === 'pickup' ? 'Ready for Pickup' : 'Out for Delivery'; ?></div>
                                </div>
                                <div class="tracker-step <?php echo $stepIndex === 4 ? 'done active' : ''; ?>">
                                  <div class="tracker-dot"><i class="bi bi-stars"></i></div>
                                  <div class="tracker-label">4. <?php echo $method === 'pickup' ? 'Collected at Store' : 'Delivered'; ?></div>
                                </div>
                              </div>
                            <?php endif; ?>

                            <!-- Fulfillment Summary Card -->
                            <div class="row g-3 mb-3">
                              <?php if ($method === 'pickup'): ?>
                                <div class="col-md-12">
                                  <div class="p-3 rounded-3" style="background:#fffbeb; border:1px solid #fde68a;">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                      <div>
                                        <span class="fw-bold text-dark"><i class="bi bi-shop text-warning me-1"></i>In-Store Pickup Details (Rajagiriya Branch)</span>
                                        <div class="small text-secondary mt-1">
                                          <strong>Scheduled Date:</strong> <?php echo htmlspecialchars($order['pickup_date'] ?: 'Anytime'); ?> &nbsp;|&nbsp;
                                          <strong>Time Slot:</strong> <?php echo htmlspecialchars($order['pickup_time_slot'] ?: 'Standard Hours'); ?>
                                        </div>
                                        <?php if (!empty($order['delivery_notes'])): ?>
                                          <div class="small text-muted mt-1"><strong>Store Note:</strong> <?php echo htmlspecialchars($order['delivery_notes']); ?></div>
                                        <?php endif; ?>
                                      </div>
                                      <div class="text-end">
                                        <div class="small text-muted">Present this Pickup Code at counter:</div>
                                        <span class="pickup-code-pill" style="font-size:1rem; padding:5px 14px;"><?php echo htmlspecialchars($order['pickup_code'] ?: 'N/A'); ?></span>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              <?php else: ?>
                                <div class="col-md-12">
                                  <div class="p-3 rounded-3" style="background:#eff6ff; border:1px solid #bfdbfe;">
                                    <span class="fw-bold text-dark"><i class="bi bi-truck text-primary me-1"></i>Home Delivery Details (Cash on Delivery)</span>
                                    <div class="small text-secondary mt-1">
                                      <strong>Recipient:</strong> <?php echo htmlspecialchars($order['customer_name'] ?: 'Customer'); ?> (<?php echo htmlspecialchars($order['customer_phone'] ?: 'N/A'); ?>) &nbsp;|&nbsp;
                                      <strong>Address:</strong> <?php echo htmlspecialchars(trim(($order['street_address1'] ?? '') . ' ' . ($order['street_address2'] ?? '') . ', ' . ($order['town'] ?? ''), ', ')); ?>
                                    </div>
                                    <?php if (!empty($order['delivery_notes'])): ?>
                                      <div class="small text-muted mt-1"><strong>Delivery Note:</strong> <?php echo htmlspecialchars($order['delivery_notes']); ?></div>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              <?php endif; ?>
                            </div>

                            <div class="receipt-card">
                              <div class="receipt-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold text-secondary mb-0">
                                  <i class="bi bi-receipt me-2"></i>Items in this Order (<?php echo count($order['items']); ?>)
                                </h6>
                                <a href="../order-confirmation.php?order_id=<?php echo (int)$order_id; ?>" class="btn btn-sm btn-success rounded-pill px-3">
                                  <i class="bi bi-file-earmark-text me-1"></i>Open Full Printable Pass / Receipt
                                </a>
                              </div>
                              <div class="table-responsive">
                                <table class="table align-middle mb-0" style="font-size: 0.92rem;">
                                  <thead class="table-light text-secondary">
                                    <tr>
                                      <th scope="col" style="width: 90px;" class="py-2 text-center">Image</th>
                                      <th scope="col" class="py-2">Product Name</th>
                                      <th scope="col" class="py-2 text-end">Unit Price</th>
                                      <th scope="col" class="py-2 text-center">Quantity</th>
                                      <th scope="col" class="py-2 text-end">Subtotal</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($order['items'] as $item) {
                                      $formattedQty = Database::formatQuantity($item['quantity'], $item['unit'] ?? '', (int)($item['category_id'] ?? 0));
                                    ?>
                                      <tr>
                                        <td class="text-center py-2">
                                          <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="Product" class="product-img" onerror="this.src='../assets/images/logo/logo-freshco.png'" />
                                        </td>
                                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="text-end">Rs. <?php echo number_format((float)$item['item_price'], 2); ?></td>
                                        <td class="text-center fw-semibold"><?php echo htmlspecialchars((string)$formattedQty); ?></td>
                                        <td class="fw-bold text-dark text-end">Rs. <?php echo number_format((float)$item['item_subtotal'], 2); ?></td>
                                      </tr>
                                    <?php } ?>
                                    <tr class="table-light text-secondary">
                                      <td colspan="4" class="text-end fw-semibold py-2"><?php echo $method === 'pickup' ? 'Store Pickup Fee:' : 'Home Delivery Fee:'; ?></td>
                                      <td class="text-end fw-semibold text-dark">
                                        <?php echo (float)$order['delivery_fee'] > 0 ? 'Rs. ' . number_format((float)$order['delivery_fee'], 2) : '<span class="text-success fw-bold">FREE (Rs. 0.00)</span>'; ?>
                                      </td>
                                    </tr>
                                    <tr class="fw-bold text-success" style="background-color: #f8fafc;">
                                      <td colspan="4" class="text-end py-3">Grand Total:</td>
                                      <td class="text-end py-3 text-success" style="font-size: 1.05rem;">Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></td>
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
            <h4 class="fw-bold">No Matching Orders Found</h4>
            <p class="text-muted mb-4">
              <?php echo $filter !== 'all' ? 'No orders match the selected filter tab.' : 'You have not placed any orders with us yet.'; ?>
            </p>
            <?php if ($filter !== 'all'): ?>
              <a href="my-orders.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">Show All Orders</a>
            <?php endif; ?>
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
