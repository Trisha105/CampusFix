<?php
/** Public sample gallery. The production complaint queries remain database-backed. */
require_once __DIR__ . '/includes/functions.php';

$allComplaints = require __DIR__ . '/data/demo_complaints.php';
$q = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$priority = trim((string)($_GET['priority'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$availableCategories = array_values(array_filter(
    COMPLAINT_CATEGORIES,
    static fn (string $value): bool => in_array($value, array_column($allComplaints, 'category'), true)
));

$complaints = array_values(array_filter($allComplaints, static function (array $complaint) use ($q, $category, $priority, $status): bool {
    if ($q !== '' && stripos(implode(' ', [
        $complaint['id'], $complaint['title'], $complaint['description'],
        $complaint['location'], $complaint['reporter'],
    ]), $q) === false) {
        return false;
    }
    return ($category === '' || $complaint['category'] === $category)
        && ($priority === '' || $complaint['priority'] === $priority)
        && ($status === '' || $complaint['status'] === $status);
}));
usort($complaints, static fn (array $a, array $b): int => strcmp($b['submitted_at'], $a['submitted_at']));
$hasActiveFilters = $q !== '' || $category !== '' || $priority !== '' || $status !== '';

$pageTitle = 'Demo Complaints - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="badge bg-teal-accent text-teal-dark border border-teal-subtle mb-2">Sample data</span>
            <h1 class="h2 fw-bold mb-1">Campus Complaint Examples</h1>
            <p class="text-muted mb-0">Explore realistic reports and the way their status changes over time.</p>
        </div>
        <?php if (!isLoggedIn()): ?>
            <a class="btn btn-primary px-3 py-2 shadow-sm fw-semibold" href="<?= base_url('register.php'); ?>">
                <i class="bi bi-plus-circle me-1"></i> Submit a real complaint
            </a>
        <?php elseif (isStudent()): ?>
            <a class="btn btn-primary px-3 py-2 shadow-sm fw-semibold" href="<?= base_url('complaint_create.php'); ?>">
                <i class="bi bi-plus-circle me-1"></i> Submit a real complaint
            </a>
        <?php endif; ?>
    </div>

    <div class="alert alert-info border-info-subtle small" role="note">
        <i class="bi bi-info-circle me-1"></i> These <?= count($allComplaints); ?> examples are read-only sample data. They are separate from submitted complaints and do not affect account records.
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= base_url('demo_complaints.php'); ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="q" class="form-label small text-muted mb-1">Search examples</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input class="form-control" type="search" id="q" name="q" value="<?= e($q); ?>" placeholder="ID, title, location, reporter...">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label for="category" class="form-label small text-muted mb-1">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($availableCategories as $value): ?>
                                <option value="<?= e($value); ?>" <?= $category === $value ? 'selected' : ''; ?>><?= e($value); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="priority" class="form-label small text-muted mb-1">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All Priorities</option>
                            <?php foreach (COMPLAINT_PRIORITIES as $value): ?>
                                <option value="<?= e($value); ?>" <?= $priority === $value ? 'selected' : ''; ?>><?= e($value); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="status" class="form-label small text-muted mb-1">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (COMPLAINT_STATUSES as $value): ?>
                                <option value="<?= e($value); ?>" <?= $status === $value ? 'selected' : ''; ?>><?= e($value); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-1">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                </div>
                <?php if ($hasActiveFilters): ?>
                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center gap-2 small">
                        <span class="text-muted">Filtered results: <strong><?= count($complaints); ?></strong> complaint(s) found.</span>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('demo_complaints.php'); ?>"><i class="bi bi-x-circle me-1"></i> Clear Filters</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (!$complaints): ?>
        <div class="card shadow-sm border-0"><div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-search"></i></div>
            <h2 class="h5 fw-bold">No examples found.</h2>
            <p class="text-muted small">Try a different search or clear the filters.</p>
            <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('demo_complaints.php'); ?>">Clear Filters</a>
        </div></div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($complaints as $complaint): ?>
                <div class="col-md-6 col-xl-4">
                    <article class="card demo-complaint-card shadow-sm border-0 h-100">
                        <img class="demo-complaint-image card-img-top" src="<?= base_url($complaint['image']); ?>" alt="Illustration of <?= e($complaint['title']); ?>" loading="lazy">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge bg-light text-dark border font-monospace"><?= e($complaint['id']); ?></span>
                                <?= status_badge($complaint['status']); ?>
                            </div>
                            <h2 class="h5 fw-bold mb-2"><?= e($complaint['title']); ?></h2>
                            <p class="small text-muted demo-description mb-3"><?= e($complaint['description']); ?></p>
                            <div class="small text-muted d-grid gap-1 mb-3">
                                <span><i class="bi bi-tag me-1"></i><?= e($complaint['category']); ?> · <?= priority_badge($complaint['priority']); ?></span>
                                <span><i class="bi bi-geo-alt me-1"></i><?= e($complaint['location']); ?></span>
                                <span><i class="bi bi-person me-1"></i><?= e($complaint['reporter']); ?> · <?= format_date($complaint['submitted_at']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top">
                                <span class="small text-muted" aria-label="<?= (int)$complaint['upvotes']; ?> upvotes"><i class="bi bi-hand-thumbs-up me-1"></i><?= (int)$complaint['upvotes']; ?> upvotes</span>
                                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('demo_complaint_view.php?id=' . rawurlencode($complaint['id'])); ?>">View details <i class="bi bi-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
