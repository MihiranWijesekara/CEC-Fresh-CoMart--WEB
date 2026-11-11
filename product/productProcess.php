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

// Fetch item directly from DB to validate stock, status, and authoritative unit price
$item_rs = Database::search("SELECT name, price, stock_quantity, unit, category_id, status FROM items WHERE id='" . (int)$itemID . "' LIMIT 1");
if (!$item_rs || $item_rs->num_rows === 0) {
    echo "Item not found";
    exit();
}
$item_data = $item_rs->fetch_assoc();
$unit_price = (float)$item_data['price']; // Unit price from DB
$stock_qty  = (float)$item_data['stock_quantity'];
$item_name  = $item_data['name'];
$item_status = $item_data['status'];

if ($item_status !== 'active' || $stock_qty <= 0) {
    echo "Sorry, \"$item_name\" is currently out of stock.";
    exit();
}

$date = new DateTime();
$tz   = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

// Ensure only one active cart exists per user
$cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active' ORDER BY id DESC");

if ($cart_rs->num_rows == 0) {
    // Create new cart
    Database::iud("INSERT INTO carts (user_id, status, created_at)
                   VALUES ('$user_id', 'Active', '$d')");
    $cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active' ORDER BY id DESC LIMIT 1");
} elseif ($cart_rs->num_rows > 1) {
    // Deduplicate if multiple active carts exist: keep the latest, mark others as ordered
    $latest = $cart_rs->fetch_assoc();
    $latest_id = $latest['id'];
    Database::iud("UPDATE carts SET status='ordered' WHERE user_id='$user_id' AND status='Active' AND id != '$latest_id'");
    $cart_rs = Database::search("SELECT * FROM carts WHERE id='$latest_id'");
}

$cart_data = $cart_rs->fetch_assoc();
$cart_id   = $cart_data['id'];

$existing_item_rs = Database::search(
    "SELECT id, quantity FROM cart_items WHERE cart_id='$cart_id' AND item_id='$itemID' LIMIT 1"
);

if ($existing_item_rs && $existing_item_rs->num_rows > 0) {
    $existing = $existing_item_rs->fetch_assoc();
    $new_qty  = (float)$existing['quantity'] + $quantity;
    $cart_item_id = (int)$existing['id'];

    // Stepper "-" support: a negative quantity that empties the line removes it.
    if ($quantity < 0 && $new_qty <= 0.0001) {
        Database::iud("DELETE FROM cart_items WHERE id='$cart_item_id'");
        echo "Item removed from cart.";
        exit();
    }

    if ($new_qty > $stock_qty) {
        echo "Sorry, only " . (float)$stock_qty . " unit(s) of \"$item_name\" are available in stock.";
        exit();
    }

    Database::iud(
        "UPDATE cart_items SET quantity='$new_qty', price='$unit_price', updated_at='$d'
         WHERE id='$cart_item_id'"
    );
    echo "Item quantity updated successfully!";
} else {
    if ($quantity <= 0) {
        echo "Nothing to add.";
        exit();
    }
    if ($quantity > $stock_qty) {
        echo "Sorry, only " . (float)$stock_qty . " unit(s) of \"$item_name\" are available in stock.";
        exit();
    }
    Database::iud(
        "INSERT INTO cart_items (cart_id, item_id, quantity, price, created_at)
         VALUES ('$cart_id', '$itemID', '$quantity', '$unit_price', '$d')"
    );
    echo "Item added successfully!";
}
