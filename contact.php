<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function contactResponse(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['ok' => $status >= 200 && $status < 300, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    contactResponse(405, 'Niedozwolona metoda żądania.');
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && rtrim($origin, '/') !== rtrim(SITE_URL, '/')) {
    contactResponse(403, 'Nieprawidłowe źródło żądania.');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['contact_csrf'] ?? '');
if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    contactResponse(403, 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    contactResponse(200, 'Dziękujemy za wiadomość.');
}

$lastSubmission = (int) ($_SESSION['contact_last_submission'] ?? 0);
if ($lastSubmission > 0 && (time() - $lastSubmission) < 60) {
    contactResponse(429, 'Odczekaj chwilę przed wysłaniem kolejnej wiadomości.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    contactResponse(422, 'Uzupełnij wszystkie pola formularza.');
}

if (mb_strlen($name, 'UTF-8') > 80 || mb_strlen($subject, 'UTF-8') > 140 || mb_strlen($message, 'UTF-8') > 3000) {
    contactResponse(422, 'Jedno z pól przekracza dozwoloną długość.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
    contactResponse(422, 'Podaj prawidłowy adres e-mail.');
}

$recipient = CONTACT_EMAIL;
$mailSubject = '[' . SITE_NAME . '] ' . preg_replace('/[\r\n]+/', ' ', $subject);
$mailBody = "Wiadomość z formularza kontaktowego " . SITE_NAME . "\n\n";
$mailBody .= "Nadawca: {$name}\n";
$mailBody .= "E-mail: {$email}\n";
$mailBody .= "Temat: {$subject}\n\n";
$mailBody .= "Treść:\n{$message}\n";

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . SITE_NAME . ' <' . CONTACT_EMAIL . '>',
    'Reply-To: ' . $email,
    'X-Mailer: ' . SITE_NAME
];

if (!mail($recipient, $mailSubject, $mailBody, implode("\r\n", $headers))) {
    contactResponse(500, 'Nie udało się wysłać wiadomości. Spróbuj ponownie później.');
}

$_SESSION['contact_last_submission'] = time();
contactResponse(200, 'Wiadomość została wysłana. Dziękujemy za kontakt.');
