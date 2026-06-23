<?php
require 'adminAuth.php';
require '../connection.php';

if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered'];
    if (in_array($status, $allowed_statuses)) {
        Database::iud("UPDATE orders SET order_status='$status' WHERE id='$orderId'");
        echo "success";
    } else {
        echo "Invalid status";
    }
} else {
    echo "Missing parameters";
}
?>
