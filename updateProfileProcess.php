<?php
/**
 * Customer Profile & Address Update Handler
 * Updates customer name, phone number, and delivery address fields in users table.
 */
require_once __DIR__ . '/connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized access. Please login.");
}

$user_id = (int)$_SESSION['user_id'];

$fName          = trim($_POST['first_name'] ?? ($_POST['firstName'] ?? ''));
$lName          = trim($_POST['last_name'] ?? ($_POST['lastName'] ?? ''));
$phone          = trim($_POST['phone_number'] ?? ($_POST['phone'] ?? ''));
$streetAddress1 = trim($_POST['street_address1'] ?? ($_POST['streetAddress1'] ?? ''));
$streetAddress2 = trim($_POST['street_address2'] ?? ($_POST['streetAddress2'] ?? ''));
$town           = trim($_POST['town'] ?? '');

if (empty($fName)) {
    die("Please enter your first name.");
}
if (empty($lName)) {
    die("Please enter your last name.");
}
if (empty($phone)) {
    die("Please enter your phone number.");
}
if (empty($streetAddress1)) {
    die("Please enter your street address.");
}
if (empty($town)) {
    die("Please enter your town/city.");
}

Database::setUpConnection();
$db = Database::$connection;

$fName_esc          = $db->real_escape_string($fName);
$lName_esc          = $db->real_escape_string($lName);
$phone_esc          = $db->real_escape_string($phone);
$streetAddress1_esc = $db->real_escape_string($streetAddress1);
$streetAddress2_esc = $db->real_escape_string($streetAddress2);
$town_esc           = $db->real_escape_string($town);

$update_query = "
    UPDATE users 
    SET first_name = '$fName_esc',
        last_name = '$lName_esc',
        phone_number = '$phone_esc',
        street_address1 = '$streetAddress1_esc',
        street_address2 = '$streetAddress2_esc',
        town = '$town_esc'
    WHERE id = '$user_id'
";

Database::iud($update_query);

// Keep session updated for immediate header avatar/name display
if (isset($_SESSION['users'])) {
    $_SESSION['users']['first_name'] = $fName;
    $_SESSION['users']['last_name']  = $lName;
    $_SESSION['users']['phone_number'] = $phone;
}

echo "success";

