<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    chat_require_post();
    chat_require_csrf();
    $data = chat_json_input();
    $nickname = is_string($data['nickname'] ?? null) ? $data['nickname'] : '';
    $password = is_string($data['password'] ?? null) ? $data['password'] : '';
    $session = chat_login_permanent_account($nickname, $password);
    $users = chat_fetch_active_users();
    chat_json(['ok' => true, 'session' => chat_session_payload($session), 'users' => $users, 'onlineCount' => count($users)]);
} catch (Throwable $exception) {
    error_log('Chat account login error: ' . $exception->getMessage());
    chat_fail('Nie udało się zalogować. Spróbuj ponownie za chwilę.', 500);
}
