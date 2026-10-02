<?php
/** Shared evidence panel. Requires authenticated, authorized $complaint and $pdo. */
$imageStmt = $pdo->prepare('SELECT id, label, created_at FROM complaint_images WHERE complaint_id = ? ORDER BY id');
$imageStmt->execute([(int)$complaint['id']]);
$evidenceImages = $imageStmt->fetchAll();
$storageReady = (getenv('CLOUDINARY_URL') ?: '') !== '';
$canAddEvidence = $storageReady && count($evidenceImages) < 3 && (isAdmin() || $complaint['status'] === 'Pending');
$canRemoveEvidence = isAdmin() || $complaint['status'] === 'Pending';
?>
<section class="container pb-4" aria-labelledby="evidence-heading">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h2 id="evidence-heading" class="h5 fw-bold mb-0">Complaint images</h2>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small">Up to three protected images. Only the complaint owner and administrators can view them.</p>
            <?php if (!$storageReady): ?>
                <p class="text-muted small mb-3">Image storage is not configured yet.</p>
            <?php endif; ?>
            <?php if ($evidenceImages): ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($evidenceImages as $image): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="border rounded p-2 h-100">
                                <a href="<?= base_url('complaint_image.php?id=' . (int)$image['id']); ?>" target="_blank" rel="noopener" aria-label="Enlarge <?= e($image['label']); ?> image">
                                    <img src="<?= base_url('complaint_image.php?id=' . (int)$image['id']); ?>" alt="<?= e(ucfirst($image['label'])); ?> repair evidence" class="img-fluid rounded evidence-image">
                                </a>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-secondary text-uppercase"><?= e($image['label']); ?></span>
                                    <?php if ($canRemoveEvidence): ?>
                                        <form method="POST" action="<?= base_url('complaint_image_delete.php'); ?>" class="form-delete" data-confirm="Remove this image?">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="image_id" value="<?= (int)$image['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($canAddEvidence): ?>
                <form method="POST" action="<?= base_url('complaint_image_upload.php'); ?>" enctype="multipart/form-data" class="evidence-upload-form">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="complaint_id" value="<?= (int)$complaint['id']; ?>">
                    <label class="form-label fw-semibold" for="complaint-images">Add <?= isAdmin() ? 'after-repair' : 'before-repair'; ?> images</label>
                    <input class="form-control" id="complaint-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required data-image-preview="complaint-image-preview" data-max-files="<?= 3 - count($evidenceImages); ?>">
                    <div class="form-text">JPEG, PNG or WebP; 5 MB each; <?= 3 - count($evidenceImages); ?> slot(s) remaining.</div>
                    <div id="complaint-image-preview" class="d-flex flex-wrap gap-2 mt-2" aria-live="polite"></div>
                    <button type="submit" class="btn btn-primary mt-3">Upload images</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
