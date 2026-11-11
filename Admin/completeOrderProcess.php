<?php
require_once __DIR__ . '/adminAuth.php';
require_once __DIR__ . '/../connection.php';

if (isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    Database::setUpConnection();
    $res = Database::search("SELECT purchase_method FROM orders WHERE id = '$orderId' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $isPickup = (strtolower(trim($row['purchase_method'] ?? '')) === 'pickup');
        $newStatus = $isPickup ? 'collected' : 'delivered';
        Database::iud("UPDATE orders SET order_status = '$newStatus', payment_status = 'paid' WHERE id = '$orderId'");
        echo "success";
    } else {
        echo "Order not found";
    }
} else {
    echo "Order ID missing";
}
?>
