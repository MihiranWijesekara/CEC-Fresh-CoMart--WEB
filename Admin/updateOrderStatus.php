<?php
require 'adminAuth.php';
require '../connection.php';

if (isset($_POST['order_id']) && (isset($_POST['status']) || isset($_POST['payment_status']))) {
    $orderId = (int)$_POST['order_id'];

    // Order status change (pending → processing → shipped/ready → delivered/collected)
    if (isset($_POST['status'])) {
        $status = $_POST['status'];
        $allowed_statuses = ['pending', 'processing', 'ready_for_pickup', 'ready', 'shipped', 'delivered', 'collected', 'cancelled'];
        if (!in_array($status, $allowed_statuses, true)) {
            echo "Invalid status";
            exit();
        }
        // Normalize 'ready' to 'ready_for_pickup' if passed
        $db_status = ($status === 'ready') ? 'ready_for_pickup' : $status;
        Database::iud("UPDATE orders SET order_status='$db_status' WHERE id='$orderId'");
        // Cash is settled when the order is handed over
        if ($db_status === 'delivered' || $db_status === 'collected') {
            Database::iud("UPDATE orders SET payment_status='paid' WHERE id='$orderId'");
        }
    }

    // Manual payment status toggle
    if (isset($_POST['payment_status'])) {
        $raw_pay = strtolower(trim($_POST['payment_status']));
        $pay = ($raw_pay === 'paid') ? 'paid' : ($raw_pay === 'cancelled' ? 'cancelled' : 'unpaid');
        Database::iud("UPDATE orders SET payment_status='$pay' WHERE id='$orderId'");
    }
    echo "success";
} else {
    echo "Missing parameters";
}
?>
