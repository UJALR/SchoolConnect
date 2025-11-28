<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

protectPage();
$userId = getUserId();
$db = Database::getInstance();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $postText = trim($_POST['post_text'] ?? '');
    $mediaUrl = null;

    if ($groupId > 0 && !empty($postText)) {
        $checkMemberStmt = $pdo->prepare("SELECT 1 FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        $checkMemberStmt->execute([$groupId, $userId]);
        
        if ($checkMemberStmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO Posts (user_id, post_text, group_id, media_url)
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$userId, $postText, $groupId, $mediaUrl])) {
                header("Location: groups_detail.php?group_id=" . $groupId);
                exit;
            } else {
                $_SESSION['error'] = "Failed to create group post.";
            }
        } else {
            $_SESSION['error'] = "You are not a member of this group.";
        }
    } else {
        $_SESSION['error'] = "Post text is required.";
    }
}

// Fallback redirect
header("Location: my_groups.php");
exit;