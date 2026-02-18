<?php
session_start();
require("../connection.php");

$email = $_POST["email"];
$password = $_POST["password"];

$q = Database::search("SELECT * FROM `users` WHERE email = '" . $email . "' AND password = '" . $password . "'");
$un = $q->num_rows;

if (empty($email)) {
    echo ("Please Enter Your Email Address.");
    exit();
} else if (empty($password)) {
    echo ("Please Enter Your Password.");   
    exit();
}

if ($un == "1") {
    $wdata = $q->fetch_assoc();
    $_SESSION["users"] = $wdata;
    echo "success";
} else {
    echo "Invalid email or password";
}

?>
