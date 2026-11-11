<?php
require 'adminAuth.php';
require '../connection.php';

if (isset($_POST['order_id']) && (isset($_POST['status']) || isset($_POST['payment_status']))) {
    $orderId = (int)$_POST['order_id'];

    // Order status change (pending → processing → shipped/ready → delivered/collected)
    if (isset($_POST['status'])) {
        $status = $_POST['status'];
        $allowed_statuses = ['pending', 'processing', 'shipped', 'ready', 'delivered', 'collected'];
        if (!in_array($status, $allowed_statuses, true)) {
            echo "Invalid status";
            exit();
        }
        Database::iud("UPDATE orders SET order_status='$status' WHERE id='$orderId'");
        // Cash is settled when the order is handed over
        if ($status === 'delivered' || $status === 'collected') {
            Database::iud("UPDATE orders SET payment_status='Paid' WHERE id='$orderId'");
        }
    }

    // Manual payment status toggle
    if (isset($_POST['payment_status'])) {
        $pay = ($_POST['payment_status'] === 'Paid') ? 'Paid' : 'Unpaid';
        Database::iud("UPDATE orders SET payment_status='$pay' WHERE id='$orderId'");
    }
    echo "success";
} else {
    echo "Missing parameters";
}
?>
