<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

chat_require_post();
$session = chat_require_session();
chat_require_csrf();
$input = chat_json_input();
$lastMessageId = max(0, (int) ($input['lastMessageId'] ?? 0));

chat_json([
    'ok' => true,
    'unreadCount' => chat_mark_public_messages_read($session, $lastMessageId)
]);
