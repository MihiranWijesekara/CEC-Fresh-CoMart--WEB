<?php
require("connection.php");
session_start();

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

$streetAddress1 = $db->real_escape_string(trim($_POST['streetAddress1'] ?? ''));
$streetAddress2 = $db->real_escape_string(trim($_POST['streetAddress2'] ?? ''));
$town           = $db->real_escape_string(trim($_POST['town']           ?? ''));

$delivery_fee = (float)($_POST['delivery_fee'] ?? 0);

// Arrays sent from JS with [] suffix
$item_ids    = $_POST['item_id']    ?? [];
$quantities  = $_POST['quantity']   ?? [];

if (empty($streetAddress1)) {
    die("Please provide your street address.");
}
if (empty($town)) {
    die("Please provide your town.");
}
if (empty($item_ids) || empty($quantities)) {
    die("Order items are missing.");
}

// ─────────────────────────────────────────────
// 3. Recalculate totals from DB (never trust JS prices)
// ─────────────────────────────────────────────
$subtotal = 0.0;
$order_items_data = [];

foreach ($item_ids as $index => $item_id) {
    $item_id = (int)$item_id;
    $qty     = (float)($quantities[$index] ?? 0);
    if ($item_id <= 0 || $qty <= 0) continue;

    // ── Stock check (Issue #6) ──────────────────
    $stock_rs = Database::search(
        "SELECT stock_quantity, name, price FROM items WHERE id='$item_id' LIMIT 1"
    );
    if (!$stock_rs || $stock_rs->num_rows === 0) {
        die("Item ID $item_id not found.");
    }
    $stock_row   = $stock_rs->fetch_assoc();
    $avail_stock = (float)$stock_row['stock_quantity'];
    $item_name   = $stock_row['name'];
    $unit_price  = (float)$stock_row['price'];

    if ($qty > $avail_stock) {
        die("Sorry, only " . number_format($avail_stock, 2) . " units of \"$item_name\" are available in stock.");
    }

    $line_subtotal = $qty * $unit_price;
    $subtotal     += $line_subtotal;

    $order_items_data[] = [
        'item_id'   => $item_id,
        'qty'       => $qty,
        'price'     => $unit_price,
        'subtotal'  => $line_subtotal,
        'prev_stock'=> $avail_stock,
        'new_stock' => max(0.0, $avail_stock - $qty),
    ];
}

if (empty($order_items_data)) {
    die("No valid order items.");
}

$total_amount = $subtotal + $delivery_fee;

// ─────────────────────────────────────────────
// 4. Save delivery address — store separately, do NOT overwrite
//    permanently the user's profile address (Issue #4).
//    Instead we save it in the order itself.
// ─────────────────────────────────────────────
$tz         = new DateTimeZone("Asia/Colombo");
$date       = new DateTime("now", $tz);
$created_at = $date->format("Y-m-d H:i:s");

// ─────────────────────────────────────────────
// 5. Insert Order
// ─────────────────────────────────────────────
$order_sql = "INSERT INTO orders
                (order_number, user_id, total_amount, delivery_fee,
                 street_address1, street_address2, town, created_at)
              VALUES
                ('', '$user_id', '$total_amount', '$delivery_fee',
                 '$streetAddress1', '$streetAddress2', '$town', '$created_at')";
Database::iud($order_sql);

$order_id_rs = Database::search("SELECT LAST_INSERT_ID() AS oid");
$order_id    = null;
if ($order_id_rs && $row = $order_id_rs->fetch_assoc()) {
    $order_id = (int)$row['oid'];
}

if (!$order_id) {
    die("Order creation failed.");
}

$order_number = str_pad($order_id, 5, '0', STR_PAD_LEFT);
Database::iud("UPDATE orders SET order_number='$order_number' WHERE id='$order_id'");

// ─────────────────────────────────────────────
// 6. Insert Order Items & Decrement Stock
// ─────────────────────────────────────────────
foreach ($order_items_data as $oi) {
    $iid       = $oi['item_id'];
    $qty       = $oi['qty'];
    $price     = $oi['price'];
    $sub       = $oi['subtotal'];
    $new_stock = $oi['new_stock'];
    $prev_stock= $oi['prev_stock'];

    Database::iud(
        "INSERT INTO order_items (order_id, item_id, quantity, price, subtotal)
         VALUES ('$order_id', '$iid', '$qty', '$price', '$sub')"
    );

    Database::iud(
        "UPDATE items SET stock_quantity = '$new_stock' WHERE id = '$iid'"
    );

    $reason         = "Customer Order #$order_number";
    $reason_escaped = $db->real_escape_string($reason);
    Database::iud(
        "INSERT INTO stock_transactions
            (item_id, transaction_type, quantity, previous_stock, new_stock, reason, created_at)
         VALUES
            ('$iid', 'out', '$qty', '$prev_stock', '$new_stock', '$reason_escaped', '$created_at')"
    );
}

// ─────────────────────────────────────────────
// 7. Mark cart as ordered
// ─────────────────────────────────────────────
Database::iud("UPDATE carts SET status='Ordered' WHERE user_id='$user_id' AND status='Active'");

// ─────────────────────────────────────────────
// 8. Store order reference in session for confirmation page
// ─────────────────────────────────────────────
$_SESSION['last_order_number'] = $order_number;
$_SESSION['last_order_id']     = $order_id;

echo "success";
?>