<?php
/**
 * CampusFix - Administrator Global Dashboard
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Restrict access strictly to administrators
requireAdmin();

$pdo = getDBConnection();

// 1. Global Complaint Statistics (Across all campus students)
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_count,
        COALESCE(SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END), 0) AS in_progress_count,
        COALESCE(SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END), 0) AS resolved_count,
        COALESCE(SUM(CASE WHEN priority = 'High' THEN 1 ELSE 0 END), 0) AS high_priority_count
    FROM complaints
");
$stats = $statsStmt->fetch() ?: [
    'total_count'         => 0,
    'pending_count'       => 0,
    'in_progress_count'   => 0,
    'resolved_count'      => 0,
    'high_priority_count' => 0,
];

// 2. Latest 5 Complaints Across All Users (with submitter info)
$recentStmt = $pdo->query("
    SELECT 
        c.id, 
        c.complaint_code, 
        c.title, 
        c.category, 
        c.location, 
        c.priority, 
        c.status, 
        c.created_at,
        u.full_name AS student_name, 
        u.email AS student_email,
        u.student_id
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.id DESC 
    LIMIT 5
");
$recentComplaints = $recentStmt->fetchAll();

$pageTitle = 'Admin Dashboard - CampusFix';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Section with Quick Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning text-dark font-monospace text-uppercase">Admin Portal</span>
                <h2 class="fw-bold mb-0">Campus Overview</h2>
            </div>
            <p class="text-muted mb-0">System-wide monitoring of facility issues, workloads, and department resolutions.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('admin/complaints.php'); ?>" class="btn btn-primary shadow-sm fw-semibold">
                <i class="bi bi-card-checklist me-1"></i> Manage Complaints
            </a>
            <a href="<?= base_url('admin/users.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-people me-1"></i> User Directory
            </a>
        </div>
    </div>

    <!-- 5 Global Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Complaints -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">Total System</div>
                        <div class="stat-value text-navy"><?= (int)$stats['total_count']; ?></div>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-collection-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">Pending</div>
                        <div class="stat-value text-warning"><?= (int)$stats['pending_count']; ?></div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- In Progress -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">In Progress</div>
                        <div class="stat-value text-info"><?= (int)$stats['in_progress_count']; ?></div>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolved -->
        <div class="col-6 col-md-6 col-xl">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">Resolved</div>
                        <div class="stat-value text-success"><?= (int)$stats['resolved_count']; ?></div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- High Priority -->
        <div class="col-12 col-md-6 col-xl">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">High Priority</div>
                        <div class="stat-value text-danger"><?= (int)$stats['high_priority_count']; ?></div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent System Complaints Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="card-title mb-0 fw-bold d-flex align-items-center">
                <i class="bi bi-card-list text-primary me-2"></i> Latest 5 Campus Complaints
            </h5>
            <a href="<?= base_url('admin/complaints.php'); ?>" class="btn btn-sm btn-outline-primary">
                View All Complaints <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="card-body p-0">
            <?php if (empty($recentComplaints)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon text-muted mb-3">
                        <i class="bi bi-check2-all fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1">No complaints recorded</h5>
                    <p class="text-muted small mb-0">There are currently no facility complaints registered across campus.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Student</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComplaints as $c): ?>
                                <tr>
                                    <td class="fw-bold">
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($c['complaint_code']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($c['student_name']); ?></div>
                                        <div class="small text-muted"><?= e($c['student_email']); ?></div>
                                    </td>
                                    <td class="fw-semibold text-truncate" style="max-width: 200px;">
                                        <?= e($c['title']); ?>
                                    </td>
                                    <td><span class="small text-muted"><?= e($c['category']); ?></span></td>
                                    <td><span class="small"><i class="bi bi-geo-alt text-muted me-1"></i><?= e($c['location']); ?></span></td>
                                    <td><?= priority_badge($c['priority']); ?></td>
                                    <td><?= status_badge($c['status']); ?></td>
                                    <td class="small text-muted"><?= format_date($c['created_at']); ?></td>
                                    <td class="text-end">
                                        <a href="<?= base_url('admin/complaint_view.php?id=' . (int)$c['id']); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-sliders me-1"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
