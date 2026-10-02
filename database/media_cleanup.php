<?php
/** Retry Cloudinary deletions that failed after their DB references were removed. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/media.php';

try {
    $pdo = getDBConnection();
    $store = new CloudinaryMediaStore();
    echo 'Processed ' . processMediaCleanup($pdo, $store) . ' cleanup job(s).' . PHP_EOL;
} catch (Throwable $error) {
    error_log('Media cleanup failed: ' . $error->getMessage());
    exit(1);
}
