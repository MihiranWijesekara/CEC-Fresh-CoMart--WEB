<?php
require_once __DIR__ . '/adminAuth.php';
require_once __DIR__ . '/../connection.php';

if (isset($_POST['order_id']) && (isset($_POST['status']) || isset($_POST['payment_status']))) {
    $orderId = (int)$_POST['order_id'];
    Database::setUpConnection();
    $db = Database::$connection;

    // Fetch order record
    $orderRes = $db->query("SELECT id, order_number, order_status, purchase_method, payment_status FROM orders WHERE id = '$orderId' LIMIT 1");
    if (!$orderRes || $orderRes->num_rows === 0) {
        echo "Order not found";
        exit();
    }
    $order = $orderRes->fetch_assoc();
    $currentStatus = $order['order_status'];
    $orderNo = !empty($order['order_number']) ? $order['order_number'] : str_pad($orderId, 5, '0', STR_PAD_LEFT);

    // Status change
    if (isset($_POST['status'])) {
        $status = strtolower(trim($_POST['status']));
        $allowed = ['pending', 'processing', 'ready_for_pickup', 'ready', 'shipped', 'delivered', 'collected', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            echo "Invalid status";
            exit();
        }

        $targetStatus = ($status === 'ready') ? 'ready_for_pickup' : $status;

        // If target is cancelled and order was not already cancelled: restore stock!
        if ($targetStatus === 'cancelled' && $currentStatus !== 'cancelled') {
            $db->begin_transaction();
            try {
                $itemsRs = $db->query("SELECT item_id, quantity FROM order_items WHERE order_id = '$orderId'");
                if ($itemsRs) {
                    while ($item = $itemsRs->fetch_assoc()) {
                        $itemId = (int)$item['item_id'];
                        $qty = (float)$item['quantity'];

                        $stkRs = $db->query("SELECT stock_quantity FROM items WHERE id = '$itemId' FOR UPDATE");
                        if ($stkRs && $stkRow = $stkRs->fetch_assoc()) {
                            $prevStock = (float)$stkRow['stock_quantity'];
                            $newStock = $prevStock + $qty;

                            $db->query("UPDATE items SET stock_quantity = '$newStock' WHERE id = '$itemId'");
                            $reason = $db->real_escape_string("Order #{$orderNo} Cancelled by Admin");
                            $db->query("
                                INSERT INTO stock_transactions 
                                (item_id, transaction_type, quantity, previous_stock, new_stock, reason, created_at)
                                VALUES 
                                ('$itemId', 'in', '$qty', '$prevStock', '$newStock', '$reason', NOW())
                            ");
                        }
                    }
                }
                $db->query("UPDATE orders SET order_status = 'cancelled', payment_status = 'cancelled' WHERE id = '$orderId'");
                $db->commit();
                echo "success";
                exit();
            } catch (Exception $e) {
                $db->rollback();
                echo "Error cancelling order: " . $e->getMessage();
                exit();
            }
        }

        // Automatic payment settlement when delivered (COD) or collected (Pickup)
        if ($targetStatus === 'delivered' || $targetStatus === 'collected') {
            $db->query("UPDATE orders SET order_status = '$targetStatus', payment_status = 'paid' WHERE id = '$orderId'");
        } else {
            $db->query("UPDATE orders SET order_status = '$targetStatus' WHERE id = '$orderId'");
        }
    }

    // Manual payment toggle
    if (isset($_POST['payment_status'])) {
        $rawPay = strtolower(trim($_POST['payment_status']));
        $payStatus = ($rawPay === 'paid') ? 'paid' : ($rawPay === 'cancelled' ? 'cancelled' : 'unpaid');
        $db->query("UPDATE orders SET payment_status = '$payStatus' WHERE id = '$orderId'");
    }

    echo "success";
} else {
    echo "Missing parameters";
}
?>
