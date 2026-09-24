<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    chat_require_post();
    chat_require_csrf();
    $data = chat_json_input();
    $nickname = is_string($data['nickname'] ?? null) ? $data['nickname'] : '';

    $session = chat_create_guest_session($nickname);
    chat_json([
        'ok' => true,
        'session' => chat_session_payload($session),
        'users' => chat_fetch_active_users(),
        'onlineCount' => count(chat_fetch_active_users())
    ], 201);
} catch (Throwable $exception) {
    error_log('Chat join error: ' . $exception->getMessage());
    chat_fail('Nie udało się utworzyć sesji czatu. Spróbuj ponownie za chwilę.', 500);
}
