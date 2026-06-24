<?php
require("../connection.php");

if (isset($_POST["cid"])) {

    $categoryId = intval($_POST["cid"]);  // FORCE it to number

    if ($categoryId >= 0 && $categoryId <= 19) {
        echo "success";
    } else {
        echo "The selected category does not exist.";
    }

} else {
    echo "Process failed. Category ID not found.";
}
?>