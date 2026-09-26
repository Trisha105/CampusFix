<?php
/**
 * CampusFix - User Login (Student & Administrator)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Verification
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired session security token. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both your email address and password.';
        } else {
            $pdo = getDBConnection();

            // 2. Query User by Email
            $stmt = $pdo->prepare("
                SELECT id, full_name, student_id, email, password, role 
                FROM users 
                WHERE email = ? 
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 3. Verify Password securely
            if ($user && password_verify($password, $user['password'])) {
                // Prevent session fixation
                session_regenerate_id(true);

                // Store user session state
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['role']       = $user['role'];

                // 4. Role-aware redirect
                if ($user['role'] === 'admin') {
                    set_flash('success', 'Welcome back, Administrator!');
                    redirect('admin/dashboard.php');
                } else {
                    set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                    redirect('dashboard.php');
                }
            } else {
                // Generic error prevents user enumeration
                $error = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-2" style="width: 54px; height: 54px;">
                            <i class="bi bi-box-arrow-in-right fs-3"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Welcome Back</h3>
                        <p class="text-muted small">Sign in to your CampusFix account</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger shadow-sm mb-4 d-flex align-items-center">
                            <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                            <div><?= e($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('login.php'); ?>" novalidate>
                        <?= csrf_field(); ?>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= e($email); ?>" placeholder="name@university.edu" required autofocus>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                            </button>
                        </div>

                        <div class="text-center">
                            <span class="text-muted small">Need a student account?</span>
                            <a href="<?= base_url('register.php'); ?>" class="small fw-semibold ms-1 text-primary">Register here</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Demo Hint Box for Evaluation -->
            <div class="card mt-3 bg-light border-0 shadow-sm">
                <div class="card-body p-3 small text-muted">
                    <strong class="d-block text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i>Demo Account Credentials:</strong>
                    <div class="d-flex justify-content-between">
                        <span>Admin:</span>
                        <code>admin@campusfix.edu / admin123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
