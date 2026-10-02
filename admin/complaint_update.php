<?php
/**
 * CampusFix - Admin Complaint Update Endpoint (POST Only)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity.php';

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
if (mb_strlen($resolutionNote) > 2000) {
    set_flash('danger', 'Resolution note must be at most 2000 characters.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
}

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

// Update, audit record and notification commit together.
try {
    $pdo->beginTransaction();
    $checkStmt = $pdo->prepare('SELECT id, user_id, complaint_code, status, priority, resolution_note FROM complaints WHERE id = ? FOR UPDATE');
    $checkStmt->execute([$complaintId]);
    $before = $checkStmt->fetch();
    if (!$before) throw new RuntimeException('Complaint no longer exists.');
    $note = $resolutionNote !== '' ? $resolutionNote : null;
    $changed = $before['status'] !== $status || $before['priority'] !== $priority || $before['resolution_note'] !== $note;
    if ($changed) {
    $updateStmt = $pdo->prepare("
        UPDATE complaints 
        SET priority = ?, status = ?, resolution_note = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$priority, $status, $note, $complaintId]);
    recordComplaintEvent($pdo, $complaintId, (int)currentUserId(), 'updated',
        $before['status'], $status, $before['priority'], $priority);
    notifyUser($pdo, (int)$before['user_id'], (int)currentUserId(), $complaintId,
        'complaint_updated', 'Your complaint ' . $before['complaint_code'] . ' was updated.');
    }
    $pdo->commit();

    set_flash('success', $changed ? 'Complaint updated and owner notified.' : 'No changes to save.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Admin Complaint Update Error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while updating. Please try again.');
    redirect('admin/complaint_view.php?id=' . $complaintId);
}
