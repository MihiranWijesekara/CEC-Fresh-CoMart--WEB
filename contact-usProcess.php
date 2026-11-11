<?php
require_once __DIR__ . '/connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$contactName = trim($_POST['contactName'] ?? ($_POST['con_name'] ?? ($_POST['name'] ?? '')));
$email       = trim($_POST['email'] ?? ($_POST['con_email'] ?? ''));
$subject     = trim($_POST['subject'] ?? ($_POST['con_content'] ?? ''));
$message     = trim($_POST['message'] ?? ($_POST['con_message'] ?? ''));

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function handleContactResponse($isAjax, $status, $msg) {
    $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'contact-us.php';
    if ($isAjax) {
        if ($status === 'success') {
            echo "success";
        } else {
            echo $msg;
        }
        exit();
    } else {
        if ($status === 'success') {
            $_SESSION['contact_success'] = $msg;
        } else {
            $_SESSION['contact_error'] = $msg;
        }
        header("Location: " . $redirect);
        exit();
    }
}

if (empty($contactName)) {
    handleContactResponse($isAjax, 'error', "Please enter your contact name.");
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    handleContactResponse($isAjax, 'error', "Please enter a valid email address.");
}
if (empty($subject)) {
    handleContactResponse($isAjax, 'error', "Please enter your inquiry subject.");
}
if (empty($message)) {
    handleContactResponse($isAjax, 'error', "Please enter your message.");
}

Database::setUpConnection();
$db = Database::$connection;

$name_esc    = $db->real_escape_string($contactName);
$email_esc   = $db->real_escape_string($email);
$subject_esc = $db->real_escape_string($subject);
$message_esc = $db->real_escape_string($message);

Database::iud("
    INSERT INTO `contacts` (`name`, `email`, `subject`, `message`, `created_at`) 
    VALUES ('$name_esc', '$email_esc', '$subject_esc', '$message_esc', NOW())
");

handleContactResponse($isAjax, 'success', "Thank you! Your message has been sent successfully. Our team will get back to you shortly.");
