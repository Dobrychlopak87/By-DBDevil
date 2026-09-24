<?php
declare(strict_types=1);

/**
 * Wspólny bootstrap odseparowanego modułu czatu.
 * Moduł używa wydzielonego PDO dla danych czatu. Połączenie strony zachowuje
 * wyłącznie do odczytu istniejącej sesji administratora.
 */
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    throw new RuntimeException('Brak poprawnego połączenia z bazą strony.');
}
$sitePdo = $pdo;
/** @var array<string, mixed>|null $appPrivateConfig */
$chatDatabase = $appPrivateConfig['chat_database'] ?? null;if (!is_array($chatDatabase) || !isset($chatDatabase['dsn'], $chatDatabase['username'], $chatDatabase['password'])) {
    throw new RuntimeException('Konfiguracja wydzielonej bazy Chat roomu jest nieprawidłowa.');
}
$pdo = new PDO((string) $chatDatabase['dsn'], (string) $chatDatabase['username'], (string) $chatDatabase['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
]);

const CHAT_SESSION_COOKIE = 'chat_room_session';
const CHAT_PRESENCE_TTL_SECONDS = 900;
const CHAT_NICK_RETENTION_TTL_SECONDS = 86400;
const CHAT_MESSAGE_RETENTION_TTL_SECONDS = 86400;
const CHAT_COOKIE_TTL_SECONDS = 2592000;
const CHAT_MAX_ACTIVE_USERS = 50;
const CHAT_MESSAGE_MAX_LENGTH = 500;
const CHAT_NICK_MIN_LENGTH = 3;
const CHAT_NICK_MAX_LENGTH = 32;
const CHAT_POST_COOLDOWN_SECONDS = 2;
const CHAT_IMAGE_MAX_BYTES = 8388608;
const CHAT_IMAGE_MAX_DIMENSION = 4096;
const CHAT_IMAGE_TTL_HOURS = 24;
const CHAT_TERMS_VERSION = '2026-08-16';
const CHAT_PASSWORD_MIN_LENGTH = 8;
const CHAT_LOGIN_ATTEMPT_LIMIT = 5;
const CHAT_LOGIN_ATTEMPT_WINDOW_SECONDS = 900;
const CHAT_LOGIN_LOCKOUT_SECONDS = 900;

function chat_login_attempt_file(string $nicknameKey): ?string
{
    $address = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($address === '') {
        return null;
    }

    $directory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '66600-chat-login-attempts';
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        return null;
    }

    return $directory . DIRECTORY_SEPARATOR . hash('sha256', $address . "\0" . $nicknameKey) . '.json';
}

function chat_login_attempt_state(string $nicknameKey): array
{
    $path = chat_login_attempt_file($nicknameKey);
    if ($path === null || !is_file($path)) {
        return ['window_started_at' => 0, 'failed_count' => 0, 'blocked_until' => 0];
    }

    $decoded = json_decode((string) @file_get_contents($path), true);
    if (!is_array($decoded)) {
        return ['window_started_at' => 0, 'failed_count' => 0, 'blocked_until' => 0];
    }

    $state = [
        'window_started_at' => max(0, (int) ($decoded['window_started_at'] ?? 0)),
        'failed_count' => max(0, (int) ($decoded['failed_count'] ?? 0)),
        'blocked_until' => max(0, (int) ($decoded['blocked_until'] ?? 0))
    ];
    if ($state['blocked_until'] < time() - CHAT_LOGIN_ATTEMPT_WINDOW_SECONDS) {
        @unlink($path);
        return ['window_started_at' => 0, 'failed_count' => 0, 'blocked_until' => 0];
    }

    return $state;
}

function chat_save_login_attempt_state(string $nicknameKey, array $state): void
{
    $path = chat_login_attempt_file($nicknameKey);
    if ($path !== null) {
        @file_put_contents($path, json_encode($state), LOCK_EX);
    }
}

function chat_require_login_attempt_available(string $nicknameKey): void
{
    if (chat_login_attempt_state($nicknameKey)['blocked_until'] > time()) {
        chat_fail('Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za kilka minut.', 429);
    }
}

function chat_register_login_attempt_failure(string $nicknameKey): void
{
    $now = time();
    $state = chat_login_attempt_state($nicknameKey);
    if ($state['window_started_at'] === 0 || ($now - $state['window_started_at']) >= CHAT_LOGIN_ATTEMPT_WINDOW_SECONDS) {
        $state = ['window_started_at' => $now, 'failed_count' => 0, 'blocked_until' => 0];
    }

    $state['failed_count']++;
    if ($state['failed_count'] >= CHAT_LOGIN_ATTEMPT_LIMIT) {
        $state['blocked_until'] = $now + CHAT_LOGIN_LOCKOUT_SECONDS;
    }
    chat_save_login_attempt_state($nicknameKey, $state);
}

