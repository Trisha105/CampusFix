<?php
/**
 * CampusFix - Student Complaint Details View
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Restrict access to authenticated students only
requireStudent();

$userId = currentUserId();
$pdo = getDBConnection();

// Validate ID query parameter
$complaintId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$complaintId) {
    set_flash('danger', 'Invalid complaint identifier requested.');
    redirect('complaints.php');
}

// =======================================================
// SECURITY CRITICAL RULE:
// Query using BOTH complaint id AND logged-in user_id.
// This guarantees students cannot view another student's
// complaint by manually changing the ID in the URL query string.
// =======================================================
$stmt = $pdo->prepare("
    SELECT * 
    FROM complaints 
    WHERE id = ? AND user_id = ? 
    LIMIT 1
");
$stmt->execute([$complaintId, $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('danger', 'You do not have permission to access that complaint.');
    redirect('complaints.php');
}

$pageTitle = 'Complaint Details: ' . $complaint['complaint_code'] . ' - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb / Back Link -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('complaints.php'); ?>">My Complaints</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($complaint['complaint_code']); ?></li>
            </ol>
        </nav>
        <a href="<?= base_url('complaints.php'); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Complaints
        </a>
    </div>

    <!-- Main Complaint Details Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark font-monospace fs-6 px-3 py-2"><?= e($complaint['complaint_code']); ?></span>
                <h4 class="mb-0 fw-bold"><?= e($complaint['title']); ?></h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?= priority_badge($complaint['priority']); ?>
                <?= status_badge($complaint['status']); ?>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Metadata Summary Grid -->
            <div class="row g-3 p-3 bg-light rounded-3 mb-4 border">
                <div class="col-sm-6 col-md-3">
                    <div class="small text-muted mb-1"><i class="bi bi-tag me-1"></i> Category</div>
                    <div class="fw-semibold"><?= e($complaint['category']); ?></div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i> Campus Location</div>
                    <div class="fw-semibold"><?= e($complaint['location']); ?></div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="small text-muted mb-1"><i class="bi bi-calendar-event me-1"></i> Date Submitted</div>
                    <div class="fw-semibold"><?= format_date($complaint['created_at']); ?></div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="small text-muted mb-1"><i class="bi bi-clock-history me-1"></i> Last Updated</div>
                    <div class="fw-semibold"><?= format_date($complaint['updated_at']); ?></div>
                </div>
            </div>

            <!-- Problem Description -->
            <div class="mb-4">
                <h6 class="fw-bold text-navy mb-2"><i class="bi bi-card-text me-1 text-primary"></i> Problem Description</h6>
                <div class="p-3 bg-white border rounded-3 text-secondary" style="line-height: 1.6; white-space: pre-line;">
                    <?= e($complaint['description']); ?>
                </div>
            </div>

            <!-- Administrative Resolution Note Section -->
            <?php if (!empty($complaint['resolution_note'])): ?>
                <div class="resolution-box mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                        <h6 class="fw-bold text-success mb-0">Official Resolution Note from Administration</h6>
                    </div>
                    <p class="mb-0 text-dark" style="line-height: 1.6; white-space: pre-line;">
                        <?= e($complaint['resolution_note']); ?>
                    </p>
                </div>
            <?php elseif ($complaint['status'] === 'Resolved'): ?>
                <div class="alert alert-success d-flex align-items-center mb-4">
                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                    <div>This complaint has been marked as <strong>Resolved</strong> by the administration.</div>
                </div>
            <?php endif; ?>

            <!-- Action Controls (Edit & Delete only when status is Pending) -->
            <div class="border-top pt-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <a href="<?= base_url('complaints.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Complaints
                    </a>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($complaint['status'] === 'Pending'): ?>
                        <!-- Edit Button -->
                        <a href="<?= base_url('complaint_edit.php?id=' . (int)$complaint['id']); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil-square me-1"></i> Edit Complaint
                        </a>

                        <!-- Delete Form (Strict POST with CSRF and Confirmation) -->
                        <form method="POST" action="<?= base_url('complaint_delete.php'); ?>" class="form-delete d-inline" 
                              data-confirm="Are you sure you want to permanently delete this complaint? This cannot be undone.">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="id" value="<?= (int)$complaint['id']; ?>">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-trash3 me-1"></i> Delete
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="small text-muted bg-light px-3 py-2 rounded-2 border">
                            <i class="bi bi-lock-fill text-secondary me-1"></i>
                            This complaint is currently <strong><?= e($complaint['status']); ?></strong>. Modifications and deletion are locked after administrative review begins.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
