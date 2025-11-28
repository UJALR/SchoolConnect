<?php
require_once 'database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function protectPage($redirect = 'Login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect");
        exit();
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

function checkFriendship($userId1, $userId2) {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $sql = "SELECT status FROM UserFriends 
            WHERE (user_id_sender = :id1 AND user_id_receiver = :id2)
            OR (user_id_sender = :id2_alt AND user_id_receiver = :id1_alt)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id1' => $userId1, 
        ':id2' => $userId2, 
        ':id2_alt' => $userId2,
        ':id1_alt' => $userId1
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['status'] : false;
}