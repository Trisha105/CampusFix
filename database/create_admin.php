<?php
/**
 * CampusFix - Secure Administrator Account Creator
 *
 * This script initializes or resets the primary Administrator account
 * using password_hash(). It can be run from the command line or web browser.
 *
 * SECURITY NOTICE:
 * Disable or delete this script immediately after creating the administrator
 * in production environments to prevent unauthorized account manipulation.
 */

// Allow execution from CLI or web
$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../config/database.php';

$adminName     = 'System Administrator';
$adminEmail    = 'admin@campusfix.edu';
$adminPassword = 'admin123';
$adminRole     = 'admin';

$pdo = getDBConnection();

$messages = [];
$success  = false;

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingAdmin = $stmt->fetch();

    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);

    if ($existingAdmin) {
        // Update password and role to ensure admin privileges are active
        $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ?, role = ? WHERE id = ?");
        $updateStmt->execute([$adminName, $passwordHash, $adminRole, $existingAdmin['id']]);
        $messages[] = "Existing administrator account found ({$adminEmail}). Password has been updated.";
        $success = true;
    } else {
        // Insert new admin user
        $insertStmt = $pdo->prepare("INSERT INTO users (full_name, student_id, email, password, role) VALUES (?, NULL, ?, ?, ?)");
        $insertStmt->execute([$adminName, $adminEmail, $passwordHash, $adminRole]);
        $messages[] = "Administrator account created successfully!";
        $success = true;
    }
} catch (Exception $e) {
    $messages[] = "Error creating administrator: " . $e->getMessage();
    $success = false;
}

if ($isCli) {
    echo "===============================================\n";
    echo "CampusFix - Administrator Account Setup\n";
    echo "===============================================\n";
    foreach ($messages as $msg) {
        echo $msg . "\n";
    }
    if ($success) {
        echo "\nCredentials:\n";
        echo "Email:    " . $adminEmail . "\n";
        echo "Password: " . $adminPassword . "\n";
        echo "Role:     " . $adminRole . "\n";
        echo "\nIMPORTANT: Remove or restrict database/create_admin.php after initial setup.\n";
    }
    echo "===============================================\n";
} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Admin - CampusFix</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="card-title mb-0">CampusFix Admin Initializer</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <h6 class="alert-heading fw-bold mb-1">Success</h6>
                                    <?php foreach ($messages as $msg): ?>
                                        <p class="mb-0"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endforeach; ?>
                                </div>
                                <table class="table table-bordered mb-3">
                                    <tr>
                                        <th class="bg-light" style="width: 35%;">Email</th>
                                        <td><code><?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Password</th>
                                        <td><code><?= htmlspecialchars($adminPassword, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Role</th>
                                        <td><span class="badge bg-success">admin</span></td>
                                    </tr>
                                </table>
                                <div class="alert alert-warning mb-0">
                                    <strong>Security Warning:</strong> Delete or disable <code>database/create_admin.php</code> after use.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading fw-bold mb-1">Setup Error</h6>
                                    <?php foreach ($messages as $msg): ?>
                                        <p class="mb-0"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
