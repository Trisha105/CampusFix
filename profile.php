<?php
/**
 * CampusFix - Student Profile Management
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Restrict access to authenticated students only
requireStudent();

$userId = currentUserId();
$pdo = getDBConnection();

$errors = [];

// Fetch current user details from database
$stmt = $pdo->prepare("
    SELECT id, full_name, student_id, email, role, created_at 
    FROM users 
    WHERE id = ? 
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

$fullName  = $user['full_name'];
$studentId = $user['student_id'] ?? '';
$email     = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired session security token. Please try again.';
    }

    // 2. Retrieve Submitted Inputs
    $fullName  = trim($_POST['full_name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');

    // 3. Validations
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors[] = 'Full Name is required and must be between 2 and 100 characters.';
    }

    if ($studentId !== '' && mb_strlen($studentId) > 30) {
        $errors[] = 'Student ID must not exceed 30 characters.';
    }

    // Uniqueness Check for Student ID (against other user records)
    if (empty($errors) && $studentId !== '') {
        $checkStmt = $pdo->prepare("
            SELECT id 
            FROM users 
            WHERE student_id = ? AND id != ? 
            LIMIT 1
        ");
        $checkStmt->execute([$studentId, $userId]);
        if ($checkStmt->fetch()) {
            $errors[] = 'This Student ID is already assigned to another account.';
        }
    }

    // 4. Update Profile
    if (empty($errors)) {
        try {
            $normalizedStudentId = ($studentId !== '') ? $studentId : null;

            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, student_id = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$fullName, $normalizedStudentId, $userId]);

            // Update session cache
            $_SESSION['full_name']  = $fullName;
            $_SESSION['student_id'] = $normalizedStudentId;

            set_flash('success', 'Profile updated successfully.');
            redirect('profile.php');
        } catch (Exception $e) {
            error_log('Profile Update Error: ' . $e->getMessage());
            $errors[] = 'An error occurred while updating your profile. Please try again.';
        }
    }
}

$pageTitle = 'My Profile - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <!-- Breadcrumb Navigation -->
            <div class="mb-3">
                <a href="<?= base_url('dashboard.php'); ?>" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <i class="bi bi-person-circle fs-4 text-primary me-2"></i>
                    <h4 class="card-title mb-0 fw-bold">Student Profile</h4>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm mb-4">
                            <h6 class="alert-heading fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Update Errors:
                            </h6>
                            <ul class="mb-0 ps-3 small">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('profile.php'); ?>" novalidate>
                        <?= csrf_field(); ?>

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= e($fullName); ?>" 
                                       minlength="2" maxlength="100" required>
                            </div>
                        </div>

                        <!-- Student ID -->
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-card-text"></i></span>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       value="<?= e($studentId); ?>" 
                                       placeholder="e.g. STU-2024-001" maxlength="30">
                            </div>
                            <div class="form-text">Your institutional student identification number.</div>
                        </div>

                        <!-- Email Address (Read-Only) -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="badge bg-secondary-subtle text-secondary small ms-1">Read-Only</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control bg-light" id="email" name="email" 
                                       value="<?= e($email); ?>" readonly>
                            </div>
                            <div class="form-text">Email address is linked to your account credentials and cannot be modified here.</div>
                        </div>

                        <!-- Account Role & Registration Date (Read-Only Badges) -->
                        <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">Account Role</div>
                                <span class="badge bg-info text-dark text-uppercase"><?= e($user['role']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">Member Since</div>
                                <div class="small fw-semibold text-secondary"><?= format_date($user['created_at']); ?></div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <a href="<?= base_url('dashboard.php'); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
