<?php
require 'adminAuth.php';
require '../connection.php';

if (!isset($_POST['item_id']) || !isset($_POST['transaction_type']) || !isset($_POST['quantity']) || !isset($_POST['reason'])) {
    echo "Invalid request parameters";
    exit();
}

$itemId = (int)$_POST['item_id'];
$type = $_POST['transaction_type']; // 'in', 'out', 'adjustment'
$qty = (float)$_POST['quantity'];
$reason = $_POST['reason'];

if ($itemId <= 0 || $qty < 0) {
    echo "Invalid quantities";
    exit();
}

// 1. Fetch current stock
$item_rs = Database::search("SELECT `stock_quantity`, `name` FROM `items` WHERE `id` = $itemId LIMIT 1");
if ($item_rs->num_rows == 0) {
    echo "Item not found";
    exit();
}
$item_row = $item_rs->fetch_assoc();
$prev_stock = (float)$item_row['stock_quantity'];

// 2. Calculate new stock
$new_stock = $prev_stock;
$tx_type = 'adjustment';

if ($type === 'in') {
    $new_stock = $prev_stock + $qty;
    $tx_type = 'in';
} elseif ($type === 'out') {
    if ($prev_stock < $qty) {
        echo "Error: Cannot remove more stock than is currently available ($prev_stock).";
        exit();
    }
    $new_stock = $prev_stock - $qty;
    $tx_type = 'out';
} elseif ($type === 'adjustment') {
    $new_stock = $qty; // Qty is the absolute target
    $tx_type = 'adjustment';
} else {
    echo "Invalid transaction type";
    exit();
}

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

// 3. Update items table
Database::iud("UPDATE `items` SET `stock_quantity` = $new_stock, `updated_at` = '$d' WHERE `id` = $itemId");

// 4. Log transaction
Database::setUpConnection();
$reason_escaped = Database::$connection->real_escape_string($reason);
Database::iud("INSERT INTO `stock_transactions` (item_id, transaction_type, quantity, previous_stock, new_stock, reason, created_at) 
               VALUES ($itemId, '$tx_type', $qty, $prev_stock, $new_stock, '$reason_escaped', '$d')");

echo "success";
?>
