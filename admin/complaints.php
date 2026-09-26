<?php
/**
 * CampusFix - Admin Global Complaint List with Search & Filter
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Restrict access strictly to administrators
requireAdmin();

$pdo = getDBConnection();

// Retrieve and sanitize search & filter parameters
$q        = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$status   = trim($_GET['status'] ?? '');

// Build dynamic query with all search and filter options
// Admin sees ALL complaints; student name/email also searchable
$sql = "
    SELECT 
        c.id, 
        c.complaint_code, 
        c.title, 
        c.category, 
        c.location, 
        c.priority, 
        c.status, 
        c.created_at,
        c.updated_at,
        u.full_name AS student_name, 
        u.email AS student_email,
        u.student_id
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE 1=1
";
$params = [];

// 1. Keyword search across complaint fields AND student info
if ($q !== '') {
    $sql .= " AND (
        c.complaint_code LIKE ? OR 
        c.title LIKE ? OR 
        c.location LIKE ? OR 
        c.description LIKE ? OR 
        u.full_name LIKE ? OR 
        u.email LIKE ?
    )";
    $searchTerm = '%' . $q . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// 2. Category filter (whitelist validated)
if ($category !== '' && in_array($category, COMPLAINT_CATEGORIES, true)) {
    $sql .= " AND c.category = ?";
    $params[] = $category;
}

// 3. Priority filter (whitelist validated)
if ($priority !== '' && in_array($priority, COMPLAINT_PRIORITIES, true)) {
    $sql .= " AND c.priority = ?";
    $params[] = $priority;
}

// 4. Status filter (whitelist validated)
if ($status !== '' && in_array($status, COMPLAINT_STATUSES, true)) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$hasActiveFilters = ($q !== '' || $category !== '' || $priority !== '' || $status !== '');

$pageTitle = 'Manage Complaints - CampusFix Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning text-dark font-monospace text-uppercase">Admin Portal</span>
                <h2 class="fw-bold mb-0">Manage All Complaints</h2>
            </div>
            <p class="text-muted mb-0">Review, search, filter, and update the status of all campus facility complaints.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/dashboard.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= base_url('admin/complaints.php'); ?>">
                <div class="row g-3">
                    <!-- Keyword Search (includes student name/email) -->
                    <div class="col-12 col-lg-4">
                        <label for="q" class="form-label small text-muted mb-1">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="q" name="q" 
                                   value="<?= e($q); ?>" 
                                   placeholder="Code, title, location, student name/email...">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="category" class="form-label small text-muted mb-1">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach (COMPLAINT_CATEGORIES as $cat): ?>
                                <option value="<?= e($cat); ?>" <?= ($category === $cat) ? 'selected' : ''; ?>>
                                    <?= e($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div class="col-6 col-md-2 col-lg-2">
                        <label for="priority" class="form-label small text-muted mb-1">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All</option>
                            <?php foreach (COMPLAINT_PRIORITIES as $p): ?>
                                <option value="<?= e($p); ?>" <?= ($priority === $p) ? 'selected' : ''; ?>>
                                    <?= e($p); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-6 col-md-2 col-lg-2">
                        <label for="status" class="form-label small text-muted mb-1">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (COMPLAINT_STATUSES as $s): ?>
                                <option value="<?= e($s); ?>" <?= ($status === $s) ? 'selected' : ''; ?>>
                                    <?= e($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-6 col-md-1 col-lg-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100" title="Apply Filters">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                    </div>
                </div>

                <?php if ($hasActiveFilters): ?>
                    <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Filtered: <strong><?= count($complaints); ?></strong> complaint(s) found.
                        </div>
                        <a href="<?= base_url('admin/complaints.php'); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- All Complaints Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="card-title mb-0 fw-bold d-flex align-items-center">
                <i class="bi bi-card-checklist text-primary me-2"></i> All Campus Complaints
            </h5>
            <span class="badge bg-secondary rounded-pill"><?= count($complaints); ?> total</span>
        </div>

        <div class="card-body p-0">
            <?php if (empty($complaints)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon text-muted mb-3">
                        <i class="bi bi-search fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1">No complaints found.</h5>
                    <p class="text-muted small mb-3">
                        <?= $hasActiveFilters 
                            ? 'No complaints matched your active filter criteria.' 
                            : 'No facility complaints have been submitted yet.'; ?>
                    </p>
                    <?php if ($hasActiveFilters): ?>
                        <a href="<?= base_url('admin/complaints.php'); ?>" class="btn btn-outline-secondary btn-sm px-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                        </a>
                    <?php endif; ?>
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
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace fw-bold"><?= e($c['complaint_code']); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= e($c['student_name']); ?></div>
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
                                        <a href="<?= base_url('admin/complaint_view.php?id=' . (int)$c['id']); ?>" class="btn btn-sm btn-primary">
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
