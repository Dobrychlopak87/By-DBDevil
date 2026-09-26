<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

$storedImage = null;
try {
    chat_require_post();
    chat_require_csrf();
    $session = chat_require_session();
    $data = chat_json_input();
    $upload = isset($_FILES['image']) && is_array($_FILES['image']) ? $_FILES['image'] : null;
    $hasImage = $upload !== null && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $message = chat_validate_message(is_string($data['message'] ?? null) ? $data['message'] : '', $hasImage);

    $pdo->beginTransaction();
    chat_require_message_cooldown($session);
    $storedImage = chat_store_uploaded_image($upload);

    $insert = $pdo->prepare('INSERT INTO chat_messages (author_session_id, author_role, body) VALUES (?, ?, ?)');
    $insert->execute([(int) $session['id'], (string) ($session['effective_role'] ?? 'guest'), $message]);
    $messageId = (int) $pdo->lastInsertId();

    if ($storedImage !== null) {
        $insertImage = $pdo->prepare("INSERT INTO chat_message_images
            (message_id, storage_name, mime_type, file_size, expires_at)
            VALUES (?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL " . CHAT_IMAGE_TTL_HOURS . " HOUR))");
        $insertImage->execute([$messageId, $storedImage['storageName'], $storedImage['mimeType'], $storedImage['fileSize']]);
    }

    $touch = $pdo->prepare('UPDATE chat_sessions SET last_message_at = UTC_TIMESTAMP(), last_seen = UTC_TIMESTAMP() WHERE id = ?');
    $touch->execute([(int) $session['id']]);
    $pdo->commit();

    $stmt = $pdo->prepare("SELECT m.id, m.author_session_id, m.body, m.created_at, m.deleted_at,
            s.nickname, s.account_id AS author_account_id, m.author_role AS role, i.id AS image_id,
            CASE WHEN i.id IS NOT NULL AND (i.deleted_at IS NOT NULL OR i.expires_at <= UTC_TIMESTAMP()) THEN 1 ELSE 0 END AS image_expired
        FROM chat_messages m
        INNER JOIN chat_sessions s ON s.id = m.author_session_id
        LEFT JOIN chat_message_images i ON i.message_id = m.id
        WHERE m.id = ? LIMIT 1");
    $stmt->execute([$messageId]);
    $created = $stmt->fetch();
    chat_json(['ok' => true, 'message' => chat_message_payload($created)], 201);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (is_array($storedImage)) {
        chat_delete_stored_image($storedImage['storageName'] ?? null);
    }
    error_log('Chat public message error: ' . $exception->getMessage());
    chat_fail('Nie udało się wysłać wiadomości. Spróbuj ponownie.', 500);
}
