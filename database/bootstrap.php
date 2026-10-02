<?php
/** Initialize an empty production database and its first administrator. CLI only. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/migrations.php';

try {
    $pdo = getDBConnection();
    runMigrations($pdo);

    $email = trim(getenv('ADMIN_EMAIL') ?: '');
    $password = getenv('ADMIN_PASSWORD') ?: '';
    if ($email !== '' || $password !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            throw new RuntimeException('ADMIN_EMAIL must be valid and ADMIN_PASSWORD must have at least 12 characters.');
        }
        $stmt = $pdo->prepare('SELECT role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        if ($existing && $existing['role'] !== 'admin') {
            throw new RuntimeException('ADMIN_EMAIL belongs to a student account.');
        }
        if (!$existing) {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute(['CampusFix Administrator', $email, password_hash($password, PASSWORD_DEFAULT)]);
            error_log('CampusFix administrator created.');
        }
    }
} catch (Throwable $error) {
    error_log('CampusFix bootstrap failed: ' . $error->getMessage());
    exit(1);
}
