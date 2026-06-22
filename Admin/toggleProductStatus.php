<?php
require 'adminAuth.php';
require '../connection.php';

if (isset($_POST['item_id'])) {
    $itemId = (int)$_POST['item_id'];
    $rs = Database::search("SELECT status FROM items WHERE id='$itemId'");
    if ($rs && $rs->num_rows > 0) {
        $row = $rs->fetch_assoc();
        $new_status = ($row['status'] === 'active') ? 'inactive' : 'active';
        Database::iud("UPDATE items SET status='$new_status' WHERE id='$itemId'");
        echo $new_status;
    } else {
        echo "Product not found";
    }
} else {
    echo "Item ID missing";
}
?>
