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

// update Avatar for the userProfile.php

function updateUserAvatar($file, $userId) {
    if (empty($file['name'])) {
        return false;
    }

    $targetDir = __DIR__ . "/../uploads/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $filename = "avatar_" . $userId . "_" . time() . ".jpg";
    $targetFile = $targetDir . $filename;

    if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
        return false;
    }

    $dbPath = "uploads/" . $filename;

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
    $stmt->execute([$dbPath, $userId]);

    return true;
}


//this is for the viewFriends.php
function sendFriendRequest($senderId, $receiverId) {

    $db = Database::getInstance()->getConnection();

    $sql = "
        SELECT connection_id 
        FROM userfriends
        WHERE (user_id_sender = :s1 AND user_id_receiver = :r1)
           OR (user_id_sender = :s2 AND user_id_receiver = :r2)
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':s1' => $senderId,
        ':r1' => $receiverId,
        ':s2' => $receiverId,
        ':r2' => $senderId
    ]);

    if ($stmt->rowCount() == 0) {
        $insert = $db->prepare("
            INSERT INTO userfriends (user_id_sender, user_id_receiver, status)
            VALUES (:sender, :receiver, 'pending')
        ");

        $insert->execute([
            ':sender' => $senderId,
            ':receiver' => $receiverId
        ]);
    }
}
function getFriendshipStatus($userId, $otherUserId) {

    $db = Database::getInstance()->getConnection();

    $sql = "
        SELECT status, user_id_sender 
        FROM userfriends
        WHERE (user_id_sender = :a AND user_id_receiver = :b)
           OR (user_id_sender = :c AND user_id_receiver = :d)
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':a' => $userId,
        ':b' => $otherUserId,
        ':c' => $otherUserId,
        ':d' => $userId
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function cancelFriendRequest($senderId, $receiverId) {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        DELETE FROM userfriends
        WHERE user_id_sender = :sender
        AND user_id_receiver = :receiver
        AND status = 'pending'
    ");

    $stmt->execute([
        ':sender' => $senderId,
        ':receiver' => $receiverId
    ]);
}
function unfriendUser($userId, $otherId) {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        DELETE FROM userfriends
        WHERE 
            (user_id_sender = ? AND user_id_receiver = ?)
         OR (user_id_sender = ? AND user_id_receiver = ?)
        AND status = 'accepted'
    ");

    $stmt->execute([
        $userId,
        $otherId,
        $otherId,
        $userId
    ]);
}



// Get all friends + requests for one user (used on userProfile.php)
function getUserFriendsAndRequests($userId) {

    $db = Database::getInstance()->getConnection();

    $sql = "
        SELECT 
            u.user_id,
            u.full_name,
            u.profile_picture,
            uf.status,
            uf.user_id_sender
        FROM userfriends uf
        JOIN users u 
            ON u.user_id = 
            CASE 
                WHEN uf.user_id_sender = :uid1 THEN uf.user_id_receiver 
                ELSE uf.user_id_sender 
            END
        WHERE (uf.user_id_sender = :uid2 OR uf.user_id_receiver = :uid3)
        ORDER BY uf.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':uid1' => $userId,
        ':uid2' => $userId,
        ':uid3' => $userId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Accept an incoming friend request
function acceptFriendRequest($senderId, $receiverId) {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        UPDATE userfriends
        SET status = 'accepted'
        WHERE user_id_sender = :sender
          AND user_id_receiver = :receiver
          AND status = 'pending'
    ");

    $stmt->execute([
        ':sender'   => $senderId,
        ':receiver' => $receiverId
    ]);
}
