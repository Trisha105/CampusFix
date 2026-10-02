<?php
require_once __DIR__ . '/../includes/media.php';

function expectFailure(callable $action): void {
    try {
        $action();
    } catch (InvalidArgumentException $error) {
        return;
    }
    throw new RuntimeException('Expected image validation to reject the input.');
}

$validPath = tempnam(sys_get_temp_dir(), 'campusfix-image-');
$invalidPath = tempnam(sys_get_temp_dir(), 'campusfix-text-');
try {
    // A real 1x1 PNG fixture, not merely a filename with a .png extension.
    file_put_contents($validPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6Jm8AAAAASUVORK5CYII='));
    file_put_contents($invalidPath, 'not an image');
    $valid = validateImageContent($validPath, filesize($validPath));
    if ($valid['mime'] !== 'image/png') {
        throw new RuntimeException('Valid PNG MIME was not recognized.');
    }
    expectFailure(fn () => validateImageContent($invalidPath, filesize($invalidPath)));
    expectFailure(fn () => validateImageContent($validPath, 5 * 1024 * 1024 + 1));
    echo "Media validation: valid, invalid-content and oversize cases passed.\n";
} finally {
    unlink($validPath);
    unlink($invalidPath);
}
