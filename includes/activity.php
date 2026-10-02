<?php
/** Complaint events and private notification helpers; callers own the transaction. */

function complaintVisibleTo(PDO $pdo, int $complaintId, int $userId, bool $admin): ?array {
    $sql = 'SELECT id, user_id, complaint_code, status, priority FROM complaints WHERE id = ?';
    if (!$admin) $sql .= ' AND user_id = ?';
    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($admin ? [$complaintId] : [$complaintId, $userId]);
    return $stmt->fetch() ?: null;
}

function recordComplaintEvent(PDO $pdo, int $complaintId, int $actorId, string $type,
    ?string $oldStatus, ?string $newStatus, ?string $oldPriority, ?string $newPriority): void {
    $stmt = $pdo->prepare('INSERT INTO complaint_events
        (complaint_id, actor_id, event_type, old_status, new_status, old_priority, new_priority)
        VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$complaintId, $actorId, $type, $oldStatus, $newStatus, $oldPriority, $newPriority]);
}

function notifyUser(PDO $pdo, int $recipientId, int $actorId, int $complaintId, string $kind, string $message): void {
    if ($recipientId === $actorId) return;
    $stmt = $pdo->prepare('INSERT INTO notifications (recipient_id, actor_id, complaint_id, kind, message)
        VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$recipientId, $actorId, $complaintId, $kind, $message]);
}

function notifyAdmins(PDO $pdo, int $actorId, int $complaintId, string $kind, string $message): void {
    $stmt = $pdo->prepare("INSERT INTO notifications (recipient_id, actor_id, complaint_id, kind, message)
        SELECT id, ?, ?, ?, ? FROM users WHERE role = 'admin' AND id <> ?");
    $stmt->execute([$actorId, $complaintId, $kind, $message, $actorId]);
}