function chat_clear_login_attempts(string $nicknameKey): void
{
    $path = chat_login_attempt_file($nicknameKey);
    if ($path !== null && is_file($path)) {
        @unlink($path);
    }
}

/** @return array<string, mixed> */
function chat_json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return $_POST;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** @param array<string, mixed> $payload */
function chat_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chat_fail(string $message, int $status = 400): never
{
    chat_json(['ok' => false, 'error' => $message], $status);
}

function chat_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        chat_fail('Ta operacja wymaga żądania POST.', 405);
    }
}

function chat_csrf_token(): string
{
    if (empty($_SESSION['chat_csrf_token']) || !is_string($_SESSION['chat_csrf_token'])) {
        $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['chat_csrf_token'];
}

function chat_require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['chat_csrf_token'] ?? '';

    if (!is_string($token) || !is_string($expected) || $token === '' || !hash_equals($expected, $token)) {
        chat_fail('Nieprawidłowy token bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 403);
    }
}

function chat_normalize_nickname(string $nickname): string
{
    $nickname = trim(preg_replace('/\s+/u', ' ', $nickname) ?? '');
    return function_exists('mb_strtolower') ? mb_strtolower($nickname, 'UTF-8') : strtolower($nickname);
}

function chat_text_length(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

function chat_validate_nickname(string $nickname): string
{
    $nickname = trim(preg_replace('/\s+/u', ' ', $nickname) ?? '');
    $length = chat_text_length($nickname);
    $reserved = ['administrator', 'admin', 'moderator', 'obsługa', 'obsluga'];

    if ($length < CHAT_NICK_MIN_LENGTH || $length > CHAT_NICK_MAX_LENGTH) {
        chat_fail('Pseudonim musi mieć od 3 do 32 znaków.');
    }

    if (preg_match('/^[\p{L}\p{N}][\p{L}\p{N}_. -]*$/u', $nickname) !== 1) {
        chat_fail('Pseudonim może zawierać litery, cyfry, spacje oraz znaki: . _ -');
    }

    if (in_array(chat_normalize_nickname($nickname), $reserved, true)) {
        chat_fail('Ten pseudonim jest zastrzeżony dla obsługi czatu.');
    }

    return $nickname;
}

function chat_ensure_nickname_block_schema(): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    global $pdo;
    $statement = $pdo->prepare("SHOW TABLES LIKE 'chat_nickname_blocks'");
    $statement->execute();
    return $checked = $statement->fetchColumn() !== false;
}

function chat_nickname_is_blocked(string $nicknameKey): bool
{
    if (!chat_ensure_nickname_block_schema()) {
        return false;
    }
    global $pdo;
    $purge = $pdo->prepare('DELETE FROM chat_nickname_blocks WHERE blocked_until <= UTC_TIMESTAMP()');
    $purge->execute();
    $stmt = $pdo->prepare('SELECT 1 FROM chat_nickname_blocks WHERE nickname_key = ? AND blocked_until > UTC_TIMESTAMP() LIMIT 1');
    $stmt->execute([$nicknameKey]);
    return $stmt->fetchColumn() !== false;
}

function chat_require_nickname_not_blocked(string $nicknameKey): void
{
    if (chat_nickname_is_blocked($nicknameKey)) {
        chat_fail('Ten pseudonim jest czasowo zablokowany. Spróbuj ponownie później.', 403);
    }
}

/** @return array<int, array<string, mixed>> */
function chat_active_nickname_blocks(): array
{
    if (!chat_ensure_nickname_block_schema()) {
        return [];
    }
    global $pdo;
    $stmt = $pdo->query("SELECT id, nickname, blocked_until, created_at FROM chat_nickname_blocks WHERE blocked_until > UTC_TIMESTAMP() ORDER BY blocked_until ASC, nickname ASC");
    return $stmt ? $stmt->fetchAll() : [];
}

function chat_validate_message(string $message, bool $allowEmpty = false): string
{
    $message = trim(preg_replace('/\r\n?|\n/u', "\n", $message) ?? '');
    $length = chat_text_length($message);

    if ($length < 1 && !$allowEmpty) {
        chat_fail('Wiadomość nie może być pusta.');
    }

    if ($length > CHAT_MESSAGE_MAX_LENGTH) {
        chat_fail('Wiadomość może mieć maksymalnie ' . CHAT_MESSAGE_MAX_LENGTH . ' znaków.');
    }

    return $message;
}

function chat_image_directory(): string
{
    return dirname(__DIR__) . '/uploads';
}

/** @return array{storageName: string, mimeType: string, fileSize: int}|null */
function chat_store_uploaded_image(?array $upload): ?array
{
    if ($upload === null || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        chat_fail('Nie udało się przesłać zdjęcia. Spróbuj ponownie.', 400);
    }

    $tmpName = (string) ($upload['tmp_name'] ?? '');
    $fileSize = (int) ($upload['size'] ?? 0);
    if ($fileSize < 1 || $fileSize > CHAT_IMAGE_MAX_BYTES || !is_uploaded_file($tmpName)) {
        chat_fail('Zdjęcie musi być prawidłowym plikiem JPG, PNG lub WebP o rozmiarze do 8 MB.', 400);
    }

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
        chat_fail('Możesz dodać wyłącznie zdjęcie JPG, PNG lub WebP.', 400);
    }

    $dimensions = @getimagesize($tmpName);
    if (!is_array($dimensions)
        || (int) ($dimensions[0] ?? 0) < 1
        || (int) ($dimensions[1] ?? 0) < 1
        || (int) $dimensions[0] > CHAT_IMAGE_MAX_DIMENSION
        || (int) $dimensions[1] > CHAT_IMAGE_MAX_DIMENSION) {
        chat_fail('Zdjęcie musi mieć wymiary od 1 × 1 do 4096 × 4096 pikseli.', 400);
    }

    $directory = chat_image_directory();
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        chat_fail('Nie można przygotować bezpiecznego katalogu zdjęć.', 500);
    }

    $storageName = bin2hex(random_bytes(24)) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($tmpName, $directory . '/' . $storageName)) {
        chat_fail('Nie udało się zapisać zdjęcia. Spróbuj ponownie.', 500);
    }
    @chmod($directory . '/' . $storageName, 0644);

    return ['storageName' => $storageName, 'mimeType' => $mimeType, 'fileSize' => $fileSize];
}

