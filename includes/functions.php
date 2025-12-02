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

function updateUserAvatar($file, $userId) {
    if (empty($file['name'])) {
        return false;
    }

    // Set target directory relative to the project root
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

// -------------------- POSTS --------------------
function createPost($userId, $text, $mediaUrl = null) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO Posts (user_id, post_text, media_url)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, $text, $mediaUrl]);
}

function getAllPosts($userId) {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // 1. Get a list of group_ids the user is a member of
    $groupsStmt = $pdo->prepare("SELECT group_id FROM GroupMembers WHERE user_id = ?");
    $groupsStmt->execute([$userId]);
    $groupIds = $groupsStmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Initialize SQL and parameters for the main query
    $sql = "
        SELECT 
            p.*, 
            u.username, 
            u.full_name, 
            u.profile_picture,
            CASE WHEN p.group_id IS NOT NULL THEN 'Group Post' ELSE 'General Post' END as post_type,
            ug.group_name
        FROM Posts p
        JOIN Users u ON p.user_id = u.user_id
        LEFT JOIN UserGroups ug ON p.group_id = ug.group_id
        WHERE p.group_id IS NULL "; // Start with general posts only
    
    $params = [];
    
    // 3. Conditionally add the GROUP posts logic if the user is in groups
    if (!empty($groupIds)) {
        // Create placeholder string: (?), (?), ... (Number of groups - 1 repeats)
        $inPlaceholders = '?' . str_repeat(',?', count($groupIds) - 1);
        
        $sql .= "
        OR p.group_id IN ({$inPlaceholders})
        ";
        
        // Add the group IDs to the parameters
        $params = $groupIds;
    }

    // 4. Finalize the query
    $sql .= "
        ORDER BY p.created_at DESC
        LIMIT 50
    ";
    
    // 5. Execute the statement
    $postsStmt = $pdo->prepare($sql);
    // Note: If $groupIds is empty, $params is empty, executing only the WHERE p.group_id IS NULL part.
    $postsStmt->execute($params);

    return $postsStmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPostsByUserId($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT post_id, user_id, post_text, media_url, created_at
        FROM Posts
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// -------------------- COMMENTS --------------------

/**
 * @param int $postId The ID of the post being commented on.
 * @param int $userId The ID of the user submitting the comment.
 * @param string $commentText The content of the comment.
 * @return bool True on success, false on failure.
 */
function createComment($postId, $userId, $commentText) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO UserComments (post_id, user_id, comment_text)
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$postId, $userId, $commentText]);
}

/**
 * Retrieves all comments for a specific post, ordered by creation time.
 * Includes user information for display.
 * @param int $postId The ID of the post.
 * @return array Array of comments.
 */
