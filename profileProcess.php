<?php
require_once 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access. Please login.";
    exit();
}

$user_id = $_SESSION['user_id'];

$fName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
$newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

if (empty($fName)) {
    echo "Please enter your first name.";
    exit();
}
if (empty($lName)) {
    echo "Please enter your last name.";
    exit();
}
if (empty($phone)) {
    echo "Please enter your phone number.";
    exit();
}
if (!preg_match('/^(?:0|\+94|94)?[1-9][0-9]{8}$/', $phone)) {
    echo "Please enter a valid Sri Lankan phone number.";
    exit();
}

// Fetch user data from database
$user_rs = Database::search("SELECT * FROM users WHERE id = '$user_id'");
if (!$user_rs || $user_rs->num_rows !== 1) {
    echo "User not found.";
    exit();
}

$user_data = $user_rs->fetch_assoc();
$db_password = $user_data['password'];

$update_password = false;
$hashed_new_password = '';

// Check if user requested password change
if (!empty($newPassword) || !empty($confirmPassword) || !empty($currentPassword)) {
    if (empty($currentPassword)) {
        echo "Please enter your current password to make security changes.";
        exit();
    }
    if (!password_verify($currentPassword, $db_password)) {
        echo "Incorrect current password.";
        exit();
    }
    if (empty($newPassword)) {
        echo "Please enter a new password.";
        exit();
    }
    if (strlen($newPassword) < 8) {
        echo "Password must be at least 8 characters.";
        exit();
    }
    if ($newPassword !== $confirmPassword) {
        echo "New password and confirm password do not match.";
        exit();
    }
    
    $hashed_new_password = password_hash($newPassword, PASSWORD_DEFAULT);
    $update_password = true;
}

// Perform update query
if ($update_password) {
    Database::iud("UPDATE users SET first_name = '$fName', last_name = '$lName', phone_number = '$phone', password = '$hashed_new_password' WHERE id = '$user_id'");
} else {
    Database::iud("UPDATE users SET first_name = '$fName', last_name = '$lName', phone_number = '$phone' WHERE id = '$user_id'");
}

// Refresh user session data
$user_rs = Database::search("SELECT * FROM users WHERE id = '$user_id'");
if ($user_rs && $user_row = $user_rs->fetch_assoc()) {
    $_SESSION['users'] = $user_row;
}

echo "success";
?>
