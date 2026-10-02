<?php
/** Remove one image; owner may remove Pending evidence, admin may remove any. */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}
$imageId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);
if (!$imageId) {
    http_response_code(400);
    exit('Invalid image.');
}
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT i.id, i.complaint_id, i.public_id, c.user_id, c.status FROM complaint_images i JOIN complaints c ON c.id = i.complaint_id WHERE i.id = ?');
$stmt->execute([$imageId]);
$image = $stmt->fetch();
if (!$image || (!isAdmin() && ((int)$image['user_id'] !== currentUserId() || $image['status'] !== 'Pending'))) {
    http_response_code(403);
    exit('Access denied.');
}
$return = isAdmin() ? 'admin/complaint_view.php?id=' : 'complaint_view.php?id=';
try {
    $store = new CloudinaryMediaStore();
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT i.id, c.user_id, c.status FROM complaint_images i JOIN complaints c ON c.id = i.complaint_id WHERE i.id = ? FOR UPDATE');
    $lock->execute([$imageId]);
    $current = $lock->fetch();
    if (!$current || (!isAdmin() && ((int)$current['user_id'] !== currentUserId() || $current['status'] !== 'Pending'))) {
        throw new RuntimeException('Image access changed before deletion.');
    }
    $delete = $pdo->prepare('DELETE FROM complaint_images WHERE id = ?');
    $delete->execute([$imageId]);
    $pdo->commit();
    deleteOrQueueMedia($store, $pdo, $image['public_id']);
    set_flash('success', 'Image removed.');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Complaint image delete failed: ' . $error->getMessage());
    set_flash('danger', 'Could not remove the image.');
}
redirect($return . (int)$image['complaint_id']);
