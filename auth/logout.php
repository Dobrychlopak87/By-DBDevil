<?php
require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/auth/my.php');
}

try {
    auth_verify_csrf($_POST['csrf_token'] ?? null);
} catch (Throwable $exception) {
    http_response_code(400);
    exit('Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
}

auth_logout();
redirect(SITE_URL . '/auth/login.php');