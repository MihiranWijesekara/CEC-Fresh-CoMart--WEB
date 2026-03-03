<?php
require("../connection.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User not logged in";
    exit();
}

$user_id = $_SESSION['user_id'];
$itemID  = $_POST['item_id'];
$quantity  = $_POST['quantity'];
$price  = $_POST['price'];

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

// 🔎 Check if user already has active cart
$cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active'");

if ($cart_rs->num_rows == 0) {

    // 🆕 Create new cart
    Database::iud("INSERT INTO carts (user_id, status, created_at) 
                   VALUES ('$user_id', 'Active', '$d')");

    $cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active'");
}

$cart_data = $cart_rs->fetch_assoc();
$cart_id = $cart_data['id'];

// 🛒 Insert item into cart_items table
Database::iud("INSERT INTO cart_items (cart_id, item_id, quantity, price, created_at)
               VALUES ('$cart_id', '$itemID', '$quantity', '$price', '$d')");

echo "Item added successfully!";
?>