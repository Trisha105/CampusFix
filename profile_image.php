<?php
/** Same-session access to a private profile image. */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!isLoggedIn() || !$userId || (!isAdmin() && $userId !== currentUserId())) {
    http_response_code(403);
    exit;
}
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT public_id, format, mime_type FROM profile_images WHERE user_id = ?');
$stmt->execute([$userId]);
$image = $stmt->fetch();
if (!$image) {
    http_response_code(404);
    exit;
}
try {
    $body = (new CloudinaryMediaStore())->download($image['public_id'], $image['format']);
    header('Content-Type: ' . $image['mime_type']);
    header('Content-Length: ' . strlen($body));
    echo $body;
} catch (Throwable $error) {
    error_log('Profile image delivery failed: ' . $error->getMessage());
    http_response_code(503);
}
