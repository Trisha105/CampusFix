<?php
/** CLI entry point for versioned migrations. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/migrations.php';

try {
    $applied = runMigrations(getDBConnection());
    echo $applied ? 'Applied: ' . implode(', ', $applied) . PHP_EOL : 'No pending migrations.' . PHP_EOL;
} catch (Throwable $error) {
    error_log('CampusFix migration failed: ' . $error->getMessage());
    exit(1);
}
