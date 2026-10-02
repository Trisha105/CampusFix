<?php
/** Add authorized before/after evidence to a database-backed complaint. */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}
$complaintId = filter_input(INPUT_POST, 'complaint_id', FILTER_VALIDATE_INT);
if (!$complaintId) {
    http_response_code(400);
    exit('Invalid complaint.');
}
$return = isAdmin() ? 'admin/complaint_view.php?id=' : 'complaint_view.php?id=';
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT id, user_id, status FROM complaints WHERE id = ?');
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();
if (!$complaint || (!isAdmin() && ((int)$complaint['user_id'] !== currentUserId() || $complaint['status'] !== 'Pending'))) {
    http_response_code(403);
    exit('Access denied.');
}
$label = isAdmin() ? 'after' : 'before';
$uploads = submittedImages($_FILES['images'] ?? []);
if (!$uploads || count($uploads) > 3) {
    set_flash('danger', 'Select one to three images.');
    redirect($return . $complaintId);
}
try {
    foreach ($uploads as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Image upload failed. Check the 5 MB per-image limit.');
        }
        validateImageContent($file['tmp_name'], $file['size']);
    }
    $store = new CloudinaryMediaStore();
    $uploaded = [];
    try {
        foreach ($uploads as $file) {
            $metadata = validateImageContent($file['tmp_name'], $file['size']);
            $asset = $store->upload($file['tmp_name'], 'campusfix/complaints/' . $complaintId);
            $uploaded[] = $asset + $metadata;
        }
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id, user_id, status FROM complaints WHERE id = ? FOR UPDATE');
        $lock->execute([$complaintId]);
        $current = $lock->fetch();
        if (!$current || (!isAdmin() && ((int)$current['user_id'] !== currentUserId() || $current['status'] !== 'Pending'))) {
            throw new RuntimeException('Complaint access changed during upload.');
        }
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM complaint_images WHERE complaint_id = ?');
        $countStmt->execute([$complaintId]);
        if ((int)$countStmt->fetchColumn() + count($uploaded) > 3) {
            throw new InvalidArgumentException('A complaint can contain at most three images.');
        }
        $insert = $pdo->prepare('INSERT INTO complaint_images (complaint_id, uploaded_by, label, public_id, format, mime_type, byte_size) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($uploaded as $asset) {
            $insert->execute([$complaintId, currentUserId(), $label, $asset['public_id'], $asset['format'], $asset['mime'], $asset['bytes']]);
        }
        $pdo->commit();
        set_flash('success', count($uploaded) . ' image(s) added.');
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($uploaded as $asset) {
            deleteOrQueueMedia($store, $pdo, $asset['public_id']);
        }
        throw $error;
    }
} catch (Throwable $error) {
    error_log('Complaint image upload failed: ' . $error->getMessage());
    set_flash('danger', $error instanceof InvalidArgumentException ? $error->getMessage() : 'Could not save the images. Please try again.');
}
redirect($return . $complaintId);
