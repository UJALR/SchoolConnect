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
    $postsStmt->execute($params);

    return $postsStmt->fetchAll(PDO::FETCH_ASSOC);
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

// -------------------- GROUPS SUGGESTIONS --------------------
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