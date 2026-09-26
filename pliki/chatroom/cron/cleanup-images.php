<?php
declare(strict_types=1);

// Wywoływany wyłącznie z harmonogramu hostingu, najlepiej co 5 minut.
require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $result = chat_cleanup_expired_content();
    if (PHP_SAPI === 'cli') {
        fwrite(
            STDOUT,
            sprintf(
                "Usunięto: %d wiadomości publicznych, %d prywatnych, %d obrazów; zwolniono %d nicków.%s",
                count($result['messageIds']),
                $result['privateMessages'],
                $result['images'],
                $result['nicknames'],
                PHP_EOL
            )
        );
    }
} catch (Throwable $exception) {
    error_log('Chat cleanup error: ' . $exception->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Czyszczenie Chat roomu nie powiodło się.' . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
}
