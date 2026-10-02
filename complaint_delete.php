<?php
/**
 * CampusFix - Student Complaint Deletion Endpoint (POST Only)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';

// Restrict access to authenticated students only
requireStudent();

$userId = currentUserId();
$pdo = getDBConnection();

// 1. Strict POST-Only Enforcement (Reject GET requests)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('danger', 'Method Not Allowed. Delete actions must be submitted via POST.');
    redirect('complaints.php');
}

// 2. CSRF Token Verification
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid or expired session security token. Deletion aborted.');
    redirect('complaints.php');
}

// 3. Validate Complaint ID
$complaintId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$complaintId) {
    set_flash('danger', 'Invalid complaint identifier.');
    redirect('complaints.php');
}

// 4. Ownership & Status Validation
// Only complaints in 'Pending' status belonging to the current student can be deleted.
$stmt = $pdo->prepare("
    SELECT id, complaint_code, status 
    FROM complaints 
    WHERE id = ? AND user_id = ? 
    LIMIT 1
");
$stmt->execute([$complaintId, $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('danger', 'You do not have permission to delete that complaint.');
    redirect('complaints.php');
}

if ($complaint['status'] !== 'Pending') {
    set_flash('warning', 'Only complaints with "Pending" status can be deleted. This complaint is currently ' . $complaint['status'] . '.');
    redirect('complaint_view.php?id=' . $complaintId);
}

// 5. Execute Safe Deletion
try {
    $pdo->beginTransaction();
    $imageStmt = $pdo->prepare('SELECT public_id FROM complaint_images WHERE complaint_id = ? FOR UPDATE');
    $imageStmt->execute([$complaintId]);
    $imageIds = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
    $deleteStmt = $pdo->prepare("
        DELETE FROM complaints 
        WHERE id = ? AND user_id = ? AND status = 'Pending'
    ");
    $deleteStmt->execute([$complaintId, $userId]);
    if ($deleteStmt->rowCount() !== 1) {
        throw new RuntimeException('Complaint was changed before deletion.');
    }
    $pdo->commit();
    foreach ($imageIds as $publicId) {
        try {
            (new CloudinaryMediaStore())->delete((string)$publicId);
        } catch (Throwable $imageError) {
            queueMediaCleanup($pdo, (string)$publicId, $imageError->getMessage());
        }
    }

    set_flash('success', 'Complaint ' . $complaint['complaint_code'] . ' deleted successfully.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Complaint Deletion Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while deleting the complaint.');
}

redirect('complaints.php');
