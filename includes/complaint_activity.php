<?php
/** Included after an authorized complaint has been loaded by a detail route. */
$eventStmt = $pdo->prepare('SELECT e.*, u.full_name AS actor_name FROM complaint_events e LEFT JOIN users u ON u.id = e.actor_id WHERE e.complaint_id = ? ORDER BY e.id ASC');
$eventStmt->execute([(int)$complaint['id']]);
$events = $eventStmt->fetchAll();
$commentStmt = $pdo->prepare('SELECT c.*, u.full_name AS author_name, u.role AS author_role FROM complaint_comments c LEFT JOIN users u ON u.id = c.author_id WHERE c.complaint_id = ? ORDER BY c.id ASC');
$commentStmt->execute([(int)$complaint['id']]);
$comments = $commentStmt->fetchAll();
?>
<div class="container pb-4" id="activity">
  <div class="row g-4">
    <section class="col-lg-5" aria-labelledby="timeline-heading">
      <div class="card shadow-sm border-0 h-100"><div class="card-body p-4">
        <h5 id="timeline-heading" class="fw-bold"><i class="bi bi-clock-history me-2"></i>Complaint timeline</h5>
        <?php if (!$events): ?><p class="text-muted mb-0">No recorded changes yet. Older complaints begin recording events after this upgrade.</p><?php endif; ?>
        <ol class="list-group list-group-flush">
          <?php foreach ($events as $event): ?>
          <li class="list-group-item px-0">
            <div class="fw-semibold"><?= e($event['event_type'] === 'created' ? 'Complaint submitted' : 'Complaint updated'); ?></div>
            <div class="small text-muted"><?= e($event['actor_name'] ?? 'Deleted account'); ?> · <?= format_date($event['created_at']); ?></div>
            <?php if ($event['old_status'] !== $event['new_status']): ?><div class="small">Status: <?= e($event['old_status'] ?? '—'); ?> → <?= e($event['new_status'] ?? '—'); ?></div><?php endif; ?>
            <?php if ($event['old_priority'] !== $event['new_priority']): ?><div class="small">Priority: <?= e($event['old_priority'] ?? '—'); ?> → <?= e($event['new_priority'] ?? '—'); ?></div><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </div></div>
    </section>
    <section class="col-lg-7" aria-labelledby="comments-heading">
      <div class="card shadow-sm border-0 h-100"><div class="card-body p-4">
        <h5 id="comments-heading" class="fw-bold"><i class="bi bi-chat-dots me-2"></i>Conversation</h5>
        <?php if (!$comments): ?><p class="text-muted">No comments yet.</p><?php endif; ?>
        <?php foreach ($comments as $comment): ?>
          <article class="border rounded p-3 mb-2 <?= $comment['parent_id'] ? 'ms-4' : ''; ?>">
            <div class="small fw-semibold"><?= e($comment['author_name'] ?? 'Deleted account'); ?> <span class="text-muted fw-normal"><?= e($comment['author_role'] ?? ''); ?> · <?= format_date($comment['created_at']); ?></span></div>
            <div class="mt-1" style="white-space:pre-wrap"><?= e($comment['body']); ?></div>
            <?php if (!$comment['parent_id']): ?><button type="button" class="btn btn-link btn-sm p-0 mt-1 reply-button" data-comment-id="<?= (int)$comment['id']; ?>">Reply</button><?php endif; ?>
          </article>
        <?php endforeach; ?>
        <form method="post" action="<?= base_url('complaint_comment.php'); ?>" class="mt-3">
          <?= csrf_field(); ?>
          <input type="hidden" name="complaint_id" value="<?= (int)$complaint['id']; ?>">
          <input type="hidden" name="parent_id" id="reply-parent-id" value="">
          <label for="comment-body" id="comment-label" class="form-label fw-semibold">Add a comment</label>
          <textarea id="comment-body" name="body" class="form-control" rows="3" minlength="2" maxlength="2000" required></textarea>
          <div class="d-flex gap-2 mt-2"><button type="submit" class="btn btn-primary">Post comment</button><button type="button" id="cancel-reply" class="btn btn-outline-secondary d-none">Cancel reply</button></div>
        </form>
      </div></div>
    </section>
  </div>
</div>
