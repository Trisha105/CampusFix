</main>

<!-- Application Footer -->
<footer class="campus-footer mt-auto py-4 bg-white border-top">
    <div class="container">
        <div class="row align-items-center gy-2">
            <div class="col-md-6 text-center text-md-start">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                    <span class="fw-bold text-teal-dark"><i class="bi bi-shield-check text-primary me-2"></i>CampusFix</span>
                    <span class="text-muted ms-2 ps-2 border-start border-2 small">Campus Complaint Tracking System</span>
                </div>
                <p class="text-muted small mb-0 mt-1">Simple, accountable, and transparent problem resolution for university campuses.</p>
            </div>
            <div class="col-md-6 text-center text-md-end text-muted small">
                <span>&copy; <?= date('Y'); ?> CampusFix. Built with PHP 8, PDO & Bootstrap 5.</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom CampusFix Interaction Scripts -->
<script src="<?= base_url('assets/js/app.js'); ?>"></script>
</body>
</html>
