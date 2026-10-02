<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
$pdo = getDBConnection();
if (($_POST['all'] ?? '') === '1') {
    $stmt = $pdo->prepare('UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE recipient_id = ? AND read_at IS NULL');
    $stmt->execute([(int)currentUserId()]);
} else {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { http_response_code(400); exit; }
    $stmt = $pdo->prepare('UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = ? AND recipient_id = ? AND read_at IS NULL');
    $stmt->execute([$id, (int)currentUserId()]);
}
redirect('notifications.php');