function chat_delete_stored_image(?string $storageName): void
{
    if ($storageName === null || $storageName === '' || !preg_match('/^[a-f0-9]{48}\.(jpg|png|webp)$/', $storageName)) {
        return;
    }
    $path = chat_image_directory() . '/' . $storageName;
    if (is_file($path)) {
        @unlink($path);
    }
}

function chat_cleanup_expired_images(): int
{
    global $pdo;
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 2)');
    $lock->execute(['66_600_chat_image_cleanup']);
    if ((int) $lock->fetchColumn() !== 1) {
        return 0;
    }

    try {
        $stmt = $pdo->query("SELECT id, storage_name FROM chat_message_images
            WHERE deleted_at IS NULL AND expires_at <= UTC_TIMESTAMP()");
        $images = $stmt->fetchAll();
        foreach ($images as $image) {
            chat_delete_stored_image((string) $image['storage_name']);
        }
        if ($images !== []) {
            $ids = array_map(static fn(array $image): int => (int) $image['id'], $images);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $update = $pdo->prepare("UPDATE chat_message_images
                SET deleted_at = UTC_TIMESTAMP(), storage_name = NULL
                WHERE id IN ($placeholders)");
            $update->execute($ids);
        }
        return count($images);
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute(['66_600_chat_image_cleanup']);
    }
}

/** @return array<int, int> Identyfikatory publicznych wpisów usuniętych po 24 godzinach. */
function chat_cleanup_expired_messages(): array
{
    global $pdo;
    $lockName = '66_600_chat_message_retention';
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 2)');
    $lock->execute([$lockName]);
    if ((int) $lock->fetchColumn() !== 1) {
        return [];
    }

    try {
        $pdo->beginTransaction();
        $ttl = CHAT_MESSAGE_RETENTION_TTL_SECONDS;
        $select = $pdo->prepare("SELECT m.id, i.storage_name
            FROM chat_messages m
            LEFT JOIN chat_message_images i ON i.message_id = m.id
            WHERE m.created_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL {$ttl} SECOND)
            FOR UPDATE");
        $select->execute();
        $expired = $select->fetchAll();
        $messageIds = array_map(static fn(array $row): int => (int) $row['id'], $expired);

        foreach ($expired as $row) {
            chat_delete_stored_image(is_string($row['storage_name'] ?? null) ? $row['storage_name'] : null);
        }

        if ($messageIds !== []) {
            $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
            $deleteImages = $pdo->prepare("DELETE FROM chat_message_images WHERE message_id IN ($placeholders)");
            $deleteImages->execute($messageIds);
            $deleteMessages = $pdo->prepare("DELETE FROM chat_messages WHERE id IN ($placeholders)");
            $deleteMessages->execute($messageIds);
        }

        $pdo->commit();
        return $messageIds;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }
}

