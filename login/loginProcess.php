<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../connection.php");

$email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
$password = isset($_POST["password"]) ? $_POST["password"] : "";

if (empty($email) || empty($password)) {
    echo "Email and Password required";
    exit();
}

Database::setUpConnection();
$stmt = Database::$connection->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$q = $stmt->get_result();

if ($q && $q->num_rows == 1) {

    $user = $q->fetch_assoc();

    if (password_verify($password, $user["password"])) {
        $_SESSION["users"] = $user;
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["is_admin"] = (int)$user["is_admin"];
        echo "success";
    } else {
        echo "Invalid email or password";
    }

} else {
    echo "Invalid email or password";
}
$stmt->close();
?>
