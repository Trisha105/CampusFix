<?php
/**
 * CampusFix - Admin Complaint Detail View & Management
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Restrict access strictly to administrators
requireAdmin();

$pdo = getDBConnection();

// Validate complaint ID from URL
$complaintId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$complaintId) {
    set_flash('danger', 'Invalid complaint identifier.');
    redirect('admin/complaints.php');
}

// Fetch the complaint along with its submitter's information
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.full_name AS student_name,
        u.email     AS student_email,
        u.student_id AS student_number,
        u.created_at AS student_joined
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('danger', 'The requested complaint does not exist.');
    redirect('admin/complaints.php');
}

$pageTitle = 'Manage: ' . $complaint['complaint_code'] . ' - CampusFix Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard.php'); ?>">Admin Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('admin/complaints.php'); ?>">All Complaints</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($complaint['complaint_code']); ?></li>
            </ol>
        </nav>
        <a href="<?= base_url('admin/complaints.php'); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Complaints
        </a>
    </div>

    <div class="row g-4">
        <!-- LEFT: Complaint Details -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark font-monospace fs-6 px-3 py-2"><?= e($complaint['complaint_code']); ?></span>
                        <h5 class="mb-0 fw-bold"><?= e($complaint['title']); ?></h5>
                    </div>
                    <div class="d-flex gap-2">
                        <?= priority_badge($complaint['priority']); ?>
                        <?= status_badge($complaint['status']); ?>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Complaint Metadata -->
                    <div class="row g-3 p-3 bg-light rounded-3 border mb-4">
                        <div class="col-sm-6">
                            <div class="small text-muted mb-1"><i class="bi bi-tag me-1"></i>Category</div>
                            <div class="fw-semibold"><?= e($complaint['category']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>Campus Location</div>
                            <div class="fw-semibold"><?= e($complaint['location']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>Date Submitted</div>
                            <div class="fw-semibold"><?= format_date($complaint['created_at']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted mb-1"><i class="bi bi-clock-history me-1"></i>Last Updated</div>
                            <div class="fw-semibold"><?= format_date($complaint['updated_at']); ?></div>
                        </div>
                    </div>

                    <!-- Problem Description -->
                    <h6 class="fw-bold mb-2"><i class="bi bi-card-text me-1 text-primary"></i>Problem Description</h6>
                    <div class="p-3 bg-white border rounded-3 text-secondary mb-4" style="line-height: 1.6; white-space: pre-line; min-height: 80px;">
                        <?= e($complaint['description']); ?>
                    </div>

                    <!-- Existing Resolution Note (if any) -->
                    <?php if (!empty($complaint['resolution_note'])): ?>
                        <div class="resolution-box">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                                <h6 class="fw-bold text-success mb-0">Current Resolution Note</h6>
                            </div>
                            <p class="mb-0 text-dark" style="line-height: 1.6; white-space: pre-line;">
                                <?= e($complaint['resolution_note']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: Student Info + Admin Management Controls -->
        <div class="col-lg-5">
            <!-- Submitting Student Information Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold d-flex align-items-center">
                        <i class="bi bi-person-badge text-primary me-2"></i> Submitting Student
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3" style="width:44px; height:44px; border-radius:50%;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?= e($complaint['student_name']); ?></div>
                            <div class="small text-muted"><?= e($complaint['student_email']); ?></div>
                        </div>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-5 text-muted">Student ID</div>
                        <div class="col-7 fw-semibold">
                            <?= !empty($complaint['student_number']) ? e($complaint['student_number']) : '<span class="text-muted">N/A</span>'; ?>
                        </div>
                        <div class="col-5 text-muted">Registered</div>
                        <div class="col-7 fw-semibold"><?= format_date($complaint['student_joined']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Admin Management Form -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning bg-opacity-10 border-warning py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold d-flex align-items-center">
                        <i class="bi bi-sliders text-warning me-2"></i> Administrator Controls
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= base_url('admin/complaint_update.php'); ?>" novalidate>
                        <?= csrf_field(); ?>
                        <input type="hidden" name="complaint_id" value="<?= (int)$complaint['id']; ?>">

                        <!-- Priority Control -->
                        <div class="mb-3">
                            <label for="admin_priority_select" class="form-label fw-semibold">
                                <i class="bi bi-flag me-1 text-danger"></i> Priority Level
                            </label>
                            <select class="form-select" id="admin_priority_select" name="priority" required>
                                <?php foreach (COMPLAINT_PRIORITIES as $p): ?>
                                    <option value="<?= e($p); ?>" <?= ($complaint['priority'] === $p) ? 'selected' : ''; ?>>
                                        <?= e($p); ?> Priority
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status Control -->
                        <div class="mb-3">
                            <label for="admin_status_select" class="form-label fw-semibold">
                                <i class="bi bi-arrow-repeat me-1 text-info"></i> Complaint Status
                            </label>
                            <select class="form-select" id="admin_status_select" name="status" required>
                                <?php foreach (COMPLAINT_STATUSES as $s): ?>
                                    <option value="<?= e($s); ?>" <?= ($complaint['status'] === $s) ? 'selected' : ''; ?>>
                                        <?= e($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Resolution Note -->
                        <div class="mb-4">
                            <label for="resolution_note_input" class="form-label fw-semibold">
                                <i class="bi bi-check-circle me-1 text-success"></i> Resolution Note
                                <span id="resolution_required_indicator" class="badge bg-danger ms-1 small d-none">Required when Resolved</span>
                            </label>
                            <textarea class="form-control" id="resolution_note_input" name="resolution_note" rows="4" 
                                      placeholder="Describe the resolution, assigned team, and completion details..."><?= e($complaint['resolution_note'] ?? ''); ?></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Resolution note is <strong>mandatory</strong> when setting status to <strong>Resolved</strong>.
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning fw-semibold py-2 shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Complaint Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/complaint_images.php'; ?>
<?php require_once __DIR__ . '/../includes/complaint_activity.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
