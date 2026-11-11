<?php
require_once("../connection.php");

$fn  = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$ln  = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$email  = isset($_POST['email']) ? trim($_POST['email']) : '';
$phoneNumber  = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';
$password  = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword  = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

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
} else if (strlen($password) < 8 ) {
     echo ("Password must be at least 8 characters and include uppercase, lowercase, and a number.");
     exit();
} else if (empty($confirmPassword)) {
    echo ("Please Confirm Your Password.");
    exit();
} 

Database::setUpConnection();
$checkStmt = Database::$connection->prepare("SELECT id FROM `users` WHERE `email` = ? LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$urs = $checkStmt->get_result();
$un = $urs ? $urs->num_rows : 0;
$checkStmt->close();

if($un > 0){
    echo "This email is already registered.";
    exit();
}else{
    if($password == $confirmPassword){
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insStmt = Database::$connection->prepare("INSERT INTO `users` (`first_name`,`last_name`,`email`,`phone_number`,`password`,`created_at`) VALUES (?, ?, ?, ?, ?, ?)");
        $insStmt->bind_param("ssssss", $fn, $ln, $email, $phoneNumber, $hashedPassword, $d);
        $insStmt->execute();
        $insStmt->close();
        echo "success";
    }else{
        echo "Password and confirm password do not match.";
        exit();
    }
}
?>
