<?php
require_once 'config.php';

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

function checkFriendship($userId1, $userId2, $conn) {
    $stmt = $conn->prepare("SELECT Status FROM Friendship 
                          WHERE (Friend_RequesterId = ? AND Friend_RequesteeId = ?)
                          OR (Friend_RequesterId = ? AND Friend_RequesteeId = ?)");
    $stmt->bind_param("ssss", $userId1, $userId2, $userId2, $userId1);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc()['Status'] : false;
}
?>