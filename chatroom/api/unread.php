<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$session = chat_get_current_session(false);
$unreadCount = 0;
if ($session !== null && !empty($session['terms_accepted_at'])) {
    $unreadCount = chat_get_public_unread_count($session);
}

chat_json([
    'ok' => true,
    'unreadCount' => $unreadCount
]);
