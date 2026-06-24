<?php
require 'adminAuth.php';
require '../connection.php';

header('Content-Type: application/json');

if (!isset($_GET['item_id'])) {
    echo json_encode(["status" => "error", "message" => "Item ID is required"]);
    exit();
}

$itemId = (int)$_GET['item_id'];

$history_rs = Database::search("SELECT * FROM `stock_transactions` WHERE `item_id` = $itemId ORDER BY `created_at` DESC LIMIT 5");

$history = [];
if ($history_rs) {
    while ($row = $history_rs->fetch_assoc()) {
        $history[] = [
            "id" => (int)$row['id'],
            "transaction_type" => $row['transaction_type'],
            "quantity" => (float)$row['quantity'],
            "previous_stock" => (float)$row['previous_stock'],
            "new_stock" => (float)$row['new_stock'],
            "reason" => htmlspecialchars($row['reason']),
            "created_at" => date("Y-m-d H:i", strtotime($row['created_at']))
        ];
    }
}

echo json_encode(["status" => "success", "history" => $history]);
?>
