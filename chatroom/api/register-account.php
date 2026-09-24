<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    chat_require_post();
    chat_require_csrf();
    $data = chat_json_input();
    $nickname = is_string($data['nickname'] ?? null) ? $data['nickname'] : '';
    $password = is_string($data['password'] ?? null) ? $data['password'] : '';
    if (($data['terms'] ?? false) !== true) {
        chat_fail('Potwierdź brak możliwości odzyskania hasła.');
    }
    $session = chat_create_permanent_account($nickname, $password);
    $users = chat_fetch_active_users();
    chat_json(['ok' => true, 'session' => chat_session_payload($session), 'users' => $users, 'onlineCount' => count($users)], 201);
} catch (Throwable $exception) {
    if ($exception instanceof RuntimeException || $exception instanceof PDOException) {
        if (http_response_code() >= 400) throw $exception;
    }
    error_log('Chat account registration error: ' . $exception->getMessage());
    chat_fail('Nie udało się zarejestrować nicku. Spróbuj ponownie za chwilę.', 500);
}
