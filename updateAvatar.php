<?php
require_once "includes/auth.php";
require_once "includes/database.php";
require_once "includes/functions.php";

protectPage();
$userId = getUserId();

// run avatar update
if (!empty($_FILES['avatar']['name'])) {
    updateUserAvatar($_FILES['avatar'], $userId);
}

// header("Location: userProfile.php");
header("Location: edit_profile.php");
exit;
