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

    document.querySelectorAll('[data-image-preview]').forEach(function (input) {
        const preview = document.getElementById(input.dataset.imagePreview);
        if (!preview) return;
        function renderSelection() {
            preview.replaceChildren();
            const files = Array.from(input.files || []);
            const max = Number(input.dataset.maxFiles || 3);
            if (files.length > max) {
                input.setCustomValidity('Select at most ' + max + ' image(s).');
            } else if (files.some(file => file.size > 5 * 1024 * 1024)) {
                input.setCustomValidity('Each image must be at most 5 MB.');
            } else {
                input.setCustomValidity('');
            }
            files.forEach(function (file, index) {
                const holder = document.createElement('div');
                holder.className = 'border rounded p-1 text-center';
                const img = document.createElement('img');
                const url = URL.createObjectURL(file);
                img.src = url;
                img.alt = 'Selected image preview';
                img.className = 'image-preview-thumb rounded d-block';
                img.onload = function () { URL.revokeObjectURL(url); };
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-sm btn-outline-danger mt-1';
                remove.textContent = 'Remove';
                remove.addEventListener('click', function () {
                    const next = new DataTransfer();
                    files.forEach(function (candidate, candidateIndex) {
                        if (candidateIndex !== index) next.items.add(candidate);
                    });
                    input.files = next.files;
                    renderSelection();
                });
                holder.append(img, remove);
                preview.append(holder);
            });
        }
        input.addEventListener('change', renderSelection);
    });
});
document.querySelectorAll('.reply-button').forEach((button) => {
  button.addEventListener('click', () => {
    document.getElementById('reply-parent-id').value = button.dataset.commentId;
    document.getElementById('comment-label').textContent = 'Reply to comment';
    document.getElementById('cancel-reply').classList.remove('d-none');
    document.getElementById('comment-body').focus();
  });
});
document.getElementById('cancel-reply')?.addEventListener('click', () => {
  document.getElementById('reply-parent-id').value = '';
  document.getElementById('comment-label').textContent = 'Add a comment';
  document.getElementById('cancel-reply').classList.add('d-none');
});
