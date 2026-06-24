<?php
session_start();
require("../connection.php");

if (!isset($_POST["password"])) {
    echo "Password is required";
    exit();
}

$password = $_POST["password"];

if (empty($password)) {
    echo "Password is required";
    exit();
}

if ($password === "8888") {
    // Fetch the admin user
    $q = Database::search("SELECT * FROM users WHERE email = 'achintha@gmail.com' AND is_admin = 1");
    
    if ($q && $q->num_rows == 0) {
        // Fallback to any admin
        $q = Database::search("SELECT * FROM users WHERE is_admin = 1 LIMIT 1");
    }
    
    if ($q && $q->num_rows > 0) {
        $user = $q->fetch_assoc();
        $_SESSION["admin_users"] = $user;
        $_SESSION["admin_user_id"] = $user["id"];
        $_SESSION["admin_user_email"] = $user["email"];
        $_SESSION["admin_is_admin"] = (int)$user["is_admin"];
        echo "success";
    } else {
        // Create fallback session if database has no admin users yet
        $_SESSION["admin_user_id"] = 999;
        $_SESSION["admin_user_email"] = 'admin@cec.com';
        $_SESSION["admin_is_admin"] = 1;
        echo "success";
    }
} else {
    echo "Invalid password";
}
?>
