<?php
/** Details for one read-only sample complaint. */
require_once __DIR__ . '/includes/functions.php';
$complaints = require __DIR__ . '/data/demo_complaints.php';
$id = (string)($_GET['id'] ?? '');
$complaint = null;
if (preg_match('/^DEMO-2026-\d{3}$/', $id)) {
    foreach ($complaints as $item) {
        if ($item['id'] === $id) {
            $complaint = $item;
            break;
        }
    }
}
if ($complaint === null) {
    http_response_code(404);
}
$pageTitle = $complaint ? $complaint['title'] . ' - CampusFix Demo' : 'Example Not Found - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <?php if ($complaint === null): ?>
        <div class="card shadow-sm border-0"><div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-exclamation-circle"></i></div>
            <h1 class="h4 fw-bold">Example complaint not found</h1>
            <p class="text-muted">The sample ID may be incorrect or no longer available.</p>
            <a class="btn btn-primary" href="<?= base_url('demo_complaints.php'); ?>">Browse examples</a>
        </div></div>
    <?php else: ?>
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('index.php'); ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('demo_complaints.php'); ?>">Demo Complaints</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($complaint['id']); ?></li>
            </ol>
        </nav>
        <div class="alert alert-info border-info-subtle small" role="note"><i class="bi bi-info-circle me-1"></i> This is a read-only example, separate from real complaints.</div>
        <div class="row g-4">
            <div class="col-lg-8">
                <article class="card shadow-sm border-0 overflow-hidden">
                    <img class="demo-detail-image" src="<?= base_url($complaint['image']); ?>" alt="Illustration of <?= e($complaint['title']); ?>">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-dark font-monospace px-3 py-2"><?= e($complaint['id']); ?></span>
                            <?= priority_badge($complaint['priority']); ?>
                            <?= status_badge($complaint['status']); ?>
                        </div>
                        <h1 class="h3 fw-bold mb-3"><?= e($complaint['title']); ?></h1>
                        <div class="row g-3 p-3 bg-light rounded-3 mb-4 border">
                            <div class="col-sm-6"><div class="small text-muted mb-1"><i class="bi bi-tag me-1"></i>Category</div><div class="fw-semibold"><?= e($complaint['category']); ?></div></div>
                            <div class="col-sm-6"><div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>Campus Location</div><div class="fw-semibold"><?= e($complaint['location']); ?></div></div>
                            <div class="col-sm-6"><div class="small text-muted mb-1"><i class="bi bi-person me-1"></i>Reporter</div><div class="fw-semibold"><?= e($complaint['reporter']); ?></div></div>
                            <div class="col-sm-6"><div class="small text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>Submitted</div><div class="fw-semibold"><?= format_date($complaint['submitted_at']); ?></div></div>
                        </div>
                        <h2 class="h6 fw-bold text-navy mb-2"><i class="bi bi-card-text me-1 text-primary"></i>Problem Description</h2>
                        <p class="p-3 bg-white border rounded-3 text-secondary mb-4" style="line-height:1.6;"><?= e($complaint['description']); ?></p>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top pt-3">
                            <span class="small text-muted" aria-label="<?= (int)$complaint['upvotes']; ?> upvotes"><i class="bi bi-hand-thumbs-up me-1"></i><?= (int)$complaint['upvotes']; ?> upvotes</span>
                            <a class="btn btn-outline-secondary" href="<?= base_url('demo_complaints.php'); ?>"><i class="bi bi-arrow-left me-1"></i>Back to examples</a>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-lg-4">
                <section class="card shadow-sm border-0" aria-labelledby="timeline-title">
                    <div class="card-header bg-white py-3 border-bottom"><h2 id="timeline-title" class="h6 fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Status Update Timeline</h2></div>
                    <div class="card-body p-4">
                        <ol class="demo-timeline list-unstyled mb-0">
                            <?php foreach (array_reverse($complaint['timeline']) as $event): ?>
                                <li class="demo-timeline-item">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1"><?= status_badge($event['status']); ?><span class="small text-muted"><?= format_date($event['at']); ?></span></div>
                                    <p class="small text-secondary mb-0"><?= e($event['note']); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </section>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
