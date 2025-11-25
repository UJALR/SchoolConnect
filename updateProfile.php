<?php
require_once "includes/auth.php";
require_once "includes/database.php";

protectPage();
$userId = getUserId();

$full_name = trim($_POST['full_name']);
$username  = trim($_POST['username']);
$bio       = trim($_POST['bio']);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    UPDATE Users
    SET full_name = ?, username = ?, bio = ?
    WHERE user_id = ?
");
$stmt->execute([$full_name, $username, $bio, $userId]);

header("Location: userProfile.php");
exit;