/** @return array{messageIds: array<int, int>, images: int, nicknames: int, privateMessages: int} */
function chat_cleanup_expired_content(): array
{
    global $pdo;
    $messageIds = chat_cleanup_expired_messages();
    $images = chat_cleanup_expired_images();
    $privateMessages = $pdo->exec("DELETE FROM chat_private_messages
        WHERE created_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_MESSAGE_RETENTION_TTL_SECONDS . " SECOND)");
    $nicknames = chat_cleanup_inactive_sessions();

    return [
        'messageIds' => $messageIds,
        'images' => $images,
        'nicknames' => $nicknames,
        'privateMessages' => $privateMessages === false ? 0 : $privateMessages
    ];
}

/** Zwraca ścieżkę katalogu modułu, np. /chatroom/, dla ciasteczka sesji. */
function chat_cookie_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptName === '') {
        return '/';
    }

    $path = preg_replace('#/api/[^/]+$#', '', $scriptName) ?? $scriptName;
    if ($path === $scriptName) {
        $path = dirname($scriptName);
    }

    $path = '/' . trim($path, '/');
    return $path === '/' ? '/' : $path . '/';
}

function chat_issue_cookie(string $token, ?int $ttl = CHAT_COOKIE_TTL_SECONDS): void
{
    setcookie(CHAT_SESSION_COOKIE, $token, [
        'expires' => $ttl > 0 ? time() + $ttl : 0,
        'path' => chat_cookie_path(),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    $_COOKIE[CHAT_SESSION_COOKIE] = $token;
}

function chat_expire_cookie(): void
{
    setcookie(CHAT_SESSION_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => chat_cookie_path(),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    unset($_COOKIE[CHAT_SESSION_COOKIE]);
}

function chat_get_token_hash(): ?string
{
    $token = $_COOKIE[CHAT_SESSION_COOKIE] ?? null;
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        return null;
    }

    return hash('sha256', $token);
}

/** Zwalnia i anonimizuje nick po 24 godzinach bez aktywności. */
function chat_cleanup_inactive_sessions(): int
{
    global $pdo;
    $ttl = CHAT_NICK_RETENTION_TTL_SECONDS;
    $stmt = $pdo->prepare("UPDATE chat_sessions
        SET is_active = 0,
            token_hash = NULL,
            nickname = CONCAT('_expired_', id),
            nickname_key = CONCAT('_expired_', id),
            role = 'guest',
            owner_admin_user_id = NULL,
            moderator_until = NULL,
            muted_until = NULL
        WHERE is_active = 1
          AND last_seen < DATE_SUB(UTC_TIMESTAMP(), INTERVAL {$ttl} SECOND)");
    $stmt->execute();
    return $stmt->rowCount();
}

/** @return array<string, mixed>|null */
function chat_get_current_session(bool $touch = true): ?array
{
    global $pdo;
    $tokenHash = chat_get_token_hash();
    if ($tokenHash === null) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM chat_sessions WHERE token_hash = ? LIMIT 1');
    $stmt->execute([$tokenHash]);
    $session = $stmt->fetch();
    if (!$session) {
        chat_expire_cookie();
        return null;
    }

    // Sesja administratora nie może pozostać uprzywilejowana po wygaśnięciu
    // autoryzacji panelu administracyjnego.
    if (($session['role'] ?? '') === 'admin' && !isAdminLoggedIn()) {
        $deactivate = $pdo->prepare("UPDATE chat_sessions
            SET is_active = 0, token_hash = NULL, nickname_key = CONCAT('_admin_expired_', id),
                role = 'guest', owner_admin_user_id = NULL, moderator_until = NULL, muted_until = NULL
            WHERE id = ?");
        $deactivate->execute([(int) $session['id']]);
        chat_expire_cookie();
        return null;
    }

    $nicknameExpired = strtotime((string) $session['last_seen'] . ' UTC') < (time() - CHAT_NICK_RETENTION_TTL_SECONDS);
    if ($nicknameExpired) {
        chat_cleanup_inactive_sessions();
        chat_expire_cookie();
        return null;
    }

    $ttl = CHAT_PRESENCE_TTL_SECONDS;
    $isFresh = (int) ($session['is_active'] ?? 0) === 1
        && strtotime((string) $session['last_seen'] . ' UTC') >= (time() - $ttl);

    if (!$isFresh) {
        $lockName = '66_600_chat_return_lock';
        $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute([$lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            return null;
        }

        try {
            $pdo->beginTransaction();
            chat_cleanup_inactive_sessions();
            $nickKey = chat_normalize_nickname((string) $session['nickname']);
            $conflict = $pdo->prepare('SELECT id FROM chat_sessions WHERE nickname_key = ? AND is_active = 1 AND id <> ? LIMIT 1 FOR UPDATE');
            $conflict->execute([$nickKey, (int) $session['id']]);
            $activeCount = (int) $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE is_active = 1 AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND)")->fetchColumn();

            if ($conflict->fetch() || $activeCount >= CHAT_MAX_ACTIVE_USERS) {
                $pdo->rollBack();
                return null;
            }

            $restore = $pdo->prepare("UPDATE chat_sessions
                SET is_active = 1, nickname_key = ?, role = 'guest', moderator_until = NULL,
                    muted_until = NULL, last_seen = UTC_TIMESTAMP()
                WHERE id = ?");
            $restore->execute([$nickKey, (int) $session['id']]);
            $pdo->commit();
            $session['is_active'] = 1;
            $session['nickname_key'] = $nickKey;
            $session['role'] = 'guest';
            $session['moderator_until'] = null;
            $session['muted_until'] = null;
            $session['last_seen'] = gmdate('Y-m-d H:i:s');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        } finally {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        }
    } elseif ($touch) {
        $touchStmt = $pdo->prepare('UPDATE chat_sessions SET last_seen = UTC_TIMESTAMP() WHERE id = ?');
        $touchStmt->execute([(int) $session['id']]);
        $session['last_seen'] = gmdate('Y-m-d H:i:s');
    }

    chat_issue_cookie(strtolower((string) ($_COOKIE[CHAT_SESSION_COOKIE] ?? '')), !empty($session['account_id']) ? 0 : CHAT_COOKIE_TTL_SECONDS);
    $session['effective_role'] = chat_effective_role($session);
    return $session;
}

/** @param array<string, mixed> $session */
function chat_effective_role(array $session): string
{
    if (isAdminLoggedIn()
        && !empty($session['owner_admin_user_id'])
        && (int) $session['owner_admin_user_id'] === (int) ($_SESSION['user_id'] ?? 0)) {
        return 'admin';
    }

    if (($session['role'] ?? '') === 'moderator'
        && !empty($session['moderator_until'])
        && strtotime((string) $session['moderator_until'] . ' UTC') > time()) {
        return 'moderator';
    }

    return 'guest';
}

/** @return array<string, mixed> */
function chat_require_session(): array
{
    $session = chat_get_current_session();
    if ($session === null) {
        chat_fail('Sesja czatu wygasła. Wprowadź ponownie swój pseudonim.', 401);
    }
    if (empty($session['terms_accepted_at'])) {
        chat_fail('Zaakceptuj regulamin, aby korzystać z pokoju.', 428);
    }

    return $session;
}

/** @param array<string, mixed> $account */
function chat_start_account_session(array $account): array
{
    global $pdo;
    chat_require_nickname_not_blocked((string) ($account['nickname_key'] ?? ''));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $lockName = '66_600_chat_join_lock';
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $lock->execute([$lockName]);
    if ((int) $lock->fetchColumn() !== 1) {
        chat_fail('Czat jest chwilowo zajęty. Spróbuj ponownie za moment.', 503);
    }

    try {
        $pdo->beginTransaction();
        chat_cleanup_inactive_sessions();
        $nicknameKey = (string) $account['nickname_key'];
        $existing = $pdo->prepare('SELECT id FROM chat_sessions WHERE nickname_key = ? AND is_active = 1 LIMIT 1 FOR UPDATE');
        $existing->execute([$nicknameKey]);
        if ($existing->fetch()) {
            $pdo->rollBack();
            chat_fail('Ten nick jest aktualnie używany.', 409);
        }
        $count = (int) $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE is_active = 1 AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND)")->fetchColumn();
        $activeAdminCount = (int) $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE is_active = 1 AND role = 'admin' AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND)")->fetchColumn();
        $guestLimit = $activeAdminCount > 0 ? CHAT_MAX_ACTIVE_USERS : CHAT_MAX_ACTIVE_USERS - 1;
        if ($count >= $guestLimit) {
            $pdo->rollBack();
            chat_fail('Pokój jest pełny. Jedno miejsce jest zarezerwowane dla administracji.', 429);
        }
        $insert = $pdo->prepare('INSERT INTO chat_sessions (token_hash, nickname, nickname_key, account_id, role, last_seen) VALUES (?, ?, ?, ?, \'guest\', UTC_TIMESTAMP())');
        $insert->execute([$tokenHash, (string) $account['nickname'], $nicknameKey, (int) $account['id']]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }

    chat_issue_cookie($token, 0);
    return [
        'id' => $id,
        'nickname' => (string) $account['nickname'],
        'nickname_key' => (string) $account['nickname_key'],
        'account_id' => (int) $account['id'],
        'role' => 'guest',
        'effective_role' => 'guest',
        'terms_accepted_at' => null,
        'last_seen' => gmdate('Y-m-d H:i:s')
    ];
}

function chat_create_permanent_account(string $nickname, string $password): array
{
    global $pdo;
    $nickname = chat_validate_nickname($nickname);
    if (strlen($password) < CHAT_PASSWORD_MIN_LENGTH) {
        chat_fail('Hasło musi mieć co najmniej ' . CHAT_PASSWORD_MIN_LENGTH . ' znaków.');
    }
    $nicknameKey = chat_normalize_nickname($nickname);
    chat_require_nickname_not_blocked($nicknameKey);
    $claimStmt = $pdo->prepare('SELECT id FROM chat_nick_claims WHERE nickname_key = ? LIMIT 1');
    $claimStmt->execute([$nicknameKey]);
    if ($claimStmt->fetch()) chat_fail('Ten nick jest już zastrzeżony. Wybierz inny.', 409);
    $existing = $pdo->prepare('SELECT id FROM chat_nick_accounts WHERE nickname_key = ? AND is_active = 1 LIMIT 1');
    $existing->execute([$nicknameKey]);
    if ($existing->fetch()) chat_fail('Ten nick jest już zarejestrowany.', 409);
    $activeSession = $pdo->prepare('SELECT id FROM chat_sessions WHERE nickname_key = ? AND is_active = 1 LIMIT 1');
    $activeSession->execute([$nicknameKey]);
    if ($activeSession->fetch()) chat_fail('Ten nick jest aktualnie używany.', 409);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($passwordHash)) chat_fail('Nie udało się zabezpieczyć hasła.', 500);
    try {
        $insert = $pdo->prepare('INSERT INTO chat_nick_accounts (nickname, nickname_key, password_hash, terms_version) VALUES (?, ?, ?, ?)');
        $insert->execute([$nickname, $nicknameKey, $passwordHash, CHAT_TERMS_VERSION]);
    } catch (PDOException $exception) {
        if ((int) $exception->errorInfo[1] === 1062) chat_fail('Ten nick jest już zarejestrowany.', 409);
        throw $exception;
    }
    $account = ['id' => (int) $pdo->lastInsertId(), 'nickname' => $nickname, 'nickname_key' => $nicknameKey];
    return chat_start_account_session($account);
}

function chat_login_permanent_account(string $nickname, string $password): array
{
    global $pdo;
    $nickname = chat_validate_nickname($nickname);
    $nicknameKey = chat_normalize_nickname($nickname);
    chat_require_nickname_not_blocked($nicknameKey);
    chat_require_login_attempt_available($nicknameKey);

    $stmt = $pdo->prepare('SELECT id, nickname, nickname_key, password_hash FROM chat_nick_accounts WHERE nickname_key = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$nicknameKey]);
    $account = $stmt->fetch();
    if (!$account || !password_verify($password, (string) $account['password_hash'])) {
        chat_register_login_attempt_failure($nicknameKey);
        chat_fail('Podany nick lub hasło są nieprawidłowe.', 401);
    }

    chat_clear_login_attempts($nicknameKey);
    return chat_start_account_session($account);
}

function chat_accept_terms_for_current_session(): array
{
    global $pdo;
    $session = chat_get_current_session(false);
    if ($session === null) chat_fail('Sesja czatu wygasła. Wprowadź ponownie swój nick.', 401);
    $stmt = $pdo->prepare('UPDATE chat_sessions SET terms_accepted_at = UTC_TIMESTAMP(), last_seen = UTC_TIMESTAMP() WHERE id = ?');
    $stmt->execute([(int) $session['id']]);
    $session['terms_accepted_at'] = gmdate('Y-m-d H:i:s');
    return $session;
}

/** @return array<string, mixed> */
function chat_ensure_admin_session(): array
{
    global $pdo;

    if (!isAdminLoggedIn()) {
        chat_fail('Ta funkcja jest dostępna wyłącznie dla administratora.', 403);
    }

    $adminId = (int) ($_SESSION['user_id'] ?? 0);
    $current = chat_get_current_session(false);
    if ($current !== null && (int) ($current['owner_admin_user_id'] ?? 0) === $adminId) {
        $stmt = $pdo->prepare("UPDATE chat_sessions SET last_seen = UTC_TIMESTAMP(), role = 'admin', moderator_until = NULL WHERE id = ?");
        $stmt->execute([(int) $current['id']]);
        $current['role'] = 'admin';
        $current['effective_role'] = 'admin';
        return $current;
    }

    $displayName = trim((string) ($_SESSION['full_name'] ?? 'Administrator'));
    if ($displayName === '') {
        $displayName = 'Administrator';
    }
    $displayName = function_exists('mb_substr')
        ? mb_substr($displayName, 0, CHAT_NICK_MAX_LENGTH, 'UTF-8')
        : substr($displayName, 0, CHAT_NICK_MAX_LENGTH);
    $nicknameKey = '_admin_' . $adminId;
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $pdo->beginTransaction();
    try {
        chat_cleanup_inactive_sessions();
        if ($current !== null) {
            $deactivateCurrent = $pdo->prepare("UPDATE chat_sessions
                SET is_active = 0,
                    nickname_key = CONCAT('_promoted_', id),
                    role = 'guest',
                    owner_admin_user_id = NULL,
                    moderator_until = NULL,
                    muted_until = NULL
                WHERE id = ?");
            $deactivateCurrent->execute([(int) $current['id']]);
        }
        $stmt = $pdo->prepare("UPDATE chat_sessions
            SET is_active = 0,
                token_hash = NULL,
                nickname_key = CONCAT('_admin_replaced_', id),
                role = 'guest',
                owner_admin_user_id = NULL,
                moderator_until = NULL,
                muted_until = NULL
            WHERE owner_admin_user_id = ?");
        $stmt->execute([$adminId]);
        $activeCount = (int) $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE is_active = 1 AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND)")->fetchColumn();
        if ($activeCount >= CHAT_MAX_ACTIVE_USERS) {
            $pdo->rollBack();
            chat_fail('Pokój jest pełny. Administrator nie może teraz utworzyć dodatkowej sesji.', 429);
        }
        $stmt = $pdo->prepare("INSERT INTO chat_sessions (token_hash, nickname, nickname_key, role, owner_admin_user_id, last_seen) VALUES (?, ?, ?, 'admin', ?, UTC_TIMESTAMP())");
        $stmt->execute([$tokenHash, $displayName, $nicknameKey, $adminId]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    chat_issue_cookie($token);
    return [
        'id' => $id,
        'nickname' => $displayName,
        'nickname_key' => $nicknameKey,
        'role' => 'admin',
        'owner_admin_user_id' => $adminId,
        'effective_role' => 'admin',
        'last_seen' => gmdate('Y-m-d H:i:s')
    ];
}

/** @return array<string, mixed> */
function chat_create_guest_session(string $nickname): array
{
    global $pdo;
    $nickname = chat_validate_nickname($nickname);
    $nicknameKey = chat_normalize_nickname($nickname);
    chat_require_nickname_not_blocked($nicknameKey);
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $lockName = '66_600_chat_join_lock';

    $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $lock->execute([$lockName]);
    if ((int) $lock->fetchColumn() !== 1) {
        chat_fail('Czat jest chwilowo zajęty. Spróbuj ponownie za moment.', 503);
    }

    try {
        $pdo->beginTransaction();
        chat_cleanup_inactive_sessions();

        $claimStmt = $pdo->prepare('SELECT token_hash FROM chat_nick_claims WHERE nickname_key = ? FOR UPDATE');
        $claimStmt->execute([$nicknameKey]);
        $claim = $claimStmt->fetch();
        if ($claim && !hash_equals((string) $claim['token_hash'], $tokenHash)) {
            $pdo->rollBack();
            chat_fail('Ten pseudonim jest zastrzeżony. Wybierz inny lub napisz do administratora.');
        }

        $existingStmt = $pdo->prepare('SELECT id FROM chat_sessions WHERE nickname_key = ? AND is_active = 1 LIMIT 1 FOR UPDATE');
        $existingStmt->execute([$nicknameKey]);
        if ($existingStmt->fetch()) {
            $pdo->rollBack();
            chat_fail('Ten pseudonim jest aktualnie używany. Wybierz inny.');
        }

        $count = (int) $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE is_active = 1 AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND)")->fetchColumn();
        $activeAdminCount = (int) $pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE is_active = 1 AND role = 'admin' AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND)")->fetchColumn();
        $guestLimit = $activeAdminCount > 0 ? CHAT_MAX_ACTIVE_USERS : CHAT_MAX_ACTIVE_USERS - 1;
        if ($count >= $guestLimit) {
            $pdo->rollBack();
            chat_fail('Pokój jest pełny. Jedno miejsce jest zarezerwowane dla administracji.', 429);
        }

        $insert = $pdo->prepare("INSERT INTO chat_sessions (token_hash, nickname, nickname_key, role, last_seen) VALUES (?, ?, ?, 'guest', UTC_TIMESTAMP())");
        $insert->execute([$tokenHash, $nickname, $nicknameKey]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }

    chat_issue_cookie($token);
    return [
        'id' => $id,
        'nickname' => $nickname,
        'role' => 'guest',
        'effective_role' => 'guest',
        'last_seen' => gmdate('Y-m-d H:i:s')
    ];
}

function chat_is_admin(): bool
{
    return isAdminLoggedIn();
}

/** @return array<string, mixed> */
function chat_require_admin_session(): array
{
    if (!chat_is_admin()) {
        chat_fail('Ta operacja jest dostępna wyłącznie dla administratora.', 403);
    }

    return chat_ensure_admin_session();
}

/** @param array<string, mixed> $session */
function chat_can_moderate(array $session): bool
{
    return in_array($session['effective_role'] ?? 'guest', ['admin', 'moderator'], true);
}

/** @return array<string, mixed>|null */
function chat_find_active_admin(): ?array
{
    global $pdo;
    $stmt = $pdo->query("SELECT id, nickname FROM chat_sessions WHERE is_active = 1 AND role = 'admin' AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND) ORDER BY last_seen DESC LIMIT 1");
    $admin = $stmt->fetch();
    return $admin ?: null;
}

/** @param array<string, mixed> $session */
function chat_require_message_cooldown(array $session): void
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT last_message_at, muted_until FROM chat_sessions WHERE id = ? FOR UPDATE');
    $stmt->execute([(int) $session['id']]);
    $row = $stmt->fetch();

    if (!$row) {
        chat_fail('Sesja czatu już nie istnieje.', 401);
    }

    if (!empty($row['muted_until']) && strtotime((string) $row['muted_until'] . ' UTC') > time()) {
        chat_fail('Twoja możliwość wysyłania wiadomości została czasowo wyciszona.', 403);
    }

    if (!empty($row['last_message_at']) && (time() - strtotime((string) $row['last_message_at'] . ' UTC')) < CHAT_POST_COOLDOWN_SECONDS) {
        chat_fail('Wysyłasz wiadomości zbyt szybko. Poczekaj chwilę.', 429);
    }
}

function chat_log_action(int $actorSessionId, ?int $targetSessionId, string $action, ?string $details = null): void
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO chat_admin_log (actor_session_id, target_session_id, action, details) VALUES (?, ?, ?, ?)');
    $stmt->execute([$actorSessionId, $targetSessionId, $action, $details]);
}

function chat_ensure_unread_schema(): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    global $pdo;
    $columns = $pdo->query('SHOW COLUMNS FROM chat_sessions')->fetchAll(PDO::FETCH_COLUMN);
    return $checked = in_array('last_read_public_message_id', $columns, true);
}

/** @param array<string, mixed> $session */
function chat_get_public_unread_count(array $session): int
{
    if (empty($session['terms_accepted_at'])) {
        return 0;
    }

    if (!chat_ensure_unread_schema()) {
        return 0;
    }
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE id > ? AND author_session_id <> ? AND deleted_at IS NULL');
    $stmt->execute([(int) ($session['last_read_public_message_id'] ?? 0), (int) $session['id']]);
    return (int) $stmt->fetchColumn();
}

/** @param array<string, mixed> $session */
function chat_mark_public_messages_read(array $session, int $lastMessageId): int
{
    if (!chat_ensure_unread_schema()) {
        return 0;
    }
    global $pdo;
    $latestId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM chat_messages')->fetchColumn();
    $targetId = max(0, min($lastMessageId, $latestId));
    $update = $pdo->prepare('UPDATE chat_sessions SET last_read_public_message_id = GREATEST(last_read_public_message_id, ?) WHERE id = ?');
    $update->execute([$targetId, (int) $session['id']]);

    $session['last_read_public_message_id'] = max((int) ($session['last_read_public_message_id'] ?? 0), $targetId);
    return chat_get_public_unread_count($session);
}

/** @param array<string, mixed> $session */
function chat_session_payload(array $session): array
{
    return [
        'id' => (int) $session['id'],
        'nickname' => (string) $session['nickname'],
        'role' => (string) ($session['effective_role'] ?? 'guest'),
        'isAdmin' => ($session['effective_role'] ?? '') === 'admin',
        'canModerate' => chat_can_moderate($session),
        'csrfToken' => chat_csrf_token(),
        'isPermanent' => !empty($session['account_id']),
        'needsTerms' => empty($session['terms_accepted_at']),
        'unreadCount' => chat_get_public_unread_count($session)
    ];
}

function chat_require_private_pair(int $currentId, int $otherId): void
{
    global $pdo;
    if ($currentId === $otherId) {
        chat_fail('Nie można wysłać prywatnej wiadomości do siebie.');
    }

    $stmt = $pdo->prepare("SELECT id, role, last_seen FROM chat_sessions WHERE id IN (?, ?) AND is_active = 1 AND last_seen >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . CHAT_PRESENCE_TTL_SECONDS . " SECOND)");
    $stmt->execute([$currentId, $otherId]);
    $sessions = $stmt->fetchAll();
    if (count($sessions) !== 2) {
        chat_fail('Wybrany użytkownik nie jest już dostępny.', 404);
    }

    $hasAdmin = false;
    foreach ($sessions as $session) {
        if (($session['role'] ?? '') === 'admin') {
            $hasAdmin = true;
            break;
        }
    }

    if (!$hasAdmin) {
        chat_fail('Prywatna korespondencja jest dostępna wyłącznie z administratorem.', 403);
    }
}
?>
