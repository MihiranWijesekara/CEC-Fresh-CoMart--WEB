<?php
session_start();
require_once 'connection.php';
$store = require 'storeConfig.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login/sign.php");
    exit();
}

// Must have a confirmed order_number in session
if (!isset($_SESSION['last_order_number']) || !isset($_SESSION['last_order_id'])) {
    header("Location: product/product.php");
    exit();
}

$order_number = $_SESSION['last_order_number'];
$order_id     = (int)$_SESSION['last_order_id'];
$user_id      = (int)$_SESSION['user_id'];

// Load order details
$order_rs = Database::search("SELECT * FROM orders WHERE id='$order_id' AND user_id='$user_id' LIMIT 1");
if (!$order_rs || $order_rs->num_rows === 0) {
    header("Location: product/product.php");
    exit();
}
$order = $order_rs->fetch_assoc();

// Load order items
$items_rs = Database::search(
    "SELECT oi.quantity, oi.price, oi.subtotal, i.name, i.image_path, i.unit, i.category_id
     FROM order_items oi
     INNER JOIN items i ON i.id = oi.item_id
     WHERE oi.order_id = '$order_id'"
);

// Clear session order data so user can't refresh back here
unset($_SESSION['last_order_number'], $_SESSION['last_order_id']);

// ---- presentation helpers -------------------------------------------------
$method_val = $order['purchase_method'] ?? ($order['order_method'] ?? 'cod');
$is_pickup  = ($method_val === 'pickup');
$raw_status = $order['order_status'] ?: 'pending';
$steps = $is_pickup
    ? ['Pending', 'Processing', 'Ready for Pickup', 'Collected']
    : ['Pending', 'Processing', 'Shipped', 'Delivered'];
$status_index = [
    'pending' => 0, 
    'processing' => 1, 
    'shipped' => 2, 
    'ready' => 2, 
    'ready_for_pickup' => 2, 
    'delivered' => 3, 
    'collected' => 3
];
$current = $status_index[$raw_status] ?? 0;
$cancelled = ($raw_status === 'cancelled');

