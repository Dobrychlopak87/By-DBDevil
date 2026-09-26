<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $session = chat_require_session();
    chat_cleanup_expired_content();
    $sessionId = (int) $session['id'];
    $isAdmin = ($session['effective_role'] ?? '') === 'admin';

    if ($method === 'GET') {
        if ($isAdmin) {
            $adminUserId = (int) ($_SESSION['user_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT pm.*, sender.nickname AS sender_nickname, recipient.nickname AS recipient_nickname
                FROM chat_private_messages pm
                LEFT JOIN chat_sessions sender ON sender.id = pm.sender_session_id
                LEFT JOIN chat_sessions recipient ON recipient.id = pm.recipient_session_id
                WHERE pm.admin_user_id = ?
                ORDER BY pm.id DESC
                LIMIT 150");
            $stmt->execute([$adminUserId]);
        } else {
            $stmt = $pdo->prepare("SELECT pm.*, sender.nickname AS sender_nickname, recipient.nickname AS recipient_nickname
                FROM chat_private_messages pm
                LEFT JOIN chat_sessions sender ON sender.id = pm.sender_session_id
                LEFT JOIN chat_sessions recipient ON recipient.id = pm.recipient_session_id
                WHERE pm.sender_session_id = ? OR pm.recipient_session_id = ?
                ORDER BY pm.id DESC
                LIMIT 100");
            $stmt->execute([$sessionId, $sessionId]);
        }

        $rows = array_reverse($stmt->fetchAll());
        $messages = array_map(static fn(array $row): array => chat_private_message_payload($row, $sessionId), $rows);
        chat_json(['ok' => true, 'messages' => $messages]);
    }

    chat_require_post();
    chat_require_csrf();
    $data = chat_json_input();
    $message = chat_validate_message(is_string($data['message'] ?? null) ? $data['message'] : '');

    if ($isAdmin) {
        $targetId = filter_var($data['targetId'] ?? null, FILTER_VALIDATE_INT);
        if (!is_int($targetId) || $targetId < 1) {
            chat_fail('Wybierz aktywnego użytkownika, któremu chcesz odpowiedzieć.');
        }

        $targetStmt = $pdo->prepare('SELECT id FROM chat_sessions WHERE id = ? AND is_active = 1 LIMIT 1');
        $targetStmt->execute([$targetId]);
        if (!$targetStmt->fetch()) {
            chat_fail('Ten użytkownik nie jest już aktywny.', 404);
        }

        $adminUserId = (int) ($_SESSION['user_id'] ?? 0);
        $insert = $pdo->prepare('INSERT INTO chat_private_messages (sender_session_id, recipient_session_id, admin_user_id, body) VALUES (?, ?, ?, ?)');
        $insert->execute([$sessionId, $targetId, $adminUserId, $message]);
    } else {
        $adminUserId = chat_get_administrator_user_id();
        if ($adminUserId === null) {
            chat_fail('Administrator nie jest obecnie skonfigurowany dla czatu.', 503);
        }

        $adminSession = chat_find_active_admin();
        $recipientId = $adminSession === null ? null : (int) $adminSession['id'];
        $insert = $pdo->prepare('INSERT INTO chat_private_messages (sender_session_id, recipient_session_id, admin_user_id, body) VALUES (?, ?, ?, ?)');
        $insert->execute([$sessionId, $recipientId, $adminUserId, $message]);
    }

    chat_json(['ok' => true, 'message' => 'Wiadomość prywatna została wysłana.'], 201);
} catch (Throwable $exception) {
    error_log('Chat private message error: ' . $exception->getMessage());
    chat_fail('Nie udało się wysłać wiadomości prywatnej. Spróbuj ponownie.', 500);
}
