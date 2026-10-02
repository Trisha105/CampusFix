<?php
/** Server-side image validation and Cloudinary authenticated-asset adapter. */

interface CampusMediaStore {
    /** @return array{public_id:string,format:string} */
    public function upload(string $path, string $folder): array;
    public function delete(string $publicId): void;
    public function download(string $publicId, string $format): string;
}

final class CloudinaryMediaStore implements CampusMediaStore {
    private \Cloudinary\Cloudinary $client;

    public function __construct() {
        $url = getenv('CLOUDINARY_URL') ?: '';
        if ($url === '') {
            throw new RuntimeException('Image storage is not configured.');
        }
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Cloudinary SDK is not installed.');
        }
        require_once $autoload;
        $this->client = new \Cloudinary\Cloudinary($url);
    }

    public function upload(string $path, string $folder): array {
        $response = $this->client->uploadApi()->upload($path, [
            'resource_type' => 'image',
            'type' => 'authenticated',
            'folder' => $folder,
            'overwrite' => false,
        ]);
        $publicId = (string)($response['public_id'] ?? '');
        $format = (string)($response['format'] ?? '');
        if ($publicId === '' || $format === '') {
            throw new RuntimeException('Image provider returned incomplete metadata.');
        }
        return ['public_id' => $publicId, 'format' => $format];
    }

    public function delete(string $publicId): void {
        $result = $this->client->uploadApi()->destroy($publicId, [
            'resource_type' => 'image', 'type' => 'authenticated', 'invalidate' => true,
        ]);
        if (!in_array((string)($result['result'] ?? ''), ['ok', 'not found'], true)) {
            throw new RuntimeException('Image provider did not confirm deletion.');
        }
    }

    public function download(string $publicId, string $format): string {
        $url = $this->client->uploadApi()->privateDownloadUrl($publicId, $format, [
            'resource_type' => 'image', 'type' => 'authenticated', 'expires_at' => time() + 60,
        ]);
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || !preg_match('/^api(?:-[a-z0-9]+)?\.cloudinary\.com$/', $host)) {
            throw new RuntimeException('Unexpected image provider host.');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Could not initialize image download.');
        }
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if (!is_string($body) || $status !== 200 || strlen($body) > 5 * 1024 * 1024 + 1024) {
            throw new RuntimeException('Image download failed.');
        }
        return $body;
    }
}

/** @return array{mime:string,bytes:int} */
function validateImageContent(string $path, int $bytes): array {
    if ($bytes < 1 || $bytes > 5 * 1024 * 1024 || !is_file($path)) {
        throw new InvalidArgumentException('Each image must be at most 5 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $dimensions = @getimagesize($path);
    if (!is_string($mime) || !isset($allowed[$mime]) || $dimensions === false ||
        ($dimensions['mime'] ?? '') !== $mime || $dimensions[0] < 1 || $dimensions[1] < 1 ||
        $dimensions[0] > 8000 || $dimensions[1] > 8000) {
        throw new InvalidArgumentException('Upload a valid JPEG, PNG or WebP image.');
    }
    return ['mime' => $mime, 'bytes' => $bytes];
}

/** @return list<array{name:string,tmp_name:string,size:int,error:int}> */
function submittedImages(array $files): array {
    $names = $files['name'] ?? [];
    if (!is_array($names)) {
        throw new InvalidArgumentException('Invalid image selection.');
    }
    $result = [];
    foreach ($names as $index => $name) {
        $error = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $result[] = [
            'name' => (string)$name,
            'tmp_name' => (string)($files['tmp_name'][$index] ?? ''),
            'size' => (int)($files['size'][$index] ?? 0),
            'error' => $error,
        ];
    }
    return $result;
}

function queueMediaCleanup(PDO $pdo, string $publicId, string $reason): void {
    try {
        $stmt = $pdo->prepare('INSERT INTO media_cleanup_jobs (public_id, last_error) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_error = VALUES(last_error)');
        $stmt->execute([$publicId, substr($reason, 0, 255)]);
    } catch (Throwable $error) {
        error_log('Could not queue image cleanup for ' . $publicId . ': ' . $error->getMessage());
    }
}

function deleteOrQueueMedia(CampusMediaStore $store, PDO $pdo, string $publicId): void {
    try {
        $store->delete($publicId);
    } catch (Throwable $error) {
        queueMediaCleanup($pdo, $publicId, $error->getMessage());
    }
}

function processMediaCleanup(PDO $pdo, CampusMediaStore $store, int $limit = 100): int {
    $limit = max(1, min($limit, 100));
    $jobs = $pdo->query('SELECT id, public_id FROM media_cleanup_jobs ORDER BY id LIMIT ' . $limit)->fetchAll();
    foreach ($jobs as $job) {
        try {
            $store->delete($job['public_id']);
            $pdo->prepare('DELETE FROM media_cleanup_jobs WHERE id = ?')->execute([$job['id']]);
        } catch (Throwable $error) {
            $pdo->prepare('UPDATE media_cleanup_jobs SET attempts = attempts + 1, last_error = ? WHERE id = ?')
                ->execute([substr($error->getMessage(), 0, 255), $job['id']]);
        }
    }
    return count($jobs);
}
