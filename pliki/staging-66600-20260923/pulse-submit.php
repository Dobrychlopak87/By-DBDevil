<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pulse.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!pulseEnsureSchema()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Usługa jest chwilowo niedostępna. Spróbuj ponownie później.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!pulseVerifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Nie udało się wysłać formularza.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = pulseNormalizeText((string) ($_POST['message'] ?? ''));
$signature = pulseNormalizeText((string) ($_POST['signature'] ?? ''));
$phone = pulseNormalizeText((string) ($_POST['phone'] ?? ''));

$error = pulseValidateMessage($message);
if ($error === null) {
    $error = pulseValidateSignature($signature);
}
if ($error === null) {
    $error = pulseValidatePhone($phone);
}
$official = false;
if ($error === null && isReservedOfficialLabel($signature)) {
    if (!isOfficialAdminSession()) {
        $error = 'Ten podpis jest zastrzeżony dla administracji.';
    } else {
        $official = true;
    }
}
if ($error !== null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = pulsePublishNotice($message, $signature, $phone, pulseClientHash(), $official);
    if (!$result['ok']) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'Nie udało się opublikować komunikatu.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true, 'id' => $result['id'], 'message' => 'Komunikat został opublikowany.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Nie udało się opublikować komunikatu. Spróbuj ponownie.'], JSON_UNESCAPED_UNICODE);
}
