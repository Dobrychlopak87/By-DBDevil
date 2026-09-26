<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function pollVoteResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    pollVoteResponse(['ok' => false, 'error' => 'Dozwolone jest wyłącznie żądanie POST.'], 405);
}

$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== SITE_URL) {
    pollVoteResponse(['ok' => false, 'error' => 'Nieprawidłowe źródło żądania.'], 403);
}

$raw = file_get_contents('php://input');
$data = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($data)) {
    pollVoteResponse(['ok' => false, 'error' => 'Nieprawidłowe dane głosowania.'], 400);
}

try {
    $result = submitPollVote((int) ($data['poll_id'] ?? 0), (int) ($data['option_id'] ?? 0));
    pollVoteResponse($result);
} catch (InvalidArgumentException $exception) {
    pollVoteResponse(['ok' => false, 'error' => $exception->getMessage()], 422);
} catch (RuntimeException $exception) {
    pollVoteResponse(['ok' => false, 'error' => $exception->getMessage()], 409);
} catch (Throwable $exception) {
    error_log('66600 poll vote: ' . $exception->getMessage());
    pollVoteResponse(['ok' => false, 'error' => 'Nie udało się zapisać głosu. Spróbuj ponownie.'], 500);
}
