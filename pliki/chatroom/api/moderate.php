<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    chat_require_post();
    chat_require_csrf();
    $actor = chat_require_session();
    if (!chat_can_moderate($actor)) {
        chat_fail('Nie masz uprawnień do moderacji.', 403);
    }

    $data = chat_json_input();
    $action = is_string($data['action'] ?? null) ? $data['action'] : '';
    $actorId = (int) $actor['id'];
    $isAdministrator = ($actor['effective_role'] ?? '') === 'admin';

    if ($action === 'unblock_user') {
        if (!$isAdministrator) {
            chat_fail('Tylko administrator może cofnąć blokadę nicku.', 403);
        }
        $blockId = filter_var($data['blockId'] ?? null, FILTER_VALIDATE_INT);
        if (!is_int($blockId) || $blockId < 1) {
            chat_fail('Nieprawidłowy identyfikator blokady.');
        }
        chat_ensure_nickname_block_schema();
        $stmt = $pdo->prepare('DELETE FROM chat_nickname_blocks WHERE id = ? AND blocked_until > UTC_TIMESTAMP()');
        $stmt->execute([$blockId]);
        if ($stmt->rowCount() !== 1) {
            chat_fail('Nie znaleziono aktywnej blokady.', 404);
        }
        chat_log_action($actorId, null, 'unblock_nickname', 'block_id=' . $blockId);
        chat_json(['ok' => true, 'message' => 'Cofnięto blokadę nicku.']);
    }

    $targetId = filter_var($data['targetId'] ?? null, FILTER_VALIDATE_INT);
    if (!is_int($targetId) || $targetId < 1) {
        chat_fail('Nieprawidłowy identyfikator użytkownika.');
    }

    if ($targetId === $actorId && in_array($action, ['assign_moderator', 'revoke_moderator', 'mute_user', 'remove_user', 'block_user'], true)) {
        chat_fail('Nie można wykonać tej akcji wobec własnej sesji.');
    }

    if (in_array($action, ['assign_moderator', 'revoke_moderator', 'remove_user', 'block_user'], true) && !$isAdministrator) {
        chat_fail('Tylko administrator może wykonać tę akcję.', 403);
    }

    if ($action === 'delete_message') {
        $messageId = filter_var($data['messageId'] ?? null, FILTER_VALIDATE_INT);
        if (!is_int($messageId) || $messageId < 1) {
            chat_fail('Nieprawidłowy identyfikator wiadomości.');
        }

        $imageStmt = $pdo->prepare('SELECT storage_name FROM chat_message_images WHERE message_id = ? AND deleted_at IS NULL LIMIT 1');
        $imageStmt->execute([$messageId]);
        $storageName = $imageStmt->fetchColumn();

        $stmt = $pdo->prepare('UPDATE chat_messages SET deleted_at = UTC_TIMESTAMP(), deleted_by_session_id = ? WHERE id = ? AND author_session_id = ? AND deleted_at IS NULL');
        $stmt->execute([$actorId, $messageId, $targetId]);
        if ($stmt->rowCount() !== 1) {
            chat_fail('Nie znaleziono aktywnej wiadomości.', 404);
        }
        $removeImage = $pdo->prepare('UPDATE chat_message_images SET deleted_at = UTC_TIMESTAMP(), storage_name = NULL WHERE message_id = ? AND deleted_at IS NULL');
        $removeImage->execute([$messageId]);
        chat_delete_stored_image(is_string($storageName) ? $storageName : null);
        chat_log_action($actorId, $targetId, 'delete_message', 'message_id=' . $messageId);
        chat_json(['ok' => true, 'message' => 'Wiadomość została usunięta.']);
    }

    $targetStmt = $pdo->prepare('SELECT id, nickname, nickname_key, role FROM chat_sessions WHERE id = ? AND is_active = 1 LIMIT 1');
    $targetStmt->execute([$targetId]);
    $target = $targetStmt->fetch();
    if (!$target) {
        chat_fail('Wybrany użytkownik nie jest już aktywny.', 404);
    }

    if (($target['role'] ?? '') === 'admin') {
        chat_fail('Rola administratora nie może zostać zmieniona z poziomu czatu.', 403);
    }

    if ($action === 'assign_moderator') {
        $minutes = filter_var($data['minutes'] ?? null, FILTER_VALIDATE_INT);
        $allowedDurations = [15, 60, 240];
        if (!is_int($minutes) || !in_array($minutes, $allowedDurations, true)) {
            chat_fail('Dozwolony czas moderatora to 15 minut, 1 godzina albo 4 godziny.');
        }

        $stmt = $pdo->prepare("UPDATE chat_sessions SET role = 'moderator', moderator_until = DATE_ADD(UTC_TIMESTAMP(), INTERVAL {$minutes} MINUTE) WHERE id = ?");
        $stmt->execute([$targetId]);
        chat_log_action($actorId, $targetId, 'assign_moderator', 'minutes=' . $minutes);
        chat_json(['ok' => true, 'message' => 'Nadano tymczasową rolę moderatora.']);
    }

    if ($action === 'revoke_moderator') {
        $stmt = $pdo->prepare("UPDATE chat_sessions SET role = 'guest', moderator_until = NULL WHERE id = ?");
        $stmt->execute([$targetId]);
        chat_log_action($actorId, $targetId, 'revoke_moderator');
        chat_json(['ok' => true, 'message' => 'Cofnięto rolę moderatora.']);
    }

    if ($action === 'mute_user') {
        $minutes = filter_var($data['minutes'] ?? null, FILTER_VALIDATE_INT);
        if (!is_int($minutes) || !in_array($minutes, [15, 60, 120], true)) {
            chat_fail('Wyciszenie może trwać 15 minut, 1 godzinę albo 2 godziny.');
        }

        $stmt = $pdo->prepare("UPDATE chat_sessions SET muted_until = DATE_ADD(UTC_TIMESTAMP(), INTERVAL {$minutes} MINUTE) WHERE id = ?");
        $stmt->execute([$targetId]);
        chat_log_action($actorId, $targetId, 'mute_user', 'minutes=' . $minutes);
        chat_json(['ok' => true, 'message' => 'Użytkownik został czasowo wyciszony.']);
    }

    if ($action === 'remove_user') {
        $stmt = $pdo->prepare("UPDATE chat_sessions
            SET is_active = 0,
                token_hash = NULL,
                nickname_key = CONCAT('_removed_', id),
                role = 'guest',
                moderator_until = NULL,
                muted_until = NULL
            WHERE id = ? AND is_active = 1");
        $stmt->execute([$targetId]);
        if ($stmt->rowCount() !== 1) {
            chat_fail('Wybrany użytkownik nie jest już aktywny.', 404);
        }
        chat_log_action($actorId, $targetId, 'remove_user', 'session_ended');
        chat_json(['ok' => true, 'message' => 'Użytkownik został usunięty z bieżącej sesji czatu.']);
    }

    if ($action === 'block_user') {
        chat_ensure_nickname_block_schema();
        $pdo->beginTransaction();
        try {
            $block = $pdo->prepare("INSERT INTO chat_nickname_blocks (nickname, nickname_key, blocked_until, blocked_by_session_id)
                VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1440 MINUTE), ?)
                ON DUPLICATE KEY UPDATE nickname = VALUES(nickname), blocked_until = VALUES(blocked_until), blocked_by_session_id = VALUES(blocked_by_session_id), updated_at = CURRENT_TIMESTAMP");
            $block->execute([(string) $target['nickname'], (string) $target['nickname_key'], $actorId]);
            $endSession = $pdo->prepare("UPDATE chat_sessions
                SET is_active = 0,
                    token_hash = NULL,
                    nickname_key = CONCAT('_blocked_', id),
                    role = 'guest',
                    moderator_until = NULL,
                    muted_until = NULL
                WHERE id = ? AND is_active = 1");
            $endSession->execute([$targetId]);
            if ($endSession->rowCount() !== 1) {
                throw new RuntimeException('Wybrany użytkownik nie jest już aktywny.');
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        chat_log_action($actorId, $targetId, 'block_nickname', 'minutes=1440');
        chat_json(['ok' => true, 'message' => 'Nick został zablokowany na 24 godziny, a bieżąca sesja zakończona.']);
    }

    chat_fail('Nieobsługiwana akcja.', 400);
} catch (Throwable $exception) {
    error_log('Chat moderation error: ' . $exception->getMessage());
    chat_fail('Nie udało się wykonać akcji moderacyjnej. Spróbuj ponownie.', 500);
}
