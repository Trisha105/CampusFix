<?php
/**
 * CampusFix - Reusable Header & Role-Aware Navigation
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'CampusFix - Campus Complaint Tracking System';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$isAdminDir  = ($currentDir === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<!-- Main Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark campus-navbar sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="<?= base_url(isAdmin() ? 'admin/dashboard.php' : (isLoggedIn() ? 'dashboard.php' : 'index.php')); ?>">
            <span class="brand-icon me-2"><i class="bi bi-shield-check"></i></span>
            <span>Campus<span class="text-teal-accent">Fix</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (!isLoggedIn()): ?>
                    <!-- Guest Navigation Links -->
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : ''; ?>" href="<?= base_url('index.php'); ?>">
                            <i class="bi bi-house-door me-1"></i> Home
                        </a>
                    </li>
                <?php elseif (isAdmin()): ?>
                    <!-- Admin Navigation Links -->
                    <li class="nav-item">
                        <a class="nav-link <?= ($isAdminDir && $currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="<?= base_url('admin/dashboard.php'); ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($isAdminDir && ($currentPage === 'complaints.php' || $currentPage === 'complaint_view.php')) ? 'active' : ''; ?>" href="<?= base_url('admin/complaints.php'); ?>">
                            <i class="bi bi-card-checklist me-1"></i> Manage Complaints
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($isAdminDir && $currentPage === 'users.php') ? 'active' : ''; ?>" href="<?= base_url('admin/users.php'); ?>">
                            <i class="bi bi-people me-1"></i> Users
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Student Navigation Links -->
                    <li class="nav-item">
                        <a class="nav-link <?= (!$isAdminDir && $currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="<?= base_url('dashboard.php'); ?>">
                            <i class="bi bi-grid me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (!$isAdminDir && ($currentPage === 'complaints.php' || $currentPage === 'complaint_view.php' || $currentPage === 'complaint_edit.php')) ? 'active' : ''; ?>" href="<?= base_url('complaints.php'); ?>">
                            <i class="bi bi-list-task me-1"></i> My Complaints
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'complaint_create.php') ? 'active' : ''; ?>" href="<?= base_url('complaint_create.php'); ?>">
                            <i class="bi bi-plus-circle me-1"></i> New Complaint
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav align-items-lg-center">
                <?php if (!isLoggedIn()): ?>
                    <!-- Guest Auth Actions -->
                    <li class="nav-item me-2">
                        <a class="nav-link <?= ($currentPage === 'login.php') ? 'active' : ''; ?>" href="<?= base_url('login.php'); ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-teal btn-sm px-3 rounded-pill fw-semibold" href="<?= base_url('register.php'); ?>">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Authenticated User Profile & Logout -->
                    <?php if (isStudent()): ?>
                        <li class="nav-item me-lg-2">
                            <a class="nav-link <?= ($currentPage === 'profile.php') ? 'active' : ''; ?>" href="<?= base_url('profile.php'); ?>">
                                <i class="bi bi-person me-1"></i> Profile
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item user-info-pill me-lg-3 my-2 my-lg-0">
                        <span class="badge rounded-pill bg-dark-teal text-white px-3 py-2 border border-light-subtle d-inline-flex align-items-center">
                            <i class="bi <?= isAdmin() ? 'bi-shield-lock-fill text-warning' : 'bi-mortarboard-fill text-info'; ?> me-2"></i>
                            <span class="user-display-name me-2"><?= e($_SESSION['full_name'] ?? 'User'); ?></span>
                            <span class="badge <?= isAdmin() ? 'bg-warning text-dark' : 'bg-info text-dark'; ?> text-uppercase role-badge"><?= e($_SESSION['role'] ?? 'student'); ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm px-3 rounded-pill" href="<?= base_url('logout.php'); ?>">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Global Flash Message Display -->
<div class="container mt-3">
    <?= render_flash_messages(); ?>
</div>

<main class="flex-grow-1 pb-5">
