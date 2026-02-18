<?php
session_start();
require("../connection.php");

$email = $_POST["email"];
$password = $_POST["password"];

if (empty($email) || empty($password)) {
    echo "Email and Password required";
    exit();
}

$q = Database::search("SELECT * FROM users WHERE email = '".$email."'");

if ($q->num_rows == 1) {

    $user = $q->fetch_assoc();

    if (password_verify($password, $user["password"])) {
        $_SESSION["users"] = $user;
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_email"] = $user["email"];
        echo "success";
    } else {
        echo "Invalid email or password";
    }

} else {
    echo "Invalid email or password";
}

?>
