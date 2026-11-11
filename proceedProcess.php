<?php
require_once __DIR__ . "/connection.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─────────────────────────────────────────────
// CSRF Token Validation
// ─────────────────────────────────────────────
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    die("Invalid request (CSRF token mismatch).");
}
// Regenerate token so it can only be used once
unset($_SESSION['csrf_token']);

// ─────────────────────────────────────────────
// 1. Auth check
// ─────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = (int)$_SESSION['user_id'];

// ─────────────────────────────────────────────
// 2. Basic Inputs (sanitised server-side)
// ─────────────────────────────────────────────
Database::setUpConnection();
$db = Database::$connection;

$store = require __DIR__ . '/storeConfig.php';

// Purchase method: 'cod' or 'pickup'
$raw_method = $_POST['purchase_method'] ?? ($_POST['order_method'] ?? 'cod');
$purchase_method = ($raw_method === 'pickup') ? 'pickup' : 'cod';
$payment_status = 'unpaid';

// Customer details: check POST first, fallback to user record
$post_cust_name  = trim($_POST['customer_name'] ?? '');
$post_cust_phone = trim($_POST['customer_phone'] ?? '');

$user_info_rs = Database::search("SELECT first_name, last_name, phone_number, street_address1, street_address2, town FROM users WHERE id='$user_id' LIMIT 1");
$u_row = ($user_info_rs && $user_info_rs->num_rows > 0) ? $user_info_rs->fetch_assoc() : [];

$customer_name  = !empty($post_cust_name) ? $post_cust_name : trim(($u_row['first_name'] ?? '') . ' ' . ($u_row['last_name'] ?? ''));
$customer_phone = !empty($post_cust_phone) ? $post_cust_phone : trim($u_row['phone_number'] ?? '');

if (empty($customer_name)) {
    die("Please provide your full name.");
}
if (empty($customer_phone)) {
    die("Please provide your contact phone number.");
}

$customer_name_escaped  = $db->real_escape_string($customer_name);
$customer_phone_escaped = $db->real_escape_string($customer_phone);

$delivery_notes = $db->real_escape_string(mb_substr(trim($_POST['delivery_notes'] ?? ($_POST['instructions'] ?? '')), 0, 500));

if ($purchase_method === 'pickup') {
    $streetAddress1 = $db->real_escape_string($store['address']);
    $streetAddress2 = '';
    $town           = $db->real_escape_string($store['town']);
    $delivery_fee   = 0.0;
} else {
    $streetAddress1 = $db->real_escape_string(trim($_POST['streetAddress1'] ?? ($_POST['address1'] ?? '')));
    $streetAddress2 = $db->real_escape_string(trim($_POST['streetAddress2'] ?? ($_POST['address2'] ?? '')));
    $town           = $db->real_escape_string(trim($_POST['town'] ?? ''));
    $delivery_fee   = (float)$store['delivery_fee'];

    if (empty($streetAddress1)) {
        die("Please provide your street address.");
    }
    if (empty($town)) {
        die("Please provide your town/city.");
    }

    // Update user's default address in users table for future COD checkouts
    Database::iud("UPDATE users SET street_address1='$streetAddress1', street_address2='$streetAddress2', town='$town' WHERE id='$user_id'");
}

$pickup_code = null;
$pickup_date = null;
$pickup_time_slot = null;

