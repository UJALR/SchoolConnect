<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
protectPage();
$userId = getUserId();
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT COUNT(*) FROM DirectMessages WHERE recipient_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$count = (int)$stmt->fetchColumn();
header('Content-Type: application/json');
echo json_encode(['unread' => $count]);
