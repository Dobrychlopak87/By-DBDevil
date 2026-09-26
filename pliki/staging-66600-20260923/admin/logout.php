<?php
// Wylogowanie z panelu administracyjnego
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Zakończ sesję
adminLogout();

// Przekieruj do strony logowania
redirect(SITE_URL . '/admin/login.php');
