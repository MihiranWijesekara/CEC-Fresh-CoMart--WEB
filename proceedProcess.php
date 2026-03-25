<?php
require("connection.php");
session_start();

$user_id = isset($_POST['user_id:']) ? $_POST['user_id:'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
$streetAddress1 = isset($_POST['streetAddress1']) ? $_POST['streetAddress1'] : '';
$streetAddress2 = isset($_POST['streetAddress2']) ? $_POST['streetAddress2'] : '';
$town = isset($_POST['town']) ? $_POST['town'] : '';
$total_amount = isset($_POST['total_amount:']) ? $_POST['total_amount:'] : 0;
$delivery_fee = isset($_POST['delivery_fee:']) ? $_POST['delivery_fee:'] : 0;

// Collect order items
$item_ids = [];
$quantities = [];
$subtotals = [];
$item_prices = [];
foreach ($_POST as $key => $value) {
	if (preg_match('/^item_id\\[(\\d+)\\]$/', $key, $m)) {
		$item_ids[(int)$m[1]] = $value;
	} elseif (preg_match('/^quantity\\[(\\d+)\\]$/', $key, $m)) {
		$quantities[(int)$m[1]] = $value;
	} elseif (preg_match('/^subtotal\\[(\\d+)\\]$/', $key, $m)) {
		$subtotals[(int)$m[1]] = $value;
	} elseif (preg_match('/^item_price\\[(\\d+)\\]$/', $key, $m)) {
		$item_prices[(int)$m[1]] = $value;
	}
}

// Validate required fields
if (empty($user_id)) {
	echo ("User not logged in.");
	exit();
}
if (empty($streetAddress1) || empty($town)) {
	echo ("Please provide your address and town.");
	exit();
}
if (empty($item_ids)) {
	echo ("No order items found.");
	exit();
}

// Update user address
$update_user = Database::iud("UPDATE users SET street_address1='" . $streetAddress1 . "', street_address2='" . $streetAddress2 . "', town='" . $town . "' WHERE id='" . $user_id . "'");

// Insert order
$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$created_at = $date->format("Y-m-d H:i:s");

$order_id = null;
$order_sql = "INSERT INTO orders (user_id, total_amount, delivery_fee, created_at) VALUES ('" . $user_id . "', '" . $total_amount . "', '" . $delivery_fee . "', '" . $created_at . "')";
$order_result = Database::iud($order_sql);
if ($order_result) {
	$order_id_rs = Database::search("SELECT LAST_INSERT_ID() AS oid");
	if ($order_id_rs && $row = $order_id_rs->fetch_assoc()) {
		$order_id = $row['oid'];
	}
}
if (!$order_id) {
	echo ("Order creation failed.");
	exit();
}

// Insert order items
for ($i = 0; $i < count($item_ids); $i++) {
	$item_id = isset($item_ids[$i]) ? $item_ids[$i] : null;
	$qty = isset($quantities[$i]) ? $quantities[$i] : 0;
	$subtotal = isset($subtotals[$i]) ? $subtotals[$i] : 0;
	$item_price = isset($item_prices[$i]) ? $item_prices[$i] : 0;
	if ($item_id) {
		$item_sql = "INSERT INTO order_items (order_id, item_id, quantity, price, subtotal) VALUES ('" . $order_id . "', '" . $item_id . "', '" . $qty . "', '" . $item_price . "', '" . $subtotal . "')";
		Database::iud($item_sql);
	}
}

echo "success";
?>