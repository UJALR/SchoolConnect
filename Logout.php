<?php
require_once 'includes/auth.php';

// Ensure the session is started before accessing $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: Login.php");
exit();
?>