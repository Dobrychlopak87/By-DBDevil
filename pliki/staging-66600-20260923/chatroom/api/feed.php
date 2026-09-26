<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    $session = chat_require_session();
    $afterId = filter_input(INPUT_GET, 'after', FILTER_VALIDATE_INT);
    $afterId = is_int($afterId) && $afterId > 0 ? $afterId : 0;

    $cleanup = chat_cleanup_expired_content();
    $messages = chat_fetch_messages($afterId);
    $users = chat_fetch_active_users();
    $blocks = (($session['effective_role'] ?? '') === 'admin') ? chat_active_nickname_blocks() : [];

    chat_json([
        'ok' => true,
        'messages' => $messages,
        'purgedMessageIds' => $cleanup['messageIds'],
        'users' => $users,
        'blocks' => $blocks,
        'onlineCount' => count($users),
        'session' => chat_session_payload($session),
        'serverTime' => gmdate('c')
    ]);
} catch (Throwable $exception) {
    error_log('Chat feed error: ' . $exception->getMessage());
    chat_fail('Nie udało się odświeżyć czatu. Spróbuj ponownie.', 500);
}
