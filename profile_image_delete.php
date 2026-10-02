<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
requireStudent();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}
$pdo = getDBConnection();
try {
    $store = new CloudinaryMediaStore();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT public_id FROM profile_images WHERE user_id = ? FOR UPDATE');
    $stmt->execute([currentUserId()]);
    $oldId = $stmt->fetchColumn();
    if ($oldId !== false) {
        $pdo->prepare('DELETE FROM profile_images WHERE user_id = ?')->execute([currentUserId()]);
    }
    $pdo->commit();
    if ($oldId !== false) deleteOrQueueMedia($store, $pdo, (string)$oldId);
    set_flash('success', 'Profile picture removed.');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Profile image delete failed: ' . $error->getMessage());
    set_flash('danger', 'Could not remove the profile picture.');
}
redirect('profile.php');
