<?php
require 'adminAuth.php';
require '../connection.php';

if (isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    Database::iud("UPDATE orders SET order_status='delivered' WHERE id='$orderId'");
    echo "success";
} else {
    echo "Order ID missing";
}
?>
