<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_is_admin']) || (int)$_SESSION['admin_is_admin'] !== 1) {
    header("Location: login.php");
    exit();
}
?>
