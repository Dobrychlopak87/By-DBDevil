<?php
require_once __DIR__ . '/lib.php';

if (auth_is_logged_in()) {
    redirect(SITE_URL . '/auth/my.php');
}
redirect(SITE_URL . '/auth/login.php');