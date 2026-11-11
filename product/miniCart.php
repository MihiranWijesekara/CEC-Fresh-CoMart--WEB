<?php
// Read-only JSON feed for the header mini-cart drawer.
require_once '../connection.php';
session_start();
header('Content-Type: application/json');

$out = ['count' => 0, 'subtotal' => '0.00', 'items' => []];

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $rs = Database::search(
        "SELECT ci.quantity, ci.price, i.name, i.unit, i.image_path, i.category_id
         FROM carts c
         INNER JOIN cart_items ci ON ci.cart_id = c.id
         INNER JOIN items i ON i.id = ci.item_id
         WHERE c.user_id='$uid' AND c.status='Active'
         ORDER BY ci.id DESC"
    );
    $subtotal = 0.0;
    if ($rs) {
        while ($r = $rs->fetch_assoc()) {
            $line_total = (float)$r['quantity'] * (float)$r['price'];
            $subtotal += $line_total;
            $out['items'][] = [
                'name'       => $r['name'],
                'unit'       => $r['unit'],
                'image'      => $r['image_path'],
                'qty'        => (string)Database::formatQuantity($r['quantity'], $r['unit'], $r['category_id']),
                'line_total' => number_format($line_total, 2),
            ];
        }
    }
    $out['count']    = count($out['items']);
    $out['subtotal'] = number_format($subtotal, 2);
}

echo json_encode($out);

