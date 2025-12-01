<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
protectPage();
$userId = getUserId();

$other = isset($_GET['other_id']) ? (int)$_GET['other_id'] : 0;
if (!$other) {
    http_response_code(400);
    echo json_encode(['error' => 'other_id required']);
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM DirectMessages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY sent_at ASC LIMIT 500");
$stmt->execute([$userId, $other, $other, $userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// mark messages as read where recipient is current user
$mark = $db->prepare("UPDATE DirectMessages SET is_read = 1 WHERE recipient_id = ? AND sender_id = ? AND is_read = 0");
$mark->execute([$userId, $other]);

error_log("[get_messages.php] user=$userId other=$other fetched=" . count($messages));

header('Content-Type: application/json');
echo json_encode(['messages' => $messages]);
