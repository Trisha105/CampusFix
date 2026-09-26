<?php
/**
 * CampusFix - Admin Complaint Update Endpoint (POST Only)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Restrict access strictly to administrators
requireAdmin();

$pdo = getDBConnection();

// 1. Strict POST-Only enforcement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('danger', 'Method Not Allowed. Update actions must be submitted via POST.');
    redirect('admin/complaints.php');
}

// 2. CSRF Token Verification
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid or expired session security token. Update aborted.');
    redirect('admin/complaints.php');
}

// 3. Validate Complaint ID
$complaintId = filter_input(INPUT_POST, 'complaint_id', FILTER_VALIDATE_INT);
if (!$complaintId) {
    set_flash('danger', 'Invalid complaint identifier.');
    redirect('admin/complaints.php');
}

// 4. Retrieve and Whitelist-Validate Priority & Status
$priority       = trim($_POST['priority'] ?? '');
$status         = trim($_POST['status'] ?? '');
$resolutionNote = trim($_POST['resolution_note'] ?? '');

// Validate Priority against allowed enum values
if (!in_array($priority, COMPLAINT_PRIORITIES, true)) {
    set_flash('danger', 'Invalid priority value submitted.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
}

// Validate Status against allowed enum values
if (!in_array($status, COMPLAINT_STATUSES, true)) {
    set_flash('danger', 'Invalid status value submitted.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
}

// 5. Enforce Resolution Note Requirement when status = 'Resolved'
if ($status === 'Resolved' && $resolutionNote === '') {
    set_flash('danger', 'A resolution note is required when marking a complaint as Resolved. Please describe the action taken.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
}

// 6. Verify the complaint exists before updating
$checkStmt = $pdo->prepare("SELECT id FROM complaints WHERE id = ? LIMIT 1");
$checkStmt->execute([$complaintId]);
if (!$checkStmt->fetch()) {
    set_flash('danger', 'The requested complaint does not exist.');
    redirect('admin/complaints.php');
}

// 7. Execute Update
try {
    $updateStmt = $pdo->prepare("
        UPDATE complaints 
        SET priority = ?, status = ?, resolution_note = ?
        WHERE id = ?
    ");
    $updateStmt->execute([
        $priority,
        $status,
        ($resolutionNote !== '') ? $resolutionNote : null,
        $complaintId
    ]);

    set_flash('success', 'Complaint status updated.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
} catch (Exception $e) {
    error_log('Admin Complaint Update Error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while updating. Please try again.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
}
