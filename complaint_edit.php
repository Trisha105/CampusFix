<?php
/**
 * CampusFix - Edit Student Complaint (Pending Only)
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
// SECURITY & STATUS CHECK:
// 1. Complaint must belong to the logged-in student.
// 2. Complaint status MUST be 'Pending'.
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

if ($complaint['status'] !== 'Pending') {
    set_flash('warning', 'Only complaints with "Pending" status can be edited. This complaint is currently ' . $complaint['status'] . '.');
    redirect('complaint_view.php?id=' . $complaintId);
}

$errors = [];
$title       = $complaint['title'];
$category    = $complaint['category'];
$location    = $complaint['location'];
$priority    = $complaint['priority'];
$description = $complaint['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired session security token. Please try submitting again.';
    }

    // 2. Retrieve Form Inputs
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $priority    = trim($_POST['priority'] ?? 'Medium');
    $description = trim($_POST['description'] ?? '');

    // 3. Validations
    if (mb_strlen($title) < 5 || mb_strlen($title) > 150) {
        $errors[] = 'Complaint title is required and must be between 5 and 150 characters.';
    }
    if (!in_array($category, COMPLAINT_CATEGORIES, true)) {
        $errors[] = 'Please select a valid campus complaint category.';
    }
    if (mb_strlen($location) < 2 || mb_strlen($location) > 150) {
        $errors[] = 'Location is required and must be between 2 and 150 characters.';
    }
    if (!in_array($priority, COMPLAINT_PRIORITIES, true)) {
        $errors[] = 'Please select a valid priority level.';
    }
    if (mb_strlen($description) < 10 || mb_strlen($description) > 2000) {
        $errors[] = 'Detailed description is required and must be between 10 and 2000 characters.';
    }

    // 4. Update Database
    // Security: Student cannot change status, resolution_note, complaint_code, or user_id
    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE complaints 
                SET title = ?, category = ?, location = ?, priority = ?, description = ? 
                WHERE id = ? AND user_id = ? AND status = 'Pending'
            ");
            $updateStmt->execute([
                $title,
                $category,
                $location,
                $priority,
                $description,
                $complaintId,
                $userId
            ]);

            set_flash('success', 'Complaint updated successfully.');
            redirect('complaint_view.php?id=' . $complaintId);
        } catch (Exception $e) {
            error_log('Complaint Update Error: ' . $e->getMessage());
            $errors[] = 'An error occurred while updating the complaint. Please try again.';
        }
    }
}

$pageTitle = 'Edit Complaint: ' . $complaint['complaint_code'] . ' - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Navigation Back Link -->
            <div class="mb-3">
                <a href="<?= base_url('complaint_view.php?id=' . $complaintId); ?>" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Back to Complaint Details
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0 fw-bold d-flex align-items-center">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Complaint
                    </h4>
                    <span class="badge bg-light text-dark border font-monospace"><?= e($complaint['complaint_code']); ?></span>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm mb-4">
                            <h6 class="alert-heading fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Validation Errors:
                            </h6>
                            <ul class="mb-0 ps-3 small">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('complaint_edit.php?id=' . $complaintId); ?>" novalidate>
                        <?= csrf_field(); ?>

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                Complaint Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= e($title); ?>" 
                                   minlength="5" maxlength="150" required autofocus>
                        </div>

                        <!-- Category & Priority -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category" name="category" required>
                                    <?php foreach (COMPLAINT_CATEGORIES as $cat): ?>
                                        <option value="<?= e($cat); ?>" <?= ($category === $cat) ? 'selected' : ''; ?>>
                                            <?= e($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">
                                    Priority <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <?php foreach (COMPLAINT_PRIORITIES as $p): ?>
                                        <option value="<?= e($p); ?>" <?= ($priority === $p) ? 'selected' : ''; ?>>
                                            <?= e($p); ?> Priority
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="mb-3">
                            <label for="location" class="form-label">
                                Campus Location <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?= e($location); ?>" 
                                       minlength="2" maxlength="150" required>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                Detailed Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="5" 
                                      minlength="10" maxlength="2000" required><?= e($description); ?></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <a href="<?= base_url('complaint_view.php?id=' . $complaintId); ?>" class="btn btn-outline-secondary px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
