<?php
require("connection.php");
session_start();

// 1. Basic Inputs
$user_id = $_POST['user_id'] ?? $_SESSION['user_id'] ?? null;
$streetAddress1 = $_POST['streetAddress1'] ?? '';
$streetAddress2 = $_POST['streetAddress2'] ?? '';
$town = $_POST['town'] ?? ''; 
$total_amount = $_POST['total_amount'] ?? 0;
$delivery_fee = $_POST['delivery_fee'] ?? 0;

// 2. Arrays (Automatically handled by PHP because of [] in JS)
$item_ids    = $_POST['item_id']    ?? [];
$quantities  = $_POST['quantity']   ?? [];
$subtotals   = $_POST['subtotal']   ?? [];
$item_prices = $_POST['item_price'] ?? [];


if(empty($streetAddress1)){
    die("Please provide your address.");
}
if(empty($streetAddress2)){
    die("Please provide your address.");
}
if(empty($town)){
    die("Please provide your town.");
}
if(empty($total_amount) || $total_amount <= 0){
    die("Invalid total amount.");
}
if(empty($item_ids) || empty($quantities) || empty($subtotals) || empty($item_prices)){
    die("Order items are missing.");
}
if (empty($user_id)) {
    die("User not logged in.");
}


// 4. Update User Address
Database::iud("UPDATE users SET street_address1='$streetAddress1', street_address2='$streetAddress2', town='$town' WHERE id='$user_id'");

// 5. Insert Order
$tz = new DateTimeZone("Asia/Colombo");
$date = new DateTime("now", $tz);
$created_at = $date->format("Y-m-d H:i:s");

// Insert order without order_number first (set as NULL or '')
$order_sql = "INSERT INTO orders (order_number, user_id, total_amount, delivery_fee, created_at) VALUES ('', '$user_id', '$total_amount', '$delivery_fee', '$created_at')";
Database::iud($order_sql);

// Get the last inserted order ID
$order_id_rs = Database::search("SELECT LAST_INSERT_ID() AS oid");
$order_id = null;
if ($order_id_rs && $row = $order_id_rs->fetch_assoc()) {
    $order_id = $row['oid'];
}

if (!$order_id) {
    die("Order creation failed.");
}

// Format order number as 5 digits, e.g., 00001
$order_number = str_pad($order_id, 5, '0', STR_PAD_LEFT);

// Update the order with the order number
Database::iud("UPDATE orders SET order_number='$order_number' WHERE id='$order_id'");

// 6. Insert Order Items and Update Stock / Log Transactions
foreach ($item_ids as $index => $item_id) {
    $qty = (float)($quantities[$index] ?? 0);
    $price = $item_prices[$index] ?? 0;
    $sub = $subtotals[$index] ?? 0;

    if (!empty($item_id)) {
        $item_sql = "INSERT INTO order_items (order_id, item_id, quantity, price, subtotal) 
                     VALUES ('$order_id', '$item_id', '$qty', '$price', '$sub')";
        Database::iud($item_sql);

        // Decrement stock and write audit transaction log
        $stock_rs = Database::search("SELECT stock_quantity, name FROM items WHERE id = '$item_id' LIMIT 1");
        if ($stock_rs && $stock_rs->num_rows > 0) {
            $stock_row = $stock_rs->fetch_assoc();
            $prev_stock = (float)$stock_row['stock_quantity'];
            $new_stock = max(0.000, $prev_stock - $qty);

            // Update items table
            Database::iud("UPDATE items SET stock_quantity = '$new_stock' WHERE id = '$item_id'");

            // Log stock transaction
            $reason = "Customer Order #" . $order_number;
            Database::setUpConnection();
            $reason_escaped = Database::$connection->real_escape_string($reason);
            Database::iud("INSERT INTO stock_transactions (item_id, transaction_type, quantity, previous_stock, new_stock, reason, created_at) 
                           VALUES ('$item_id', 'out', '$qty', '$prev_stock', '$new_stock', '$reason_escaped', '$created_at')");
        }
    }
}

// 7. Update status of the current active cart to 'Ordered'
Database::iud("UPDATE carts SET status='Ordered' WHERE user_id='$user_id' AND status='Active'");

echo "success";
?>