$subtotal_amt = (float)$order['total_amount'] - (float)$order['delivery_fee'];
$pay_label    = $is_pickup ? 'Pay at Store Counter' : 'Cash on Delivery';
$pay_status   = !empty($order['payment_status']) ? ucfirst(strtolower($order['payment_status'])) : 'Unpaid';
$pickup_slot_val = $order['pickup_time_slot'] ?? ($order['pickup_slot'] ?? '');
$delivery_notes_val = $order['delivery_notes'] ?? ($order['delivery_instructions'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Confirmed – CEC Fresh-Co Mart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
  <link rel="stylesheet" href="assets/css/custom-ui.css" />
</head>
<body class="fc-store" style="padding-bottom:0">

  <!-- Hero -->
  <div class="fc-confirm-hero">
    <div class="fc-check"><i class="fa fa-check"></i></div>
    <h1>Order Confirmed!</h1>
    <p>Thank you for shopping with Freshcomart.
      <?php echo $is_pickup ? "We're packing your groceries – show your pickup code at the counter." : "We've received your order and will process it shortly."; ?></p>
    <span class="fc-order-no">ORDER #<?php echo htmlspecialchars($order_number); ?></span>
  </div>

  <div class="fc-wrap">

    <!-- Progress tracker -->
    <div class="fc-card p-4 mb-3">
      <h2 class="h6 fw-bold mb-3" style="color:var(--fc-text)"><i class="fa-solid fa-route me-2 text-success"></i>Order progress</h2>
      <ol class="fc-tracker">
        <?php foreach ($steps as $i => $label):
          $cls = '';
          if ($cancelled) { $cls = ($i === $current) ? 'cancelled' : ''; }
          elseif ($i < $current || ($i === $current && $current === 3)) { $cls = 'done'; }
          elseif ($i === $current) { $cls = 'current'; }
        ?>
          <li class="<?php echo $cls; ?>" data-n="<?php echo $i + 1; ?>"><?php echo $label; ?></li>
        <?php endforeach; ?>
      </ol>
      <?php if ($cancelled): ?><p class="text-danger fw-semibold mb-0 mt-2">This order was cancelled.</p><?php endif; ?>
    </div>

    <?php if ($is_pickup): ?>
    <!-- Store Pickup Pass -->
    <div class="fc-pass" id="pickupPass">
      <div class="fc-pass-main">
        <span class="tag"><i class="fa-solid fa-store"></i> Store Pickup Pass</span>
        <dl>
          <div><dt>Pickup time slot</dt>
            <dd><i class="fa-regular fa-calendar me-1 text-warning"></i>
              <?php echo !empty($order['pickup_date']) ? date('D, d M Y', strtotime($order['pickup_date'])) : ''; ?>
              &middot; <?php echo htmlspecialchars($pickup_slot_val); ?></dd></div>
          <div><dt>Store address</dt>
            <dd><?php echo htmlspecialchars($store['address']); ?><br>
              <small class="text-muted fw-normal"><?php echo htmlspecialchars($store['hours']); ?> · <?php echo htmlspecialchars($store['phone']); ?></small></dd></div>
          <div><dt>Instructions</dt>
            <dd class="fw-normal" style="font-size:.9rem">Show this code (or this page) at the pickup counter and bring a valid ID. Please collect within your selected time slot.</dd></div>
        </dl>
      </div>
      <div class="fc-pass-code">
        <small>Your pickup code</small>
        <div class="code"><?php echo htmlspecialchars($order['pickup_code'] ?? '------'); ?></div>
        <p>Order #<?php echo htmlspecialchars($order_number); ?><br>Pay at counter: <strong>Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></strong></p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Printable receipt -->
    <div class="fc-card fc-receipt" id="receipt">
      <div class="fc-receipt-head">
        <div>
          <h2><i class="fa fa-receipt me-2 text-success"></i>Order Receipt</h2>
          <div class="mt-2 d-flex gap-2 flex-wrap">
            <span class="fc-badge <?php echo $is_pickup ? 'method-pickup' : 'method-delivery'; ?>"><i class="fa-solid <?php echo $is_pickup ? 'fa-store' : 'fa-truck'; ?>"></i><?php echo $is_pickup ? 'Store Pickup' : 'Delivery / COD'; ?></span>
            <span class="fc-badge <?php echo strtolower($pay_status) === 'paid' ? 'pay-paid' : 'pay-unpaid'; ?>"><?php echo htmlspecialchars($pay_status); ?></span>
          </div>
        </div>
        <div class="meta">Freshcomart – <?php echo htmlspecialchars($store['label']); ?><br><?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></div>
      </div>

      <?php if ($items_rs && $items_rs->num_rows > 0): ?>
        <?php while ($item = $items_rs->fetch_assoc()): ?>
        <div class="fc-receipt-item">
          <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.style.visibility='hidden'">
          <div>
            <div class="nm"><?php echo htmlspecialchars($item['name']); ?></div>
            <div class="q">Qty: <?php echo htmlspecialchars((string)Database::formatQuantity($item['quantity'], $item['unit'], $item['category_id'])); ?></div>
          </div>
          <div class="pr">Rs. <?php echo number_format((float)$item['subtotal'], 2); ?></div>
        </div>
        <?php endwhile; ?>
      <?php endif; ?>

      <div class="mt-3">
        <div class="fc-kv"><span>Subtotal</span><span>Rs. <?php echo number_format($subtotal_amt, 2); ?></span></div>
        <div class="fc-kv"><span>Delivery fee</span>
          <span><?php echo $is_pickup ? '<span class="fc-free-badge">Free</span>' : 'Rs. ' . number_format((float)$order['delivery_fee'], 2); ?></span></div>
        <div class="fc-kv total"><span><?php echo $is_pickup ? 'Total to pay in store' : 'Total to pay on delivery'; ?></span><span>Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></span></div>
      </div>

      <hr style="border-color:var(--fc-border)">
      <div class="fc-kv"><span>Order number</span><span>#<?php echo htmlspecialchars($order_number); ?></span></div>
      <div class="fc-kv"><span>Order date</span><span><?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></span></div>
      <div class="fc-kv"><span>Payment method</span><span><?php echo $pay_label; ?></span></div>
      <?php if (!$is_pickup): ?>
        <div class="fc-kv"><span>Delivery address</span><span><?php echo htmlspecialchars(trim($order['street_address1'] . ', ' . ($order['street_address2'] ? $order['street_address2'] . ', ' : '') . $order['town'])); ?></span></div>
        <?php if (!empty($delivery_notes_val)): ?>
          <div class="fc-kv"><span>Delivery instructions</span><span><?php echo htmlspecialchars($delivery_notes_val); ?></span></div>
        <?php endif; ?>
      <?php endif; ?>
      <div class="fc-kv"><span>Status</span><span><?php echo htmlspecialchars(ucfirst($raw_status === 'shipped' && $is_pickup ? 'ready' : str_replace('_', ' ', $raw_status))); ?></span></div>
    </div>

    <!-- CTAs -->
    <div class="fc-confirm-actions">
      <button type="button" class="fc-btn fc-btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Print receipt</button>
      <a href="product/my-orders.php" class="fc-btn fc-btn-primary"><i class="fa fa-list"></i> View My Orders</a>
      <a href="product/product.php" class="fc-btn fc-btn-outline"><i class="fa fa-shopping-bag"></i> Continue Shopping</a>
    </div>
  </div>

  <footer style="background:#0f172a;color:white;text-align:center;padding:15px 0;margin-top:40px;">
    © 2026 CEC Fresh-Co Mart. All Rights Reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
