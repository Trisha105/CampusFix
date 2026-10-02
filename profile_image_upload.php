<?php
/** Replace the signed-in student's optional profile picture. */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
requireStudent();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}
$file = $_FILES['profile_image'] ?? null;
$pdo = getDBConnection();
try {
    if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
        !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        throw new InvalidArgumentException('Select a valid image of at most 5 MB.');
    }
    $metadata = validateImageContent($file['tmp_name'], (int)$file['size']);
    $store = new CloudinaryMediaStore();
    $asset = $store->upload($file['tmp_name'], 'campusfix/profiles');
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT public_id FROM profile_images WHERE user_id = ? FOR UPDATE');
        $stmt->execute([currentUserId()]);
        $oldId = $stmt->fetchColumn();
        $save = $pdo->prepare('INSERT INTO profile_images (user_id, public_id, format, mime_type, byte_size) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE public_id = VALUES(public_id), format = VALUES(format), mime_type = VALUES(mime_type), byte_size = VALUES(byte_size)');
        $save->execute([currentUserId(), $asset['public_id'], $asset['format'], $metadata['mime'], $metadata['bytes']]);
        $pdo->commit();
        if ($oldId !== false) {
            deleteOrQueueMedia($store, $pdo, (string)$oldId);
        }
        set_flash('success', 'Profile picture updated.');
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        deleteOrQueueMedia($store, $pdo, $asset['public_id']);
        throw $error;
    }
} catch (Throwable $error) {
    error_log('Profile image upload failed: ' . $error->getMessage());
    set_flash('danger', $error instanceof InvalidArgumentException ? $error->getMessage() : 'Could not save the profile picture.');
}
redirect('profile.php');
