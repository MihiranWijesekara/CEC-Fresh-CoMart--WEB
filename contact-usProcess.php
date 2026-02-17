<?php
require("connection.php");

$contactName  = $_POST['contactName'];
$email  = $_POST['email'];
$subject  = $_POST['subject'];
$message  = $_POST['message'];

$date = new DateTime();
$tz = new DateTimeZone("Asia/Colombo");
$date->setTimezone($tz);
$d = $date->format("Y-m-d H:i:s");

if (empty($contactName)) {
    echo ("Please Enter Your Contact Name.");
    exit();
} else if (empty($email)) {
    echo ("Please Enter Your Email.");
    exit();
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo ("Please enter a valid email address.");
    exit();
} else if (empty($subject)) {
    echo ("Please Enter Your Subject.");
    exit();
} else if (empty($message)) { 
    echo ("Please Enter Your Message.");
    exit();
}

 Database::iud("INSERT INTO `contacts` (`name`,`email`,`subject`,`message`,`created_at`) VALUES ('" . $contactName . "','" . $email . "','" . $subject . "','" . $message . "','" . $d . "')");
    echo "success";

?>