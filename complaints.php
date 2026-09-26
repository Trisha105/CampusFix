<?php
/**
 * CampusFix - Student Complaint List & Combined Search/Filter
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Restrict access to authenticated students only
requireStudent();

$userId = currentUserId();
$pdo = getDBConnection();

// Retrieve and sanitize search & filter parameters
$q        = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$status   = trim($_GET['status'] ?? '');

// Base query strictly scoped to logged-in user (Ownership Isolation)
$sql = "
    SELECT id, complaint_code, title, category, location, priority, status, created_at, updated_at 
    FROM complaints 
    WHERE user_id = ?
";
$params = [$userId];

// 1. Keyword search (complaint code, title, location, description)
if ($q !== '') {
    $sql .= " AND (complaint_code LIKE ? OR title LIKE ? OR location LIKE ? OR description LIKE ?)";
    $searchTerm = '%' . $q . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// 2. Category filter
if ($category !== '' && in_array($category, COMPLAINT_CATEGORIES, true)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

// 3. Priority filter
if ($priority !== '' && in_array($priority, COMPLAINT_PRIORITIES, true)) {
    $sql .= " AND priority = ?";
    $params[] = $priority;
}

// 4. Status filter
if ($status !== '' && in_array($status, COMPLAINT_STATUSES, true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Determine if any filters are active
$hasActiveFilters = ($q !== '' || $category !== '' || $priority !== '' || $status !== '');

$pageTitle = 'My Complaints - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Complaints</h2>
            <p class="text-muted mb-0">View, search, and track all complaints you have submitted.</p>
        </div>
        <div>
            <a href="<?= base_url('complaint_create.php'); ?>" class="btn btn-primary px-3 py-2 shadow-sm fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> + New Complaint
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= base_url('complaints.php'); ?>">
                <div class="row g-3">
                    <!-- Keyword Search -->
                    <div class="col-12 col-lg-4">
                        <label for="q" class="form-label small text-muted mb-1">Search Keywords</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="q" name="q" 
                                   value="<?= e($q); ?>" placeholder="Search code, title, location...">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-6 col-md-4 col-lg-3">
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
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="priority" class="form-label small text-muted mb-1">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All Priorities</option>
                            <?php foreach (COMPLAINT_PRIORITIES as $p): ?>
                                <option value="<?= e($p); ?>" <?= ($priority === $p) ? 'selected' : ''; ?>>
                                    <?= e($p); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-6 col-md-4 col-lg-2">
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

                    <!-- Action Buttons -->
                    <div class="col-6 col-lg-1 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100" title="Apply Filters">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                    </div>
                </div>

                <?php if ($hasActiveFilters): ?>
                    <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Filtered results: <strong><?= count($complaints); ?></strong> complaint(s) found.
                        </div>
                        <a href="<?= base_url('complaints.php'); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Complaints Data Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?php if (empty($complaints)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon text-muted mb-3">
                        <i class="bi bi-search fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1">No complaints found.</h5>
                    <p class="text-muted small mb-3">
                        <?= $hasActiveFilters 
                            ? 'No complaints matched your active filter criteria. Try broadening your search or clear filters.' 
                            : 'You have not submitted any complaints yet.'; ?>
                    </p>
                    <?php if ($hasActiveFilters): ?>
                        <a href="<?= base_url('complaints.php'); ?>" class="btn btn-outline-secondary btn-sm px-3">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('complaint_create.php'); ?>" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-plus-circle me-1"></i> + New Complaint
                        </a>
                    <?php endif; ?>
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
                                <th>Date Filed</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td class="fw-bold">
                                        <span class="badge bg-light text-dark border font-monospace"><?= e($c['complaint_code']); ?></span>
                                    </td>
                                    <td class="fw-semibold text-truncate" style="max-width: 240px;">
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
