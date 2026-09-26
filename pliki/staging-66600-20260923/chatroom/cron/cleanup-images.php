<?php
declare(strict_types=1);

// Zadanie okresowe czyszczenia chatroomu (retencja 24 h).
// Podstawowe uruchomienie: cron hostingu przez CLI, zgodnie z docs/cron.md.
// Wariant HTTP istnieje wyłącznie jako fallback dla hostingu bez crona
// i wymaga tokenu cron_cleanup_token w prywatnej konfiguracji; bez tokenu
// każde żądanie HTTP kończy się odpowiedzią 404 (fail-closed).
require_once __DIR__ . '/../includes/bootstrap.php';

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    $cronToken = null;
    try {
        $privateConfig = Config::load();
        $cronToken = $privateConfig['cron_cleanup_token'] ?? null;
    } catch (Throwable $exception) {
        $cronToken = null;
    }
    $providedToken = $_GET['token'] ?? '';
    if (!is_string($cronToken) || $cronToken === ''
        || !is_string($providedToken) || $providedToken === ''
        || !hash_equals($cronToken, $providedToken)) {
        http_response_code(404);
        exit;
    }
    // Ograniczenie częstotliwości: nie częściej niż raz na 60 sekund.
    $stateFile = __DIR__ . '/../includes/.cleanup-last-run';
    $lastRun = (int) @file_get_contents($stateFile);
    if ($lastRun > 0 && (time() - $lastRun) < 60) {
        http_response_code(429);
        exit;
    }
    @file_put_contents($stateFile, (string) time(), LOCK_EX);
}

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
