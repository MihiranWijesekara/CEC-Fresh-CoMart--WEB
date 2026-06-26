<?php
require("../connection.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User not logged in";
    exit();
}

$user_id  = $_SESSION['user_id'];
$itemID   = $_POST['item_id'];
$quantity = (float)$_POST['quantity'];

// Fetch the authoritative unit price directly from the DB — never trust JS-sent price
$item_rs = Database::search("SELECT price FROM items WHERE id='" . (int)$itemID . "' LIMIT 1");
if (!$item_rs || $item_rs->num_rows === 0) {
    echo "Item not found";
    exit();
}
$item_data = $item_rs->fetch_assoc();
$unit_price = (float)$item_data['price']; // price per unit from DB (trusted source)

$date = new DateTime();
$tz   = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

// Check if user already has active cart
$cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active'");

if ($cart_rs->num_rows == 0) {
    // Create new cart
    Database::iud("INSERT INTO carts (user_id, status, created_at)
                   VALUES ('$user_id', 'Active', '$d')");
    $cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active'");
}

$cart_data = $cart_rs->fetch_assoc();
$cart_id   = $cart_data['id'];

$existing_item_rs = Database::search(
    "SELECT id, quantity FROM cart_items WHERE cart_id='$cart_id' AND item_id='$itemID' LIMIT 1"
);

if ($existing_item_rs && $existing_item_rs->num_rows > 0) {
    $existing = $existing_item_rs->fetch_assoc();
    $new_qty  = (float)$existing['quantity'] + $quantity;
    $new_price = $new_qty * $unit_price; // recalculate total price from unit price
    $cart_item_id = (int)$existing['id'];

    Database::iud(
        "UPDATE cart_items SET quantity='$new_qty', price='$new_price', updated_at='$d'
         WHERE id='$cart_item_id'"
    );
    echo "Item quantity updated successfully!";
} else {
    $total_price = $quantity * $unit_price;
    Database::iud(
        "INSERT INTO cart_items (cart_id, item_id, quantity, price, created_at)
         VALUES ('$cart_id', '$itemID', '$quantity', '$total_price', '$d')"
    );
    echo "Item added successfully!";
}
