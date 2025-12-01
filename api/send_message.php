<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
protectPage();
$userId = getUserId();

$input = json_decode(file_get_contents('php://input'), true);
$recipient = isset($input['recipient_id']) ? (int)$input['recipient_id'] : 0;
$message = isset($input['message_text']) ? trim($input['message_text']) : '';

if (!$recipient || $message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'recipient_id and message_text required']);
    exit;
}

$db = Database::getInstance()->getConnection();
error_log("[send_message.php] incoming sender=$userId recipient=$recipient message=" . substr($message,0,200));
$stmt = $db->prepare("INSERT INTO DirectMessages (sender_id, recipient_id, message_text) VALUES (?, ?, ?)");
$stmt->execute([$userId, $recipient, $message]);
$insertId = $db->lastInsertId();
error_log("[send_message.php] inserted id=$insertId");

$stmt = $db->prepare("SELECT * FROM DirectMessages WHERE message_id = ?");
$stmt->execute([$insertId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// return saved message
header('Content-Type: application/json');
echo json_encode(['message' => $row]);
