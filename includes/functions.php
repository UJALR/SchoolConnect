<?php
require_once 'database.php';

// -------------------- USERS --------------------
function getUserName($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT full_name FROM Users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: "Unknown User";
}

function getUserProfilePic($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT profile_picture FROM Users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: "assets/images/default-avatar.png";
}

// -------------------- POSTS --------------------
function createPost($userId, $text, $mediaUrl = null) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO Posts (user_id, post_text, media_url)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, $text, $mediaUrl]);
}

function getAllPosts() {
    $db = Database::getInstance()->getConnection();
    $query = "
        SELECT post_id, user_id, post_text, media_url, created_at
        FROM Posts
        ORDER BY created_at DESC
    ";
    return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

// -------------------- FRIENDS SUGGESTIONS --------------------
function getSuggestedFriends($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT user_id, full_name, profile_picture
        FROM Users
        WHERE user_id != ?
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
