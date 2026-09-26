/**
 * CampusFix - Vanilla JavaScript Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Universal Delete / Dangerous Action Confirmation
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!window.confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Handle form submissions with .form-delete class
    document.querySelectorAll('.form-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const message = form.getAttribute('data-confirm') || 'Are you sure you want to delete this complaint? This action cannot be undone.';
            if (!window.confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // 2. Admin Status Change Interaction: Dynamic Resolution Note Highlighting
    const statusSelect = document.getElementById('admin_status_select');
    const resolutionTextarea = document.getElementById('resolution_note_input');
    const resolutionRequiredIndicator = document.getElementById('resolution_required_indicator');

    if (statusSelect && resolutionTextarea) {
        function updateResolutionState() {
            const isResolved = (statusSelect.value === 'Resolved');
            if (isResolved) {
                resolutionTextarea.setAttribute('required', 'required');
                if (resolutionRequiredIndicator) {
                    resolutionRequiredIndicator.classList.remove('d-none');
                }
                resolutionTextarea.classList.add('border-primary');
            } else {
                resolutionTextarea.removeAttribute('required');
                if (resolutionRequiredIndicator) {
                    resolutionRequiredIndicator.classList.add('d-none');
                }
                resolutionTextarea.classList.remove('border-primary');
            }
        }

        statusSelect.addEventListener('change', updateResolutionState);
        // Run once on load to set proper initial state
        updateResolutionState();
    }

    // 3. Auto-dismiss temporary alerts after 6 seconds
    setTimeout(function () {
        document.querySelectorAll('.alert.alert-dismissible').forEach(function (alertElement) {
            // Use Bootstrap Alert instance to close smoothly if available
            if (window.bootstrap && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                bsAlert.close();
            }
        });
    }, 6000);
});
