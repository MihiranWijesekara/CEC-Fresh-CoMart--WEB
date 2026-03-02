
<?php
require("../connection.php");

$itemID  = $_POST['item_id'];
$quantity  = $_POST['quantity'];
$price  = $_POST['price'];

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

$urs = Database::search("SELECT * FROM `carts` WHERE `status`='" Active "'");
$un = $urs->num_rows;

// // Receive AJAX POST data
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// 	$item_id = isset($_POST['item_id']) ? $_POST['item_id'] : null;
// 	$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : null;
// 	$price = isset($_POST['price']) ? $_POST['price'] : null;

// 	// Here you would add logic to add to cart, update DB, etc.
// 	// For now, just return a success message with the received data
// 	if ($item_id && $quantity && $price) {
// 		echo "success: item_id=$item_id, quantity=$quantity, price=$price";
// 	} else {
// 		echo "error: missing data";
// 	}
// 	exit;
// }

?>