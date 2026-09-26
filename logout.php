<?php
/**
 * CampusFix - User Logout
 *
 * Securely destroys session state and redirects to the login screen.
 */
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// 1. Unset all session variables
$_SESSION = [];

// 2. Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy the session data on the server
session_destroy();

// 4. Start a fresh session to record the logout confirmation flash notice
session_start();
set_flash('info', 'You have been safely logged out.');

// 5. Redirect to login
redirect('login.php');