if ($purchase_method === 'pickup') {
    $slot_in = trim($_POST['pickup_time_slot'] ?? ($_POST['pickup_slot'] ?? ''));
    $date_in = trim($_POST['pickup_date'] ?? '');

    if (!in_array($slot_in, $store['pickup_slots'], true)) {
        die("Please choose a valid pickup time slot.");
    }
    $allowed_dates = [];
    for ($d = 0; $d <= (int)$store['pickup_days']; $d++) {
        $allowed_dates[] = (new DateTime("+$d day", new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
    }
    if (!in_array($date_in, $allowed_dates, true)) {
        die("Please choose a valid pickup date.");
    }
    $pickup_date = $date_in;
    $pickup_time_slot = $db->real_escape_string($slot_in);

    // Generate unique 6-character alphanumeric pickup code (e.g. PK-84920)
    $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    do {
        $rand_str = '';
        for ($i = 0; $i < 5; $i++) {
            $rand_str .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $pickup_code = 'PK-' . $rand_str;
        $dup = Database::search("SELECT id FROM orders WHERE pickup_code='$pickup_code' LIMIT 1");
    } while ($dup && $dup->num_rows > 0);
}

// Check active cart items
$active_cart_rs = Database::search("SELECT id FROM carts WHERE user_id='$user_id' AND status='Active' ORDER BY id DESC LIMIT 1");
if (!$active_cart_rs || $active_cart_rs->num_rows === 0) {
    die("No active cart found.");
}
$active_cart_id = (int)$active_cart_rs->fetch_assoc()['id'];

$cart_items_db_rs = Database::search("
    SELECT ci.item_id, ci.quantity, i.name, i.price, i.stock_quantity, i.status
    FROM cart_items ci
    INNER JOIN items i ON i.id = ci.item_id
    WHERE ci.cart_id = '$active_cart_id'
");

if (!$cart_items_db_rs || $cart_items_db_rs->num_rows === 0) {
    die("Your cart is empty.");
}

// ─────────────────────────────────────────────
// 3. Re-verify stock & recalculate exact totals from server
// ─────────────────────────────────────────────
$subtotal = 0.0;
$order_items_data = [];

while ($c_item = $cart_items_db_rs->fetch_assoc()) {
    $iid        = (int)$c_item['item_id'];
    $qty        = (float)$c_item['quantity'];
    $item_name  = $c_item['name'];
    $unit_price = (float)$c_item['price'];
    $avail_stock = (float)$c_item['stock_quantity'];
    $item_status = $c_item['status'];

    if ($qty <= 0) continue;

    if ($item_status !== 'active' || $avail_stock <= 0) {
        die("Sorry, \"$item_name\" is currently out of stock.");
    }

    if ($qty > $avail_stock) {
        die("Sorry, only " . number_format($avail_stock, 2) . " unit(s) of \"$item_name\" are available in stock.");
    }

    $line_subtotal = $qty * $unit_price;
    $subtotal += $line_subtotal;

    $order_items_data[] = [
        'item_id'    => $iid,
        'name'       => $item_name,
        'qty'        => $qty,
        'price'      => $unit_price,
        'subtotal'   => $line_subtotal,
        'prev_stock' => $avail_stock,
        'new_stock'  => max(0.0, $avail_stock - $qty),
    ];
}

if (empty($order_items_data)) {
    die("No valid order items found in cart.");
}

$total_amount = $subtotal + $delivery_fee;

$tz         = new DateTimeZone("Asia/Colombo");
$date       = new DateTime("now", $tz);
$created_at = $date->format("Y-m-d H:i:s");

// ─────────────────────────────────────────────
// 4. Begin Database Transaction (ACID)
// ─────────────────────────────────────────────
$db->begin_transaction();

try {
    // A. Re-verify item stocks inside transaction with FOR UPDATE locking
    foreach ($order_items_data as &$oi) {
        $iid = $oi['item_id'];
        $lock_rs = $db->query("SELECT stock_quantity, status FROM items WHERE id='$iid' FOR UPDATE");
        if (!$lock_rs || $lock_rs->num_rows === 0) {
            throw new Exception("Product ID $iid not found during stock lock.");
        }
        $lock_row = $lock_rs->fetch_assoc();
        $fresh_stock = (float)$lock_row['stock_quantity'];
        if ($lock_row['status'] !== 'active' || $oi['qty'] > $fresh_stock) {
            throw new Exception("Stock changed for \"{$oi['name']}\". Only $fresh_stock available.");
        }
        $oi['prev_stock'] = $fresh_stock;
        $oi['new_stock']  = max(0.0, $fresh_stock - $oi['qty']);
    }
    unset($oi);

    // B. Insert Order
    $pc_sql = $pickup_code === null ? 'NULL' : "'$pickup_code'";
    $pd_sql = $pickup_date === null ? 'NULL' : "'$pickup_date'";
    $ps_sql = $pickup_time_slot === null ? 'NULL' : "'$pickup_time_slot'";
    $dn_sql = empty($delivery_notes) ? 'NULL' : "'$delivery_notes'";

    $order_sql = "INSERT INTO orders
                    (order_number, user_id, purchase_method, payment_status,
                     customer_name, customer_phone, total_amount, delivery_fee,
                     street_address1, street_address2, town, delivery_notes,
                     pickup_date, pickup_time_slot, pickup_code, order_status, created_at)
                  VALUES
                    ('', '$user_id', '$purchase_method', '$payment_status',
                     '$customer_name_escaped', '$customer_phone_escaped', '$total_amount', '$delivery_fee',
                     '$streetAddress1', '$streetAddress2', '$town', $dn_sql,
                     $pd_sql, $ps_sql, $pc_sql, 'pending', '$created_at')";

    if (!$db->query($order_sql)) {
        throw new Exception("Order creation failed: " . $db->error);
    }

    $order_id = (int)$db->insert_id;
    if ($order_id <= 0) {
        throw new Exception("Invalid order ID generated.");
    }

    $order_number = str_pad($order_id, 5, '0', STR_PAD_LEFT);
    if (!$db->query("UPDATE orders SET order_number='$order_number' WHERE id='$order_id'")) {
        throw new Exception("Failed to assign order number: " . $db->error);
    }

    // C. Insert Order Items & Decrement Stock & Record Stock Transactions
    $method_label = ($purchase_method === 'pickup') ? 'Store Pickup' : 'COD';
    $reason_text  = $db->real_escape_string("Customer Order #$order_number ($method_label)");

    foreach ($order_items_data as $oi) {
        $iid        = $oi['item_id'];
        $qty        = $oi['qty'];
        $unit_price = $oi['price'];
        $sub        = $oi['subtotal'];
        $new_stock  = $oi['new_stock'];
        $prev_stock = $oi['prev_stock'];

        $item_insert = "INSERT INTO order_items (order_id, item_id, quantity, price, subtotal)
                        VALUES ('$order_id', '$iid', '$qty', '$unit_price', '$sub')";
        if (!$db->query($item_insert)) {
            throw new Exception("Failed to save order item: " . $db->error);
        }

        $stock_update = "UPDATE items SET stock_quantity = '$new_stock' WHERE id = '$iid'";
        if (!$db->query($stock_update)) {
            throw new Exception("Failed to deduct stock: " . $db->error);
        }

        $tx_insert = "INSERT INTO stock_transactions
                        (item_id, transaction_type, quantity, previous_stock, new_stock, reason, created_at)
                      VALUES
                        ('$iid', 'out', '$qty', '$prev_stock', '$new_stock', '$reason_text', '$created_at')";
        if (!$db->query($tx_insert)) {
            throw new Exception("Failed to log stock transaction: " . $db->error);
        }
    }

    // D. Mark Active Cart as Ordered
    if (!$db->query("UPDATE carts SET status='ordered' WHERE id='$active_cart_id'")) {
        throw new Exception("Failed to update cart status: " . $db->error);
    }

    // Commit transaction
    $db->commit();

} catch (Exception $e) {
    $db->rollback();
    die($e->getMessage());
}

// ─────────────────────────────────────────────
// 5. Store session and return success with order_id
// ─────────────────────────────────────────────
$_SESSION['last_order_number'] = $order_number;
$_SESSION['last_order_id']     = $order_id;

echo "success:" . $order_id;
exit();
?>