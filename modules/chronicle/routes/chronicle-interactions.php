<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function chronicleInteractionResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    chronicleInteractionResponse(['ok' => false, 'error' => 'Dozwolone jest wyłącznie żądanie POST.'], 405);
}

$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== SITE_URL) {
    chronicleInteractionResponse(['ok' => false, 'error' => 'Nieprawidłowe źródło żądania.'], 403);
}

$raw = file_get_contents('php://input');
$data = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($data)) {
    chronicleInteractionResponse(['ok' => false, 'error' => 'Nieprawidłowe dane żądania.'], 400);
}

try {
    $action = (string) ($data['action'] ?? '');
    if ($action === 'vote') {
        chronicleInteractionResponse(submitChroniclePostVote((int) ($data['post_id'] ?? 0), (int) ($data['vote'] ?? 0)));
    }
    if ($action === 'comment') {
        chronicleInteractionResponse(submitChronicleComment(
            (int) ($data['post_id'] ?? 0),
            $data['author_name'] ?? '',
            $data['content'] ?? ''
        ));
    }
    if ($action === 'comment_like') {
        chronicleInteractionResponse(submitChronicleCommentLike((int) ($data['comment_id'] ?? 0)));
    }

    chronicleInteractionResponse(['ok' => false, 'error' => 'Nieznana akcja Kroniki.'], 400);
} catch (InvalidArgumentException $exception) {
    chronicleInteractionResponse(['ok' => false, 'error' => $exception->getMessage()], 422);
} catch (RuntimeException $exception) {
    chronicleInteractionResponse(['ok' => false, 'error' => $exception->getMessage()], 409);
} catch (Throwable $exception) {
    error_log('66600 chronicle interaction: ' . $exception->getMessage());
    chronicleInteractionResponse(['ok' => false, 'error' => 'Nie udało się wykonać tej akcji. Spróbuj ponownie.'], 500);
}
