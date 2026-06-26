<?php
session_start();
require_once 'connection.php';

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
    "SELECT oi.quantity, oi.price, oi.subtotal, i.name, i.image_path
     FROM order_items oi
     INNER JOIN items i ON i.id = oi.item_id
     WHERE oi.order_id = '$order_id'"
);

// Clear session order data so user can't refresh back here
unset($_SESSION['last_order_number'], $_SESSION['last_order_id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Confirmed – CEC Fresh-Co Mart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    body { font-family: 'Poppins', sans-serif; background: #f5f7fa; }

    .confirm-hero {
      background: linear-gradient(135deg, #27b62e 0%, #1a8c20 100%);
      color: white;
      padding: 60px 20px 40px;
      text-align: center;
    }
    .confirm-hero .checkmark-circle {
      width: 90px; height: 90px;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      animation: popIn 0.5s cubic-bezier(0.175,0.885,0.32,1.275) both;
    }
    .confirm-hero .checkmark-circle i { font-size: 45px; }
    .confirm-hero h1 { font-size: 2rem; font-weight: 700; margin-bottom: 6px; }
    .confirm-hero p { opacity: 0.9; font-size: 1rem; }

    @keyframes popIn {
      from { transform: scale(0); opacity: 0; }
      to   { transform: scale(1); opacity: 1; }
    }

    .order-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      padding: 30px;
      margin-bottom: 24px;
    }
    .order-card h5 {
      font-weight: 700;
      color: #1b1b1b;
      border-bottom: 2px solid #f0f0f0;
      padding-bottom: 12px;
      margin-bottom: 20px;
    }
    .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f5f5f5; font-size: 0.95rem; }
    .detail-row:last-child { border-bottom: none; font-weight: 700; font-size: 1.05rem; }
    .detail-row span:first-child { color: #666; }
    .detail-row span:last-child { color: #1b1b1b; }

    .item-row { display: flex; align-items: center; gap: 14px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
    .item-row:last-child { border-bottom: none; }
    .item-row img { width: 55px; height: 55px; object-fit: cover; border-radius: 10px; background: #f0f0f0; }
    .item-row .item-name { font-weight: 600; font-size: 0.95rem; }
    .item-row .item-meta { font-size: 0.82rem; color: #888; }
    .item-row .item-price { margin-left: auto; font-weight: 700; color: #27b62e; white-space: nowrap; }

    .badge-order { background: #e8f5e9; color: #27b62e; font-weight: 700; border-radius: 8px; padding: 4px 14px; font-size: 1rem; }

    .cta-btn {
      display: inline-block;
      background: linear-gradient(135deg, #27b62e, #1a8c20);
      color: white;
      padding: 14px 36px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 1rem;
      text-decoration: none;
      transition: transform 0.2s, box-shadow 0.2s;
      box-shadow: 0 4px 16px rgba(39,182,46,0.3);
    }
    .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(39,182,46,0.4); color: white; }
    .cta-btn-outline {
      display: inline-block;
      border: 2px solid #27b62e;
      color: #27b62e;
      padding: 13px 36px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 1rem;
      text-decoration: none;
      transition: all 0.2s;
    }
    .cta-btn-outline:hover { background: #27b62e; color: white; }
  </style>
</head>
<body>

  <!-- Hero -->
  <div class="confirm-hero">
    <div class="checkmark-circle">
      <i class="fa fa-check"></i>
    </div>
    <h1>Order Confirmed!</h1>
    <p>Thank you for your purchase. We've received your order and will process it shortly.</p>
    <div class="mt-3">
      <span class="badge-order">Order #<?php echo htmlspecialchars($order_number); ?></span>
    </div>
  </div>

  <div class="container py-5" style="max-width: 760px;">

    <!-- Order Summary -->
    <div class="order-card">
      <h5><i class="fa fa-receipt me-2 text-success"></i>Order Summary</h5>

      <?php if ($items_rs && $items_rs->num_rows > 0): ?>
        <?php while ($item = $items_rs->fetch_assoc()): ?>
        <div class="item-row">
          <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='assets/images/placeholder.png'">
          <div>
            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
            <div class="item-meta">Qty: <?php echo htmlspecialchars($item['quantity']); ?></div>
          </div>
          <div class="item-price">Rs. <?php echo number_format((float)$item['subtotal'], 2); ?></div>
        </div>
        <?php endwhile; ?>
      <?php endif; ?>

      <div class="detail-row mt-3">
        <span>Subtotal</span>
        <span>Rs. <?php echo number_format((float)$order['total_amount'] - (float)$order['delivery_fee'], 2); ?></span>
      </div>
      <div class="detail-row">
        <span>Delivery Fee</span>
        <span>Rs. <?php echo number_format((float)$order['delivery_fee'], 2); ?></span>
      </div>
      <div class="detail-row">
        <span>Total Paid</span>
        <span style="color:#27b62e;">Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></span>
      </div>
    </div>

    <!-- Order Details -->
    <div class="order-card">
      <h5><i class="fa fa-info-circle me-2 text-success"></i>Order Details</h5>
      <div class="detail-row">
        <span>Order Number</span>
        <span>#<?php echo htmlspecialchars($order_number); ?></span>
      </div>
      <div class="detail-row">
        <span>Order Date</span>
        <span><?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></span>
      </div>
      <div class="detail-row">
        <span>Payment Method</span>
        <span>Cash on Delivery</span>
      </div>
      <div class="detail-row">
        <span>Status</span>
        <span style="color:#27b62e;font-weight:600;">Processing</span>
      </div>
    </div>

    <!-- CTAs -->
    <div class="text-center d-flex flex-wrap gap-3 justify-content-center">
      <a href="product/my-orders.php" class="cta-btn">
        <i class="fa fa-list me-2"></i>View My Orders
      </a>
      <a href="product/product.php" class="cta-btn-outline">
        <i class="fa fa-shopping-bag me-2"></i>Continue Shopping
      </a>
    </div>

  </div>

  <footer style="background:#212529;color:white;text-align:center;padding:15px 0;margin-top:40px;">
    © 2026 CEC Fresh-Co Mart. All Rights Reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
