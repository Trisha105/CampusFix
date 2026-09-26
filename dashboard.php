<?php
/**
 * CampusFix - Student Dashboard
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Restrict access to authenticated students only
requireStudent();

$userId = currentUserId();
$pdo = getDBConnection();

// 1. Fetch Student Complaint Statistics (strictly scoped to current student)
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_count,
        COALESCE(SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END), 0) AS in_progress_count,
        COALESCE(SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END), 0) AS resolved_count
    FROM complaints 
    WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch() ?: [
    'total_count' => 0,
    'pending_count' => 0,
    'in_progress_count' => 0,
    'resolved_count' => 0
];

// 2. Fetch Latest 5 Complaints for this Student
$recentStmt = $pdo->prepare("
    SELECT id, complaint_code, title, category, location, priority, status, created_at 
    FROM complaints 
    WHERE user_id = ? 
    ORDER BY id DESC 
    LIMIT 5
");
$recentStmt->execute([$userId]);
$recentComplaints = $recentStmt->fetchAll();

$pageTitle = 'Student Dashboard - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Header with Action Button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Student Dashboard</h2>
            <p class="text-muted mb-0">Overview of your submitted facility complaints and resolution progress.</p>
        </div>
        <div>
            <a href="<?= base_url('complaint_create.php'); ?>" class="btn btn-primary px-3 py-2 shadow-sm fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> + New Complaint
            </a>
        </div>
    </div>

    <!-- 4 Summary Metric Cards (Scoped to logged-in student) -->
    <div class="row g-3 mb-4">
        <!-- Total Complaints -->
        <div class="col-6 col-lg-3">
            <div class="card stat-card shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label mb-1">Total Filed</div>
                        <div class="stat-value text-navy"><?= (int)$stats['total_count']; ?></div>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-collection-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Complaints -->
        <div class="col-6 col-lg-3">
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

        <!-- In Progress Complaints -->
        <div class="col-6 col-lg-3">
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

        <!-- Resolved Complaints -->
        <div class="col-6 col-lg-3">
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
    </div>

    <!-- Recent Complaints Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold d-flex align-items-center">
                <i class="bi bi-clock me-2 text-primary"></i> Recent Complaints
            </h5>
            <?php if (!empty($recentComplaints)): ?>
                <a href="<?= base_url('complaints.php'); ?>" class="btn btn-sm btn-outline-secondary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body p-0">
            <?php if (empty($recentComplaints)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon text-muted mb-3">
                        <i class="bi bi-inbox fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1">No complaints found</h5>
                    <p class="text-muted small mb-3">You haven't submitted any campus complaints yet.</p>
                    <a href="<?= base_url('complaint_create.php'); ?>" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-plus-circle me-1"></i> Submit Your First Complaint
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Submitted Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComplaints as $c): ?>
                                <tr>
                                    <td class="fw-bold">
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($c['complaint_code']); ?></span>
                                    </td>
                                    <td class="fw-semibold text-truncate" style="max-width: 220px;">
                                        <?= e($c['title']); ?>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= e($c['category']); ?></span>
                                    </td>
                                    <td>
                                        <span class="small"><i class="bi bi-geo-alt text-muted me-1"></i><?= e($c['location']); ?></span>
                                    </td>
                                    <td><?= priority_badge($c['priority']); ?></td>
                                    <td><?= status_badge($c['status']); ?></td>
                                    <td class="small text-muted"><?= format_date($c['created_at']); ?></td>
                                    <td class="text-end">
                                        <a href="<?= base_url('complaint_view.php?id=' . (int)$c['id']); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i> View
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
