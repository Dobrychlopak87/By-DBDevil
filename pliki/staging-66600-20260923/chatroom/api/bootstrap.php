<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/room.php';

try {
    if (isAdminLoggedIn()) {
        $session = chat_ensure_admin_session();
    } else {
        $session = chat_get_current_session();
    }

    chat_json(chat_room_snapshot($session));
} catch (Throwable $exception) {
    error_log('Chat bootstrap error: ' . $exception->getMessage());
    chat_fail('Nie udało się przygotować czatu. Spróbuj ponownie za chwilę.', 500);
}
