<?php
require_once "includes/auth.php";
require_once "includes/database.php";

protectPage();
$userId = getUserId();

if (!empty($_FILES['cover_picture']['name'])) {

    $file = $_FILES['cover_picture'];
    $targetDir = "uploads/";
    $filename = "cover_" . $userId . "_" . time() . ".jpg";
    $targetFile = $targetDir . $filename;

    // Move uploaded image
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Save in database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET cover_picture = ? WHERE user_id = ?");
        $stmt->execute([$targetFile, $userId]);
    }
}

header("Location: userProfile.php");
exit;
?>
