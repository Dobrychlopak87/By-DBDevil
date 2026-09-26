<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    chat_require_session();
    chat_cleanup_expired_images();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        http_response_code(404);
        exit;
    }

    $stmt = $pdo->prepare("SELECT storage_name, mime_type
        FROM chat_message_images
        WHERE id = ? AND deleted_at IS NULL AND expires_at > UTC_TIMESTAMP()
        LIMIT 1");
    $stmt->execute([$id]);
    $image = $stmt->fetch();
    if (!$image || !is_string($image['storage_name'])) {
        http_response_code(410);
        exit;
    }

    $path = chat_image_directory() . '/' . $image['storage_name'];
    if (!is_file($path)) {
        $update = $pdo->prepare('UPDATE chat_message_images SET deleted_at = UTC_TIMESTAMP(), storage_name = NULL WHERE id = ?');
        $update->execute([$id]);
        http_response_code(410);
        exit;
    }

    header('Content-Type: ' . $image['mime_type']);
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
} catch (Throwable $exception) {
    error_log('Chat image delivery error: ' . $exception->getMessage());
    http_response_code(404);
}
