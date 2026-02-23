<?php  
require("../connection.php");


$itemName  = $_POST['itemName'];
$price  = $_POST['price'];
$unit  = $_POST['unit'];
$category  = $_POST['category'];
$status  = $_POST['status'];

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");


if (empty($itemName)) {
    echo ("Please Enter Item Name.");
    exit();
} else if (!isset($_FILES["pi"]) || $_FILES["pi"]["error"] != 0) {
    echo ("Please Select an Image.");
    exit();
} else if (empty($price)) {
    echo ("Please Enter Price.");
    exit();
} else if (empty($unit)) {
    echo ("Please Select Unit.");
    exit();
} else if (empty($category)) {
    echo ("Please Select Category.");
    exit();
} else if (empty($status)) { 
    echo ("Please Select Status.");
    exit();
}

if(isset($_FILES["pi"])){
    $img = $_FILES["pi"];
    $imgex = $img["type"];

    $allow_extensions = array("image/jpg", "image/jpeg", "image/png", "image/svg+xml");

    if(!in_array($imgex, $allow_extensions)){
        echo "Invalid image type.";
        exit();
    }
    if($img["size"] > (1024 * 1024 * 2)){
        echo "Image size exceeds 2MB limit.";
        exit();
    }

    $imgType = '';
    if($imgex == "image/jpg" ){
        $imgType = ".jpg";
    }   else if($imgex == "image/jpeg"){
        $imgType = ".jpeg";
    }   else if($imgex == "image/png"){
        $imgType = ".png";
    }   else if($imgex == "image/svg+xml"){
        $imgType = ".svg";
    }
    $uploadDir = __DIR__ . '/ImageUpload/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    // Sanitize item name for filename
    $safeItemName = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($itemName));
    $fileName = $safeItemName . '_' . uniqid() . $imgType;
    $pro_img_path = $uploadDir . $fileName;
    $db_img_path = 'Admin/ImageUpload/' . $fileName;
    if(move_uploaded_file($img["tmp_name"], $pro_img_path)) {
        $result = Database::iud("INSERT INTO `items` (`name`,`image_path`,`price`,`unit`,`category_id`,`status`,`created_at`) VALUES ('" . $itemName . "','" . $db_img_path . "','" . $price . "','" . $unit . "','" . $category . "','" . $status . "','" . $d . "')");
            echo "success";
    } else {
        echo "Image upload failed.";
    }
}

?>
