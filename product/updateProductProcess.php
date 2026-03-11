<?php
require("../connection.php");
session_start();

if (!isset($_SESSION['user_id'])) {
	echo json_encode(["success" => false, "message" => "User not logged in"]);
	exit();
}

if (!isset($_POST['cart_item_id']) || !isset($_POST['item_id']) || !isset($_POST['cart_id']) || !isset($_POST['user_id']) || !isset($_POST['quantity'])) {
	echo json_encode(["success" => false, "message" => "Missing required fields"]);
	exit();
}

$session_user_id = (int)$_SESSION['user_id'];
$user_id = (int)$_POST['user_id'];
$cart_item_id = (int)$_POST['cart_item_id'];
$item_id = (int)$_POST['item_id'];
$cart_id = (int)$_POST['cart_id'];
$quantity = (int)$_POST['quantity'];

if ($user_id !== $session_user_id) {
	echo json_encode(["success" => false, "message" => "Unauthorized user"]);
	exit();
}

if ($quantity < 1) {
	$quantity = 1;
}

$cartItemRs = Database::search("SELECT ci.id, it.price AS unit_price
	FROM cart_items ci
	INNER JOIN carts c ON c.id = ci.cart_id
	INNER JOIN items it ON it.id = ci.item_id
	WHERE ci.id='$cart_item_id' AND ci.item_id='$item_id' AND ci.cart_id='$cart_id' AND c.user_id='$session_user_id' AND c.status='Active'");

if (!$cartItemRs || $cartItemRs->num_rows === 0) {
	echo json_encode(["success" => false, "message" => "Cart item not found"]);
	exit();
}

$cartItemData = $cartItemRs->fetch_assoc();
$unit_price = (float)$cartItemData['unit_price'];
$line_total = $unit_price * $quantity;

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

Database::iud("UPDATE cart_items SET quantity='$quantity', price='$line_total', updated_at='$d' WHERE id='$cart_item_id'");

$totalRs = Database::search("SELECT COALESCE(SUM(price), 0) AS subtotal, COUNT(id) AS item_count FROM cart_items WHERE cart_id='$cart_id'");
$totalData = $totalRs->fetch_assoc();
$subtotal = (float)$totalData['subtotal'];
$itemCount = (int)$totalData['item_count'];
$bagCharge = 10;
$grandTotal = $itemCount > 0 ? $subtotal + $bagCharge : 0;

echo json_encode([
	"success" => true,
	"quantity" => $quantity,
	"item_total" => $line_total,
	"subtotal" => $subtotal,
	"item_count" => $itemCount,
	"grand_total" => $grandTotal
]);
?>