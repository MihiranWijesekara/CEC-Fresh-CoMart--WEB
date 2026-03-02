<?php
require("../connection.php");

if (isset($_POST["cid"])) {

    $categoryId = intval($_POST["cid"]);  // FORCE it to number

    
    if ($categoryId === 0) {
        echo "success";
        exit();
    }

    $rs = Database::search("SELECT * FROM `items` WHERE `category_id` = $categoryId LIMIT 1");

    if ($rs === false) {
        echo "Database query error.";
        exit();
    }

    if ($rs->num_rows > 0) {
        echo "success";
    } else {
        echo "The selected category does not exist.";
    }

} else {
    echo "Process failed. Category ID not found.";
}
?>