<?php
/**
 * CampusFix - Public Landing Page
 */
$pageTitle = 'CampusFix - Campus Complaint Tracking System';
require_once __DIR__ . '/includes/header.php';

// Category metadata with icons and descriptions
$categoryCards = [
    [
        'title' => 'Wi-Fi / Internet',
        'icon'  => 'bi-wifi',
        'desc'  => 'Campus Wi-Fi connectivity, lab network access, or slow bandwidth issues.',
        'color' => '#0284c7'
    ],
    [
        'title' => 'Electrical',
        'icon'  => 'bi-lightning-charge',
        'desc'  => 'Broken switches, power outlets, circuit breakers, and lighting failures.',
        'color' => '#eab308'
    ],
    [
        'title' => 'Classroom',
        'icon'  => 'bi-easel',
        'desc'  => 'Projector failures, whiteboard damage, podium issues, and sound systems.',
        'color' => '#8b5cf6'
    ],
    [
        'title' => 'Lab Equipment',
        'icon'  => 'bi-cpu',
        'desc'  => 'Defective PCs, instruments, monitors, keyboards, and specialized hardware.',
        'color' => '#06b6d4'
    ],
    [
        'title' => 'Cleanliness',
        'icon'  => 'bi-trash3',
        'desc'  => 'Trash overflow, dusty classrooms, corridors, and hygiene upkeep.',
        'color' => '#10b981'
    ],
    [
        'title' => 'Water Supply',
        'icon'  => 'bi-droplet',
        'desc'  => 'Drinking water filters, bathroom water shortages, and plumbing leaks.',
        'color' => '#0ea5e9'
    ],
    [
        'title' => 'Furniture',
        'icon'  => 'bi-inboxes',
        'desc'  => 'Damaged benches, broken chairs, tables, and teacher desk repairs.',
        'color' => '#f97316'
    ],
    [
        'title' => 'Washroom',
        'icon'  => 'bi-badge-wc',
        'desc'  => 'Sanitation issues, broken taps, flush repairs, and washroom maintenance.',
        'color' => '#14b8a6'
    ],
    [
        'title' => 'Security',
        'icon'  => 'bi-shield-exclamation',
        'desc'  => 'Campus security concerns, door lock issues, and suspicious activities.',
        'color' => '#ef4444'
    ],
    [
        'title' => 'Other',
        'icon'  => 'bi-grid-3x3-gap',
        'desc'  => 'Any other campus facility issues requiring administration attention.',
        'color' => '#64748b'
    ]
];
?>

<!-- Hero Banner Section -->
<section class="py-5 bg-white border-bottom shadow-sm">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-teal-accent text-teal-dark px-3 py-2 rounded-pill fw-semibold mb-3 border border-teal-subtle">
                    <i class="bi bi-mortarboard me-1"></i> University Campus Facility Management
                </span>
                <h1 class="display-4 fw-bold text-navy mb-3">
                    Report. Track. <span class="text-primary">Resolve.</span>
                </h1>
                <p class="lead text-muted mb-4">
                    CampusFix provides university students with a fast, transparent way to report campus facility problems—from classroom projectors and Wi-Fi outages to lab hardware and maintenance needs.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?= base_url('register.php'); ?>" class="btn btn-primary btn-lg px-4 shadow-sm">
                            <i class="bi bi-person-plus me-2"></i>Get Started (Register)
                        </a>
                        <a href="<?= base_url('login.php'); ?>" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Student / Admin Login
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url(isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'); ?>" class="btn btn-primary btn-lg px-4 shadow-sm">
                            <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
                        </a>
                        <?php if (isStudent()): ?>
                            <a href="<?= base_url('complaint_create.php'); ?>" class="btn btn-outline-primary btn-lg px-4">
                                <i class="bi bi-plus-circle me-2"></i>Submit New Complaint
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="p-4 bg-light rounded-4 border shadow-sm">
                    <h5 class="fw-bold mb-3 d-flex align-items-center text-teal-dark">
                        <i class="bi bi-lightning-charge text-warning me-2 fs-4"></i> How CampusFix Works
                    </h5>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex">
                            <span class="badge bg-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">1</span>
                            <div>
                                <h6 class="mb-1 fw-bold">Submit a Complaint</h6>
                                <p class="small text-muted mb-0">Describe the campus issue, choose a category, location, and priority.</p>
                            </div>
                        </li>
                        <li class="d-flex">
                            <span class="badge bg-info text-dark rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">2</span>
                            <div>
                                <h6 class="mb-1 fw-bold">Track In Real-Time</h6>
                                <p class="small text-muted mb-0">Monitor live status changes from Pending to In Progress as campus staff takes action.</p>
                            </div>
                        </li>
                        <li class="d-flex">
                            <span class="badge bg-success rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">3</span>
                            <div>
                                <h6 class="mb-1 fw-bold">Verified Resolution</h6>
                                <p class="small text-muted mb-0">Review the administrator's official resolution notes once the fix is verified.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Issue Categories Section -->
<section class="py-5">
    <div class="container py-2">
        <div class="text-center max-w-700 mx-auto mb-5">
            <h2 class="fw-bold mb-2">Campus Issue Categories</h2>
            <p class="text-muted">Students can file structured reports across all primary campus facilities and equipment.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($categoryCards as $cat): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="category-card shadow-sm h-100 d-flex flex-column">
                        <div class="category-icon-wrapper" style="background-color: <?= $cat['color']; ?>15; color: <?= $cat['color']; ?>;">
                            <i class="bi <?= $cat['icon']; ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-2"><?= e($cat['title']); ?></h6>
                        <p class="small text-muted mb-0 flex-grow-1"><?= e($cat['desc']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
