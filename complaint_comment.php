<?php
/** Post a comment or one-level reply to an authorized complaint. */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/activity.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { http_response_code(403); exit; }
$complaintId = filter_input(INPUT_POST, 'complaint_id', FILTER_VALIDATE_INT);
$parentRaw = trim((string)($_POST['parent_id'] ?? ''));
$parentId = $parentRaw === '' ? null : filter_var($parentRaw, FILTER_VALIDATE_INT);
$body = trim((string)($_POST['body'] ?? ''));
$return = isAdmin() ? 'admin/complaint_view.php?id=' : 'complaint_view.php?id=';
if (!$complaintId || ($parentRaw !== '' && (!$parentId || $parentId < 1)) || mb_strlen($body) < 2 || mb_strlen($body) > 2000) {
    set_flash('danger', 'Comment must be between 2 and 2000 characters.');
    redirect($return . (int)$complaintId);
}
$pdo = getDBConnection();
$actor = (int)currentUserId();
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, user_id, complaint_code FROM complaints WHERE id = ? FOR UPDATE');
    $stmt->execute([$complaintId]);
    $complaint = $stmt->fetch();
    if (!$complaint || (!isAdmin() && (int)$complaint['user_id'] !== $actor)) {
        throw new DomainException('Complaint unavailable.');
    }
    if ($parentId !== null) {
        $parentStmt = $pdo->prepare('SELECT parent_id FROM complaint_comments WHERE id = ? AND complaint_id = ?');
        $parentStmt->execute([$parentId, $complaintId]);
        $parent = $parentStmt->fetch();
        if (!$parent || $parent['parent_id'] !== null) throw new DomainException('Invalid reply target.');
    }
    $insert = $pdo->prepare('INSERT INTO complaint_comments (complaint_id, author_id, parent_id, body) VALUES (?, ?, ?, ?)');
    $insert->execute([$complaintId, $actor, $parentId, $body]);
    if (isAdmin()) {
        notifyUser($pdo, (int)$complaint['user_id'], $actor, $complaintId, 'comment',
            'New staff comment on ' . $complaint['complaint_code'] . '.');
    } else {
        notifyAdmins($pdo, $actor, $complaintId, 'comment',
            'New student comment on ' . $complaint['complaint_code'] . '.');
    }
    $pdo->commit();
    set_flash('success', 'Comment added.');
} catch (DomainException $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    set_flash('danger', $error->getMessage());
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Complaint comment error: ' . $error->getMessage());
    set_flash('danger', 'Could not save the comment.');
}
redirect($return . $complaintId);
