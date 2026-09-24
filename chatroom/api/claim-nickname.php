<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    chat_require_post();
    chat_require_csrf();
    $admin = chat_require_admin_session();
    $data = chat_json_input();
    $targetId = filter_var($data['targetId'] ?? null, FILTER_VALIDATE_INT);
    if (!is_int($targetId) || $targetId < 1) {
        chat_fail('Nieprawidłowy użytkownik do zastrzeżenia pseudonimu.');
    }

    $stmt = $pdo->prepare('SELECT id, nickname, nickname_key, token_hash FROM chat_sessions WHERE id = ? AND is_active = 1 AND token_hash IS NOT NULL LIMIT 1');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();
    if (!$target) {
        chat_fail('Użytkownik nie jest już aktywny.', 404);
    }

    if (($target['role'] ?? '') === 'admin') {
        chat_fail('Nie można zastrzegać pseudonimu administratora.');
    }

    $adminUserId = (int) ($_SESSION['user_id'] ?? 0);
    $claim = $pdo->prepare("INSERT INTO chat_nick_claims (nickname, nickname_key, token_hash, approved_by_admin_user_id)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE nickname = VALUES(nickname), token_hash = VALUES(token_hash), approved_by_admin_user_id = VALUES(approved_by_admin_user_id), updated_at = CURRENT_TIMESTAMP");
    $claim->execute([
        (string) $target['nickname'],
        (string) $target['nickname_key'],
        (string) $target['token_hash'],
        $adminUserId
    ]);

    chat_log_action((int) $admin['id'], $targetId, 'claim_nickname', (string) $target['nickname']);
    chat_json(['ok' => true, 'message' => 'Pseudonim został zastrzeżony dla bieżącej sesji użytkownika.']);
} catch (Throwable $exception) {
    error_log('Chat nickname claim error: ' . $exception->getMessage());
    chat_fail('Nie udało się zastrzec pseudonimu. Spróbuj ponownie.', 500);
}
