<?php
/**
 * CampusFix - Submit New Campus Complaint
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Restrict access to students only
requireStudent();

$userId = currentUserId();
$errors = [];

// Form field states
$title       = '';
$category    = '';
$location    = '';
$priority    = 'Medium';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Verification
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired session security token. Please try submitting again.';
    }

    // 2. Retrieve and Sanitize Form Inputs
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $priority    = trim($_POST['priority'] ?? 'Medium');
    $description = trim($_POST['description'] ?? '');

    // 3. Server-Side Validations
    // Title: 5 - 150 characters
    if (mb_strlen($title) < 5 || mb_strlen($title) > 150) {
        $errors[] = 'Complaint title is required and must be between 5 and 150 characters.';
    }

    // Category: Must match allowed taxonomy list
    if (!in_array($category, COMPLAINT_CATEGORIES, true)) {
        $errors[] = 'Please select a valid campus complaint category.';
    }

    // Location: 2 - 150 characters
    if (mb_strlen($location) < 2 || mb_strlen($location) > 150) {
        $errors[] = 'Location is required and must be between 2 and 150 characters (e.g. "Room 402, Science Building").';
    }

    // Priority: Low / Medium / High only
    if (!in_array($priority, COMPLAINT_PRIORITIES, true)) {
        $errors[] = 'Please select a valid priority level (Low, Medium, or High).';
    }

    // Description: 10 - 2000 characters
    if (mb_strlen($description) < 10 || mb_strlen($description) > 2000) {
        $errors[] = 'Detailed description is required and must be between 10 and 2000 characters.';
    }

    // 4. Database Insertion with Safe Complaint Code Generation
    if (empty($errors)) {
        $pdo = getDBConnection();

        try {
            $pdo->beginTransaction();

            // Insert initial record with placeholder code; Status is forced to 'Pending'
            $insertStmt = $pdo->prepare("
                INSERT INTO complaints (complaint_code, user_id, title, category, location, priority, description, status) 
                VALUES ('TEMP', ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $insertStmt->execute([
                $userId,
                $title,
                $category,
                $location,
                $priority,
                $description
            ]);

            $newId = (int)$pdo->lastInsertId();

            // Format readable complaint code: CMP-0001, CMP-0002, etc.
            $complaintCode = sprintf('CMP-%04d', $newId);

            // Update complaint record with unique formatted code
            $updateCodeStmt = $pdo->prepare("UPDATE complaints SET complaint_code = ? WHERE id = ?");
            $updateCodeStmt->execute([$complaintCode, $newId]);

            $pdo->commit();

            set_flash('success', "Complaint submitted successfully! Your tracking code is {$complaintCode}.");
            redirect('dashboard.php');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Complaint Submission Error: ' . $e->getMessage());
            $errors[] = 'An error occurred while filing your complaint. Please try again.';
        }
    }
}

$pageTitle = 'Submit Complaint - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Navigation Back Link -->
            <div class="mb-3">
                <a href="<?= base_url('dashboard.php'); ?>" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h4 class="card-title mb-0 fw-bold d-flex align-items-center">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Submit Campus Complaint
                    </h4>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm mb-4">
                            <h6 class="alert-heading fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Submission Errors:
                            </h6>
                            <ul class="mb-0 ps-3 small">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('complaint_create.php'); ?>" novalidate>
                        <?= csrf_field(); ?>

                        <!-- Complaint Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                Complaint Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= e($title); ?>" 
                                   placeholder="e.g. Wi-Fi unavailable in CSE Lab 2" 
                                   minlength="5" maxlength="150" required autofocus>
                            <div class="form-text">Provide a concise, specific summary of the issue (5 - 150 characters).</div>
                        </div>

                        <!-- Category & Priority (2 Column Row) -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled <?= empty($category) ? 'selected' : ''; ?>>-- Select Category --</option>
                                    <?php foreach (COMPLAINT_CATEGORIES as $cat): ?>
                                        <option value="<?= e($cat); ?>" <?= ($category === $cat) ? 'selected' : ''; ?>>
                                            <?= e($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">
                                    Initial Priority <span class="text-danger">*</span>
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

                        <!-- Specific Location -->
                        <div class="mb-3">
                            <label for="location" class="form-label">
                                Specific Campus Location <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?= e($location); ?>" 
                                       placeholder="e.g. Academic Building 2, 4th Floor, Lab 402" 
                                       minlength="2" maxlength="150" required>
                            </div>
                            <div class="form-text">Where is this problem located on campus? (2 - 150 characters).</div>
                        </div>

                        <!-- Detailed Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                Detailed Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="5" 
                                      placeholder="Please explain the exact issue, symptoms, equipment affected, and when it started..." 
                                      minlength="10" maxlength="2000" required><?= e($description); ?></textarea>
                            <div class="form-text">Describe the issue clearly for facilities staff (10 - 2000 characters).</div>
                        </div>

                        <!-- Hidden Status Assurance Notice -->
                        <div class="alert alert-light border small text-muted mb-4 d-flex align-items-center">
                            <i class="bi bi-shield-check text-success fs-5 me-2"></i>
                            <div>All new complaints are automatically registered with status <strong>Pending</strong> for administrative review.</div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('dashboard.php'); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                                <i class="bi bi-send me-1"></i> Submit Complaint
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
