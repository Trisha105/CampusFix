<?php
/**
 * CampusFix - Authentication and Authorization Helpers
 *
 * Manages sessions, authentication status, and role-based access control.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/functions.php';

/**
 * Checks if a user is currently logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user is an administrator.
 *
 * @return bool
 */
function isAdmin(): bool {
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin');
}

/**
 * Checks if the logged-in user is a student.
 *
 * @return bool
 */
function isStudent(): bool {
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'student');
}

/**
 * Returns the current authenticated user's ID or null if unauthenticated.
 *
 * @return int|null
 */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Returns an array containing the current authenticated user's session data.
 *
 * @return array|null
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'         => (int)$_SESSION['user_id'],
        'full_name'  => $_SESSION['full_name'] ?? '',
        'email'      => $_SESSION['email'] ?? '',
        'student_id' => $_SESSION['student_id'] ?? null,
        'role'       => $_SESSION['role'] ?? 'student',
    ];
}

/**
 * Requires that a user is logged in. Redirects to login page if guest.
 *
 * @return void
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        set_flash('danger', 'Please log in to access this page.');
        redirect('login.php');
    }
}

/**
 * Requires that the logged-in user is a student.
 * Admins are redirected to their admin dashboard.
 *
 * @return void
 */
function requireStudent(): void {
    requireLogin();

    if (isAdmin()) {
        redirect('admin/dashboard.php');
    }
}

/**
 * Requires that the logged-in user is an administrator.
 * Non-admins are blocked and redirected with an error message.
 *
 * @return void
 */
function requireAdmin(): void {
    requireLogin();

    if (!isAdmin()) {
        set_flash('danger', 'Access denied. Administrator privileges are required.');
        redirect('dashboard.php');
    }
}
