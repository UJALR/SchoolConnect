<?php
require_once 'config.php';
require_once 'auth.php';

function getUserAlbums($userId, $conn) {
    $stmt = $conn->prepare("SELECT Album_Id, Title FROM Album WHERE Owner_id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

function getAlbumAccessibility($albumId, $conn) {
    $stmt = $conn->prepare("SELECT Accessibility_code FROM Album WHERE Album_Id = ?");
    $stmt->bind_param("i", $albumId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc()['Accessibility_code'] : null;
}

function canViewAlbum($userId, $albumId, $conn) {
    $ownerId = $conn->prepare("SELECT Owner_id FROM Album WHERE Album_Id = ?");
    $ownerId->bind_param("i", $albumId);
    $ownerId->execute();
    $ownerId = $ownerId->get_result()->fetch_assoc()['Owner_id'];
    
    if ($ownerId === $userId) return true;
    
    $accessibility = getAlbumAccessibility($albumId, $conn);
    if ($accessibility === 'shared') {
        return checkFriendship($userId, $ownerId, $conn) === 'accepted';
    }
    return false;
}
?>