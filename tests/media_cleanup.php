<?php
/** Run only against an isolated test database with DB_* environment variables. */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/media.php';

final class FakeMediaStore implements CampusMediaStore {
    public bool $failDelete = true;
    public function upload(string $path, string $folder): array { return ['public_id' => 'fake', 'format' => 'png']; }
    public function delete(string $publicId): void {
        if ($this->failDelete) throw new RuntimeException('simulated provider outage');
    }
    public function download(string $publicId, string $format): string { return ''; }
}

$pdo = getDBConnection();
$store = new FakeMediaStore();
$publicId = 'campusfix/tests/cleanup-' . bin2hex(random_bytes(8));
try {
    deleteOrQueueMedia($store, $pdo, $publicId);
    $stmt = $pdo->prepare('SELECT attempts FROM media_cleanup_jobs WHERE public_id = ?');
    $stmt->execute([$publicId]);
    if ($stmt->fetchColumn() === false) throw new RuntimeException('Failed deletion was not queued.');
    if (processMediaCleanup($pdo, $store) < 1) throw new RuntimeException('Cleanup job was not processed.');
    $stmt->execute([$publicId]);
    if ((int)$stmt->fetchColumn() !== 1) throw new RuntimeException('Failed retry was not recorded.');
    $store->failDelete = false;
    processMediaCleanup($pdo, $store);
    $stmt->execute([$publicId]);
    if ($stmt->fetchColumn() !== false) throw new RuntimeException('Successful retry did not remove job.');
    echo "Media cleanup: failed delete, queued retry and successful retry passed.\n";
} finally {
    $pdo->prepare('DELETE FROM media_cleanup_jobs WHERE public_id = ?')->execute([$publicId]);
}
