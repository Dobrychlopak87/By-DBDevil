<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/** @param array<string, mixed> $message */
function chat_message_payload(array $message): array
{
    $hasImage = ($message['image_id'] ?? null) !== null;
    $imageExpired = $hasImage && (int) ($message['image_expired'] ?? 0) === 1;
    return [
        'id' => (int) $message['id'],
        'authorId' => (int) $message['author_session_id'],
        'nickname' => (string) $message['nickname'],
        'role' => (string) $message['role'],
        'body' => (string) $message['body'],
        'createdAt' => (string) $message['created_at'],
        'deleted' => $message['deleted_at'] !== null,
        'imageUrl' => $hasImage && !$imageExpired ? 'api/image.php?id=' . (int) $message['image_id'] : null,
        'imageExpired' => $imageExpired,
        'isPermanent' => !empty($message['author_account_id'])
    ];
}

/** @return array<int, array<string, mixed>> */
function chat_fetch_messages(int $afterId = 0, int $limit = 120): array
{
    global $pdo;
    $limit = max(1, min($limit, 120));
    $fields = "m.id, m.author_session_id, m.body, m.created_at, m.deleted_at,
        s.nickname, s.account_id AS author_account_id, m.author_role AS role,
        i.id AS image_id,
        CASE WHEN i.id IS NOT NULL AND (i.deleted_at IS NOT NULL OR i.expires_at <= UTC_TIMESTAMP()) THEN 1 ELSE 0 END AS image_expired";

    if ($afterId > 0) {
        $stmt = $pdo->prepare("SELECT $fields
            FROM chat_messages m
            INNER JOIN chat_sessions s ON s.id = m.author_session_id
            LEFT JOIN chat_message_images i ON i.message_id = m.id
            WHERE m.id > ?
            ORDER BY m.id ASC
            LIMIT {$limit}");
        $stmt->execute([$afterId]);
        return array_map('chat_message_payload', $stmt->fetchAll());
    }

    $stmt = $pdo->query("SELECT * FROM (
            SELECT $fields
            FROM chat_messages m
            INNER JOIN chat_sessions s ON s.id = m.author_session_id
            LEFT JOIN chat_message_images i ON i.message_id = m.id
            ORDER BY m.id DESC
            LIMIT {$limit}
        ) latest
        ORDER BY id ASC");
    return array_map('chat_message_payload', $stmt->fetchAll());
}

/** @return array<int, array<string, mixed>> */
function chat_fetch_active_users(): array
{
    global $pdo;
    $presenceTtl = CHAT_PRESENCE_TTL_SECONDS;
    $retentionTtl = CHAT_NICK_RETENTION_TTL_SECONDS;
    $stmt = $pdo->query("SELECT id, nickname, account_id, last_seen,
            CASE
                WHEN role = 'admin' THEN 'admin'
                WHEN role = 'moderator' AND moderator_until > UTC_TIMESTAMP() THEN 'moderator'
                ELSE 'guest'
            END AS role,
            moderator_until,
            CASE WHEN last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL {$presenceTtl} SECOND) THEN 1 ELSE 0 END AS is_online
        FROM chat_sessions
        WHERE is_active = 1
          AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL {$retentionTtl} SECOND)
        ORDER BY is_online DESC, FIELD(role, 'admin', 'moderator', 'guest'), last_seen DESC, nickname ASC");

    return array_map(static fn(array $user): array => [
        'id' => (int) $user['id'],
        'nickname' => (string) $user['nickname'],
        'role' => (string) $user['role'],
        'moderatorUntil' => $user['moderator_until'],
        'lastSeen' => (string) $user['last_seen'],
        'isOnline' => (bool) $user['is_online'],
        'isPermanent' => !empty($user['account_id'])
    ], $stmt->fetchAll());
}

/** @param array<string, mixed>|null $session */
function chat_room_snapshot(?array $session): array
{
    if ($session === null) {
        return [
            'ok' => true,
            'session' => null,
            'needNickname' => true,
            'messages' => [],
            'users' => [],
            'onlineCount' => 0,
            'serverTime' => gmdate('c')
        ];
    }

    chat_cleanup_expired_content();
    $users = chat_fetch_active_users();
    $blocks = (($session['effective_role'] ?? '') === 'admin') ? chat_active_nickname_blocks() : [];
    return [
        'ok' => true,
        'session' => chat_session_payload($session),
        'needNickname' => false,
        'messages' => chat_fetch_messages(),
        'users' => $users,
        'blocks' => $blocks,
        'onlineCount' => count($users),
        'serverTime' => gmdate('c')
    ];
}

function chat_get_administrator_user_id(): ?int
{
    global $sitePdo;
    if (isAdminLoggedIn()) {
        return (int) ($_SESSION['user_id'] ?? 0) ?: null;
    }

    $stmt = $sitePdo->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id ASC LIMIT 1");
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

/** @param array<string, mixed> $message */
function chat_private_message_payload(array $message, int $currentSessionId): array
{
    $isOutgoing = (int) $message['sender_session_id'] === $currentSessionId;
    return [
        'id' => (int) $message['id'],
        'body' => (string) $message['body'],
        'createdAt' => (string) $message['created_at'],
        'outgoing' => $isOutgoing,
        'counterpartId' => $isOutgoing ? (int) ($message['recipient_session_id'] ?? 0) : (int) $message['sender_session_id'],
        'counterpartNickname' => $isOutgoing
            ? (string) ($message['recipient_nickname'] ?? 'Administrator')
            : (string) ($message['sender_nickname'] ?? 'Użytkownik')
    ];
}
