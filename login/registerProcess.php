<?php
require("../connection.php");

$fn  = $_POST['firstName'];
$ln  = $_POST['lastName'];
$email  = $_POST['email'];
$phoneNumber  = $_POST['phoneNumber'];
$password  = $_POST['password'];
$confirmPassword  = $_POST['confirmPassword'];

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

$urs = Database::search("SELECT * FROM `users` WHERE `email`='" . $email . "'");
$un = $urs->num_rows;
if (empty($fn)) {
    echo ("Please Enter Your First Name.");
    exit();
} else if (empty($ln)) {
    echo ("Please Enter Your Last Name.");
    exit();
} else if (empty($email)) {
    echo ("Please Enter Your Email.");
    exit();
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
     echo ("Please enter a valid email address.");
     exit();
} else if (empty($phoneNumber)) {
    echo ("Please Enter Your Phone Number.");
    exit();
} else if (!preg_match('/^(?:0|\+94|94)?[1-9][0-9]{8}$/', $phoneNumber)) {
    echo ("Please enter a valid Sri Lankan phone number.");
    exit();
} else if (empty($password)) {
    echo ("Please Enter Your Password.");
    exit();
} else if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
     echo ("Password must be at least 8 characters and include uppercase, lowercase, and a number.");
     exit();
} else if (empty($confirmPassword)) {
    echo ("Please Confirm Your Password.");
    exit();
} 

if($un > 0){
    echo "This email is already registered.";
    exit();
}else{
    if($password == $confirmPassword){
        Database::iud("INSERT INTO `users` (`first_name`,`last_name`,`email`,`phone_number`,`password`,`created_at`) VALUES ('" . $fn . "','" . $ln . "','" . $email . "','" . $phoneNumber . "','" . $password . "','" . $d . "')");
        echo "success";
    }else{
        echo "Password and confirm password do not match.";
        exit();
    }
}
?>
