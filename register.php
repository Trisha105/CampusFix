<?php
/**
 * CampusFix - Student Registration
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors = [];
$fullName  = '';
$studentId = '';
$email     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired session security token. Please try again.';
    }

    // 2. Sanitize & Retrieve Inputs
    $fullName        = trim($_POST['full_name'] ?? '');
    $studentId       = trim($_POST['student_id'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 3. Server-Side Validations
    // Full Name: 2-100 characters
    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors[] = 'Full Name is required and must be between 2 and 100 characters.';
    }

    // Student ID: Optional or max 30 characters
    if ($studentId !== '' && mb_strlen($studentId) > 30) {
        $errors[] = 'Student ID must not exceed 30 characters.';
    }

    // Email: Valid email format, max 150 characters
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = 'Please provide a valid email address (maximum 150 characters).';
    }

    // Password: Minimum 8 characters
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters in length.';
    }

    // Confirm Password: Must match
    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirmation password do not match.';
    }

    // Database Uniqueness Checks
    if (empty($errors)) {
        $pdo = getDBConnection();

        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email address already exists. Please log in.';
        }

        // Check duplicate student_id if provided
        if ($studentId !== '') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ? LIMIT 1");
            $stmt->execute([$studentId]);
            if ($stmt->fetch()) {
                $errors[] = 'This Student ID is already registered to another account.';
            }
        }
    }

    // 4. Save New Student
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $normalizedStudentId = ($studentId !== '') ? $studentId : null;

            // Enforce role = 'student' strictly on the server
            $insertStmt = $pdo->prepare("
                INSERT INTO users (full_name, student_id, email, password, role) 
                VALUES (?, ?, ?, ?, 'student')
            ");
            $insertStmt->execute([$fullName, $normalizedStudentId, $email, $passwordHash]);

            set_flash('success', 'Registration successful! Please log in with your credentials.');
            redirect('login.php');
        } catch (Exception $e) {
            error_log('Registration Error: ' . $e->getMessage());
            $errors[] = 'A database error occurred while creating your account. Please try again.';
        }
    }
}

$pageTitle = 'Student Registration - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6 col-xl-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-2" style="width: 54px; height: 54px;">
                            <i class="bi bi-person-plus-fill fs-3"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Create Account</h3>
                        <p class="text-muted small">Register as a student to file and track campus complaints.</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm mb-4">
                            <h6 class="alert-heading fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the following:
                            </h6>
                            <ul class="mb-0 ps-3 small">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('register.php'); ?>" novalidate>
                        <?= csrf_field(); ?>

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= e($fullName); ?>" placeholder="e.g. Jane Smith" required autofocus>
                            </div>
                        </div>

                        <!-- Student ID -->
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student ID <span class="text-muted small">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-card-text"></i></span>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       value="<?= e($studentId); ?>" placeholder="e.g. 2024-CSE-001">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Campus Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= e($email); ?>" placeholder="name@university.edu" required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Minimum 8 characters" required>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Re-enter your password" required>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                                <i class="bi bi-person-check me-1"></i> Register Account
                            </button>
                        </div>

                        <div class="text-center">
                            <span class="text-muted small">Already registered?</span>
                            <a href="<?= base_url('login.php'); ?>" class="small fw-semibold ms-1 text-primary">Log in here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
