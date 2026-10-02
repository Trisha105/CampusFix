<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT n.id, n.kind, n.message, n.read_at, n.created_at, n.complaint_id,
    c.user_id AS complaint_owner FROM notifications n
    LEFT JOIN complaints c ON c.id = n.complaint_id
    WHERE n.recipient_id = ? ORDER BY n.id DESC LIMIT 100');
$stmt->execute([(int)currentUserId()]);
$notifications = $stmt->fetchAll();
$pageTitle = 'Notifications - CampusFix';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:820px">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 fw-bold mb-0">Notifications</h1>
    <form method="post" action="<?= base_url('notification_read.php'); ?>"><?= csrf_field(); ?><input type="hidden" name="all" value="1"><button class="btn btn-outline-secondary btn-sm" type="submit">Mark all as read</button></form></div>
  <?php if (!$notifications): ?><div class="alert alert-light border">You have no notifications yet.</div><?php endif; ?>
  <div class="list-group">
    <?php foreach ($notifications as $notice): ?>
      <div class="list-group-item <?= $notice['read_at'] ? '' : 'bg-info bg-opacity-10'; ?>">
        <div class="d-flex justify-content-between gap-3"><div><p class="mb-1"><?= e($notice['message']); ?></p><small class="text-muted"><?= format_date($notice['created_at']); ?></small>
        <?php if ($notice['complaint_id'] && (isAdmin() || (int)$notice['complaint_owner'] === (int)currentUserId())): ?>
          <a class="ms-2" href="<?= base_url((isAdmin() ? 'admin/' : '') . 'complaint_view.php?id=' . (int)$notice['complaint_id']); ?>">View complaint</a>
        <?php endif; ?></div>
        <?php if (!$notice['read_at']): ?><form method="post" action="<?= base_url('notification_read.php'); ?>"><?= csrf_field(); ?><input type="hidden" name="id" value="<?= (int)$notice['id']; ?>"><button class="btn btn-sm btn-outline-primary" type="submit">Mark read</button></form><?php endif; ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
