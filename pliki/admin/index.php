<?php
// Jedyna obsługa wejścia na /admin/: żadnego listowania plików ani treści panelu bez sesji.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

redirect(SITE_URL . '/admin/login.php');
