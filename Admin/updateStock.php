<?php
require 'adminAuth.php';
require '../connection.php';

if (!isset($_POST['item_id']) || !isset($_POST['stock_quantity'])) {
    echo "Invalid request parameters";
    exit();
}

$itemId = (int)$_POST['item_id'];
$stockQty = (int)$_POST['stock_quantity'];

if ($stockQty < 0) {
    echo "Stock quantity cannot be negative";
    exit();
}

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

// Update stock quantity and updated_at
$update_query = "UPDATE `items` SET `stock_quantity` = $stockQty, `updated_at` = '$d' WHERE `id` = $itemId";
Database::iud($update_query);

echo "success";
?>
