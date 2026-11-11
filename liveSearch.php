<?php
/**
 * AJAX Live Search Endpoint for Freshcomart Storefront
 * Returns JSON formatted product matches with categories and stock status
 */
require_once __DIR__ . '/connection.php';
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$catId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if (strlen($q) < 2) {
    echo json_encode(['status' => 'success', 'results' => []]);
    exit();
}

Database::setUpConnection();
$db = Database::$connection;
$escapedQ = $db->real_escape_string($q);

$where = ["(i.name LIKE '%$escapedQ%' OR c.name LIKE '%$escapedQ%')"];

if ($catId > 0) {
    $where[] = "i.category_id = '$catId'";
}

$whereSql = implode(' AND ', $where);

$query = "
    SELECT i.id, i.name, i.price, i.stock_quantity, i.unit, i.image_path, i.status, i.category_id,
           COALESCE(c.name, 'General') AS category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE $whereSql
    ORDER BY 
        CASE WHEN i.name LIKE '$escapedQ%' THEN 1 WHEN i.name LIKE '%$escapedQ%' THEN 2 ELSE 3 END,
        i.stock_quantity > 0 DESC,
        i.name ASC
    LIMIT 10
";

$res = Database::search($query);
$results = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $stock = (float)$row['stock_quantity'];
        $isActive = ($row['status'] === 'active');
        $inStock = ($isActive && $stock > 0);
        
        $results[] = [
            'id'            => (int)$row['id'],
            'name'          => $row['name'],
            'price'         => (float)$row['price'],
            'price_formatted' => 'Rs. ' . number_format((float)$row['price'], 2),
            'stock_quantity' => $stock,
            'in_stock'      => $inStock,
            'unit'          => $row['unit'] ?: 'unit',
            'image_path'    => $row['image_path'],
            'category_id'   => (int)$row['category_id'],
            'category_name' => $row['category_name']
        ];
    }
}

echo json_encode([
    'status'  => 'success',
    'query'   => $q,
    'count'   => count($results),
    'results' => $results
]);

