<?php
/**
 * CampusFix - Administrator User Directory (Read-Only)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Restrict access strictly to administrators
requireAdmin();

$pdo = getDBConnection();

// Fetch all registered users ordered by registration date
$stmt = $pdo->query("
    SELECT id, full_name, student_id, email, role, created_at 
    FROM users 
    ORDER BY id ASC
");
$users = $stmt->fetchAll();

// Calculate user statistics
$totalUsers    = count($users);
$studentCount  = 0;
$adminCount    = 0;

foreach ($users as $u) {
    if ($u['role'] === 'admin') {
        $adminCount++;
    } else {
        $studentCount++;
    }
}

$pageTitle = 'Registered Users - CampusFix Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning text-dark font-monospace text-uppercase">Admin Portal</span>
                <h2 class="fw-bold mb-0">Registered Users Directory</h2>
            </div>
            <p class="text-muted mb-0">Read-only roster of all student and administrator accounts in the campus system.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/dashboard.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- User Counts Summary Pill Bar -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">Total Users</div>
                        <div class="stat-value text-navy"><?= $totalUsers; ?></div>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">Registered Students</div>
                        <div class="stat-value text-info"><?= $studentCount; ?></div>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">Administrators</div>
                        <div class="stat-value text-warning"><?= $adminCount; ?></div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Read-Only Users Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold d-flex align-items-center">
                <i class="bi bi-person-lines-fill text-primary me-2"></i> All Registered Accounts
            </h5>
            <span class="badge bg-light text-muted border">Read-Only</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Full Name</th>
                            <th>Student ID</th>
                            <th>Email Address</th>
                            <th>System Role</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="text-muted fw-bold">#<?= (int)$user['id']; ?></td>
                                <td class="fw-semibold text-dark">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?= ($user['role'] === 'admin') ? 'bi-shield-lock text-warning' : 'bi-person text-secondary'; ?> me-2 fs-5"></i>
                                        <?= e($user['full_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($user['student_id'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($user['student_id']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= e($user['email']); ?></code>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-warning text-dark text-uppercase">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark text-uppercase">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= format_date($user['created_at']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
