<?php
/** Authorize every media request and proxy Cloudinary bytes; no signed URL leaks. */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
if (!isLoggedIn()) {
    http_response_code(403);
    exit;
}
$imageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$imageId) {
    http_response_code(404);
    exit;
}
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT i.public_id, i.format, i.mime_type, c.user_id FROM complaint_images i JOIN complaints c ON c.id = i.complaint_id WHERE i.id = ?');
$stmt->execute([$imageId]);
$image = $stmt->fetch();
if (!$image || (!isAdmin() && (int)$image['user_id'] !== currentUserId())) {
    http_response_code(404);
    exit;
}
try {
    $body = (new CloudinaryMediaStore())->download($image['public_id'], $image['format']);
    header('Content-Type: ' . $image['mime_type']);
    header('Content-Length: ' . strlen($body));
    echo $body;
} catch (Throwable $error) {
    error_log('Complaint image delivery failed: ' . $error->getMessage());
    http_response_code(503);
}