function getCommentsForPost($postId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT 
            c.comment_id, 
            c.user_id, 
            c.comment_text, 
            c.created_at, 
            u.full_name, 
            u.profile_picture
        FROM UserComments c
        JOIN Users u ON c.user_id = u.user_id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// -------------------- FRIENDS --------------------

function getSuggestedFriends($userId) {
    $db = Database::getInstance()->getConnection();
    // This query is basic and does not exclude existing friends/pending requests.
    $stmt = $db->prepare("
        SELECT user_id, full_name, profile_picture
        FROM Users
        WHERE user_id != ?
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sendFriendRequest($senderId, $receiverId) {
    $db = Database::getInstance()->getConnection();

    // Check if a relationship already exists (pending or accepted)
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

    // Only allow deletion of requests sent by $senderId to $receiverId
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

    // Delete the accepted friendship regardless of sender/receiver column order
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

// Get all friends + requests for one user (used on viewMyFriends.php)
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

    $sql = "
        UPDATE userfriends
        SET status = 'accepted'
        WHERE status = 'pending'
          AND (
            (user_id_sender = ? AND user_id_receiver = ?)
            OR
            (user_id_sender = ? AND user_id_receiver = ?)
          )
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $senderId,
        $receiverId,
        $receiverId,
        $senderId
    ]);
}

// Get count of pending friend requests (for badge/notification)
function getPendingFriendRequestCount($userId) {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM userfriends 
        WHERE user_id_receiver = ? 
        AND status = 'pending'
    ");
    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}


// -------------------- GROUPS --------------------
function getSuggestedGroups($limit = 3) {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            group_id, 
            group_name
        FROM UserGroups 
        ORDER BY RAND() 
        LIMIT ?
    ");
    
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// -------------------- INTERESTS & LANGUAGES --------------------
function getInterestsForUser($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT i.interest_name
        FROM UserInterests ui
        JOIN Interests i ON ui.interest_id = i.interest_id
        WHERE ui.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getLanguagesForUser($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT l.language_name
        FROM UserLanguages ul
        JOIN Languages l ON ul.language_id = l.language_id
        WHERE ul.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getAllInterests() {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT interest_name FROM Interests ORDER BY interest_name ASC";
    return $db->query($query)->fetchAll(PDO::FETCH_COLUMN);
}

function getAllLanguages() {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT language_name FROM Languages ORDER BY language_name ASC";
    return $db->query($query)->fetchAll(PDO::FETCH_COLUMN);
}

function updateInterestsAndLanguages($userId, $interests, $languages) {
    $db = Database::getInstance()->getConnection();

    // 1. Clear existing interests and languages for the user
    $db->prepare("DELETE FROM UserInterests WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM UserLanguages WHERE user_id = ?")->execute([$userId]);

    // 2. Process Interests
    if (!empty($interests)) {
        foreach ($interests as $name) {
            $name = trim($name);
            if (empty($name)) continue;

            // Find or create Interest
            $stmt = $db->prepare("SELECT interest_id FROM Interests WHERE interest_name = ?");
            $stmt->execute([$name]);
            $interestId = $stmt->fetchColumn();

            if (!$interestId) {
                $db->prepare("INSERT INTO Interests (interest_name) VALUES (?)")->execute([$name]);
                $interestId = $db->lastInsertId();
            }

            // Create UserInterest relation
            $db->prepare("INSERT INTO UserInterests (user_id, interest_id) VALUES (?, ?)")
               ->execute([$userId, $interestId]);
        }
    }

    // 3. Process Languages
    if (!empty($languages)) {
        foreach ($languages as $name) {
            $name = trim($name);
            if (empty($name)) continue;
            
            // Find or create Language
            $stmt = $db->prepare("SELECT language_id FROM Languages WHERE language_name = ?");
            $stmt->execute([$name]);
            $languageId = $stmt->fetchColumn();

            if (!$languageId) {
                $db->prepare("INSERT INTO Languages (language_name) VALUES (?)")->execute([$name]);
                $languageId = $db->lastInsertId();
            }

            // Create UserLanguage relation
            $db->prepare("INSERT INTO UserLanguages (user_id, language_id) VALUES (?, ?)")
               ->execute([$userId, $languageId]);
        }
    }

    return true;
}

/**
 * Permanently delete a group and all related data: group posts, post comments, group members, and the group record.
 * Performs the deletes inside a transaction.
 * @param int $groupId
 * @return bool True on success, false on failure
 */
function deleteGroup($groupId) {
    $db = Database::getInstance()->getConnection();

    try {
        $db->beginTransaction();

        // 1) Delete comments for posts that belong to this group
        $delComments = $db->prepare("DELETE c FROM UserComments c
            JOIN Posts p ON c.post_id = p.post_id
            WHERE p.group_id = ?");
        $delComments->execute([$groupId]);

        // 2) Delete posts for this group
        $delPosts = $db->prepare("DELETE FROM Posts WHERE group_id = ?");
        $delPosts->execute([$groupId]);

        // 3) Delete group members
        $delMembers = $db->prepare("DELETE FROM GroupMembers WHERE group_id = ?");
        $delMembers->execute([$groupId]);

        // 4) Delete the group record
        $delGroup = $db->prepare("DELETE FROM UserGroups WHERE group_id = ?");
        $delGroup->execute([$groupId]);

        $db->commit();
        return true;
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        return false;
    }
}

// Get unread direct message count for a user
function getUnreadMessageCount($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM DirectMessages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// Get user's friends (accepted connections)
function getUserFriends($userId) {
    $db = Database::getInstance()->getConnection();
        $sql = "
                SELECT u.user_id, u.full_name, u.username, u.profile_picture
                FROM userfriends uf
                JOIN users u ON (u.user_id = uf.user_id_sender OR u.user_id = uf.user_id_receiver)
                WHERE uf.status = 'accepted'
                    AND (uf.user_id_sender = ? OR uf.user_id_receiver = ?)
                    AND u.user_id != ?
                ORDER BY u.full_name ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}