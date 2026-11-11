<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login/sign.php");
    exit();
}

// Generate a fresh CSRF token for this page load
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once 'connection.php';
$store = require 'storeConfig.php';
$user_id = $_SESSION['user_id'];
$cart_rs = Database::search("SELECT id FROM carts WHERE user_id='$user_id' AND status='Active' LIMIT 1");
$cart_has_items = false;
if ($cart_rs && $cart_rs->num_rows > 0) {
    $cart_id = $cart_rs->fetch_assoc()['id'];
    $items_rs = Database::search("SELECT COUNT(id) AS total_items FROM cart_items WHERE cart_id='$cart_id'");
    if ($items_rs && $items_rs->fetch_assoc()['total_items'] > 0) {
        $cart_has_items = true;
    }
}
if (!$cart_has_items) {
    header("Location: product/product.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout - Fresh-Co Mart</title>

    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="assets/css/custom-ui.css" />
    <style>
      body.fc-store { padding-top: calc(var(--fc-header-h) + var(--fc-catbar-h)); }
      @media (max-width: 991.98px) { body.fc-store { padding-top: 112px; } }
      footer { background-color: #0f172a; color: white; text-align: center; padding: 18px 0; margin-top: 40px; }
    </style>
  </head>
  <body class="fc-store">
    <!-- Header -->
    <div id="navbar" class="navbar-container">
      <?php $FC_BASE = ''; $FC_EMBED = true; include 'product/nav.php'; ?>
    </div>

    <?php
    $user_id = (int)$_SESSION['user_id'];
    $user_rs = Database::search("SELECT * FROM users WHERE id='$user_id'");
    $user_data = $user_rs->fetch_assoc();

    $delivery_charge = (float)$store['delivery_fee'];
    $subtotal = 0;
    $total = 0;
    $order_items = [];

    $cart_rs = Database::search("SELECT id FROM carts WHERE user_id='$user_id' AND status='Active' LIMIT 1");
    if ($cart_rs && $cart_rs->num_rows > 0) {
      $cart_data = $cart_rs->fetch_assoc();
      $cart_id = (int)$cart_data['id'];

      $order_item_rs = Database::search("SELECT ci.quantity, ci.price AS unit_price, i.name, i.unit, i.category_id FROM cart_items ci INNER JOIN items i ON ci.item_id = i.id WHERE ci.cart_id='$cart_id'");

      while ($order_item = $order_item_rs->fetch_assoc()) {
        $order_item['line_price'] = (float)$order_item['quantity'] * (float)$order_item['unit_price'];
        $order_items[] = $order_item;
        $subtotal += (float)$order_item['line_price'];
      }
    }

    $total = $subtotal + $delivery_charge;

    // Pickup dates (today + next days)
    $tzc = new DateTimeZone('Asia/Colombo');
    $pickup_dates = [];
    for ($d = 0; $d < (int)$store['pickup_days']; $d++) {
      $dt = new DateTime("+$d day", $tzc);
      $pickup_dates[$dt->format('Y-m-d')] = ($d === 0 ? 'Today' : ($d === 1 ? 'Tomorrow' : $dt->format('l'))) . ' – ' . $dt->format('d M Y');
    }
    ?>

    <!-- Checkout -->
    <div id="msgdiv" style="display:none; max-width:1180px; margin:16px auto 0;"></div>
    <div class="fc-checkout">
      <h1>Checkout</h1>
      <p class="sub">Choose how you'd like to receive your groceries.</p>

      <div class="row g-4">
        <!-- Left: purchase method -->
        <div class="col-lg-7">
          <div class="fc-segment" role="tablist" aria-label="Purchase method">
            <button type="button" class="active" data-method="delivery" role="tab" aria-selected="true">
              <i class="fa-solid fa-truck-fast"></i>
              <span>Home Delivery<small>Cash on Delivery</small></span>
            </button>
            <button type="button" data-method="pickup" role="tab" aria-selected="false">
              <i class="fa-solid fa-store"></i>
              <span>Store Pickup<small>Pay / Collect in Store</small></span>
            </button>
          </div>

          <form id="checkoutForm" action="place.html" onsubmit="return false;">
            <!-- TAB A: Home delivery -->
            <div class="fc-panel active" id="panel-delivery" role="tabpanel">
              <div class="fc-card fc-section">
                <h2><i class="fa-solid fa-location-dot text-success"></i> Delivery details</h2>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label>First Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" readonly required />
                  </div>
                  <div class="col-md-6">
                    <label>Last Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" readonly required />
                  </div>
                  <div class="col-12">
                    <label for="streetAddress1">Street Address</label>
                    <input type="text" class="form-control" id="streetAddress1" placeholder="House number and street name" value="<?php echo htmlspecialchars($user_data['street_address1'] ?? ''); ?>" required />
                  </div>
                  <div class="col-12">
                    <label for="streetAddress2">Apartment, suite, unit (optional)</label>
                    <input type="text" class="form-control" id="streetAddress2" placeholder="Apartment, suite, unit etc. (optional)" value="<?php echo htmlspecialchars($user_data['street_address2'] ?? ''); ?>" />
                  </div>
                  <div class="col-md-6">
                    <label for="town">Town</label>
                    <input type="text" class="form-control" id="town" value="<?php echo htmlspecialchars($user_data['town'] ?? ''); ?>" required />
                  </div>
                  <div class="col-md-6">
                    <label>Phone number</label>
                    <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user_data['phone_number']); ?>" readonly required />
                  </div>
                  <div class="col-12">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly required />
                  </div>
                  <div class="col-12">
                    <label for="deliveryInstructions">Delivery instructions (optional)</label>
                    <textarea class="form-control" id="deliveryInstructions" rows="3" maxlength="500" placeholder="Gate code, landmarks, preferred time…"></textarea>
                  </div>
                </div>
                <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                  <span class="fc-fee-chip fee"><i class="fa-solid fa-truck"></i> Delivery fee: Rs. <?php echo number_format($delivery_charge, 2); ?></span>
                  <small class="text-muted">Pay cash to the rider on arrival.</small>
                </div>
              </div>
            </div>

            <!-- TAB B: Store pickup -->
            <div class="fc-panel" id="panel-pickup" role="tabpanel">
              <div class="fc-card fc-section">
                <h2><i class="fa-solid fa-store" style="color:#d97706"></i> Pickup location</h2>
                <div class="fc-store-card">
                  <i class="fa-solid fa-location-dot"></i>
                  <div>
                    <strong>Freshcomart – <?php echo htmlspecialchars($store['label']); ?></strong>
                    <span><?php echo htmlspecialchars($store['address']); ?></span>
                    <span><i class="fa-regular fa-clock me-1"></i><?php echo htmlspecialchars($store['hours']); ?> &middot; <i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($store['phone']); ?></span>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="pickupDate">Pickup date</label>
                  <select class="form-select" id="pickupDate">
                    <?php foreach ($pickup_dates as $val => $label): ?>
                      <option value="<?php echo $val; ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <label class="d-block mb-2">Pickup time slot</label>
                <div class="fc-slots" id="pickupSlots">
                  <?php foreach ($store['pickup_slots'] as $i => $slot): ?>
                    <label class="fc-slot">
                      <input type="radio" name="pickupSlot" value="<?php echo htmlspecialchars($slot); ?>" <?php echo $i === 0 ? 'checked' : ''; ?> />
                      <span><?php echo htmlspecialchars($slot); ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>

                <div class="mt-3">
                  <label for="pickupNotes">Notes for the store team (optional)</label>
                  <textarea class="form-control" id="pickupNotes" rows="2" maxlength="500" placeholder="e.g. someone else will collect – name &amp; NIC"></textarea>
                </div>

                <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                  <span class="fc-fee-chip free"><i class="fa-solid fa-tag"></i> Delivery fee: Rs. 0.00 / Free</span>
                  <small class="text-muted">You'll get a pickup code after placing the order.</small>
                </div>
              </div>
            </div>

            <!-- Payment tiles -->
            <div class="fc-card fc-section">
              <h2><i class="fa-regular fa-credit-card text-success"></i> Payment method</h2>
              <div class="fc-pay-tiles" role="radiogroup" aria-label="Payment method">
                <label class="fc-pay-tile">
                  <input type="radio" name="payment" value="cod" checked />
                  <span class="tile">
                    <span class="ico"><i class="fa-solid fa-money-bill-wave"></i></span>
                    <span><strong>Cash on Delivery</strong><small>Pay the rider when it arrives</small></span>
                    <span class="tick"><i class="fa-solid fa-check"></i></span>
                  </span>
                </label>
                <label class="fc-pay-tile">
                  <input type="radio" name="payment" value="store" />
                  <span class="tile">
                    <span class="ico"><i class="fa-solid fa-cash-register"></i></span>
                    <span><strong>Pay at Store Counter</strong><small>Pay when you collect your order</small></span>
                    <span class="tick"><i class="fa-solid fa-check"></i></span>
                  </span>
                </label>
              </div>
            </div>
          </form>
        </div>

        <!-- Right: sticky order summary -->
        <div class="col-lg-5">
          <aside class="fc-card fc-summary" aria-label="Order summary">
            <h2>Order Summary</h2>
            <div class="fc-summary-items">
              <?php if (!empty($order_items)) { foreach ($order_items as $order_item) { ?>
                <div class="fc-summary-item">
                  <span class="nm"><?php echo htmlspecialchars($order_item['name']); ?> <em>× <?php echo htmlspecialchars((string)Database::formatQuantity($order_item['quantity'], $order_item['unit'], $order_item['category_id'])); ?></em></span>
                  <span class="pr">Rs. <?php echo number_format((float)$order_item['line_price'], 2); ?></span>
                </div>
              <?php } } else { ?>
                <div class="fc-summary-item"><span class="nm">Your cart is empty</span><span class="pr">Rs. 0.00</span></div>
              <?php } ?>
            </div>

            <div class="fc-line"><span>Subtotal</span><span>Rs. <?php echo number_format($subtotal, 2); ?></span></div>
            <div class="fc-line">
              <span id="sumFeeLabel">Delivery fee</span>
              <span id="sumFee">Rs. <?php echo number_format($delivery_charge, 2); ?></span>
            </div>
            <div class="fc-line total"><span>Total payable</span><span id="sumTotal">Rs. <?php echo number_format($total, 2); ?></span></div>

            <button
              type="button"
              onclick = "placeOrder()"
              form="checkoutForm"
              class="fc-btn fc-btn-primary fc-place-btn"
            >
              <i class="fa-solid fa-lock"></i> Place Order
            </button>
            <p class="fc-secure-note"><i class="fa-solid fa-shield-halved me-1"></i>Your order details are kept secure.</p>
          </aside>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer>© 2026 CEC Fresh-Co Mart. All Rights Reserved.</footer>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script>
      // Expose PHP variables to JS for placeOrder()
      window.sessionUserId = <?php echo json_encode($user_id); ?>;
      window.totalAmount   = <?php echo json_encode($total); ?>;
      window.deliveryFee   = <?php echo json_encode($delivery_charge); ?>;
      window.csrfToken     = <?php echo json_encode($_SESSION['csrf_token']); ?>;
      window.orderItems = <?php
        if (!empty($order_items)) {
          $cart_id = isset($cart_id) ? $cart_id : null;
          $items = [];
          $item_rs = Database::search("SELECT ci.item_id, ci.quantity, ci.price AS unit_price, i.price AS item_price FROM cart_items ci INNER JOIN items i ON ci.item_id = i.id WHERE ci.cart_id='$cart_id'");
          while ($item = $item_rs->fetch_assoc()) {
            $item['subtotal'] = (float)$item['quantity'] * (float)$item['unit_price'];
            $items[] = $item;
          }
          echo json_encode($items);
        } else {
          echo 'null';
        }
      ?>;

      // Purchase-method state read by placeOrder() in script.js
      window.orderMethod = 'delivery';
      window.storeInfo = <?php echo json_encode(['address' => $store['address'], 'town' => $store['town']]); ?>;
      var BASE_FEE = <?php echo json_encode($delivery_charge); ?>;
      var SUBTOTAL = <?php echo json_encode($subtotal); ?>;

      function money(n) { return 'Rs. ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

      function setMethod(method) {
        window.orderMethod = method;
        var isPickup = method === 'pickup';
        document.querySelectorAll('.fc-segment button').forEach(function (b) {
          var on = b.dataset.method === method;
          b.classList.toggle('active', on);
          b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.getElementById('panel-delivery').classList.toggle('active', !isPickup);
        document.getElementById('panel-pickup').classList.toggle('active', isPickup);

        // payment tile follows the tab
        var pay = document.querySelector('input[name="payment"][value="' + (isPickup ? 'store' : 'cod') + '"]');
        if (pay) pay.checked = true;

        // fee + totals
        window.deliveryFee = isPickup ? 0 : BASE_FEE;
        window.totalAmount = SUBTOTAL + window.deliveryFee;
        document.getElementById('sumFee').innerHTML = isPickup ? '<span class="fc-free-badge">Rs. 0.00 / Free</span>' : money(BASE_FEE);
        document.getElementById('sumFeeLabel').textContent = isPickup ? 'Delivery fee (pickup)' : 'Delivery fee';
        document.getElementById('sumTotal').textContent = money(window.totalAmount);
      }

      document.querySelectorAll('.fc-segment button').forEach(function (b) {
        b.addEventListener('click', function () { setMethod(b.dataset.method); });
      });
      // clicking a payment tile switches the tab as well
      document.querySelectorAll('input[name="payment"]').forEach(function (r) {
        r.addEventListener('change', function () { setMethod(r.value === 'store' ? 'pickup' : 'delivery'); });
      });
    </script>
  </body>
</html>
