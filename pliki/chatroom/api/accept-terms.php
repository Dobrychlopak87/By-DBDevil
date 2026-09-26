<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    chat_require_post();
    chat_require_csrf();
    $session = chat_accept_terms_for_current_session();
    chat_json(['ok' => true, 'session' => chat_session_payload($session)]);
} catch (Throwable $exception) {
    error_log('Chat terms acceptance error: ' . $exception->getMessage());
    chat_fail('Nie udało się zapisać akceptacji regulaminu. Spróbuj ponownie.', 500);
}
