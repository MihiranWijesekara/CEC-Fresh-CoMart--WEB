<?php
require_once __DIR__ . '/../connection.php';

$res = Database::search("SELECT id, user_id, total_amount, delivery_fee, order_status, purchase_method, payment_status, customer_name, customer_phone, street_address1, delivery_notes, pickup_code FROM orders ORDER BY id DESC LIMIT 5");

echo "=== LATEST ORDERS IN DATABASE ===\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

$stock = Database::search("SELECT id, name, stock_quantity FROM items WHERE id=22")->fetch_assoc();
echo "\n=== ITEM 22 STOCK ===\n";
print_r($stock);

$tx = Database::search("SELECT * FROM stock_transactions WHERE item_id=22 ORDER BY id DESC LIMIT 3");
echo "\n=== RECENT STOCK TRANSACTIONS ===\n";
while ($r = $tx->fetch_assoc()) {
    print_r($r);
}

