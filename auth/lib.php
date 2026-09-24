<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

const AUTH_IDLE_TIMEOUT = 604800; // 7 dni bezczynności; aktywna sesja przesuwa termin.
const AUTH_MAX_LOGIN_ATTEMPTS = 8;
const AUTH_LOGIN_WINDOW = 900; // 15 minut.
const AUTH_MAX_REGISTER_ATTEMPTS = 5;
const AUTH_REGISTER_WINDOW = 3600; // 1 godzina.

function auth_ensure_schema(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    global $pdo;
    try {
        foreach (['auth_users', 'auth_rate_limits', 'ads', 'blog_posts'] as $table) {
            $statement = $pdo->prepare('SHOW TABLES LIKE ?');
            $statement->execute([$table]);
            if ($statement->fetchColumn() === false) {
                return $ready = false;
            }
        }
        foreach (['ads', 'blog_posts'] as $table) {
            $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('owner_id', $columns, true)) {
                return $ready = false;
            }
        }
        return $ready = true;
    } catch (Throwable $error) {
        $ready = false;
    }
    return $ready;
}

function auth_normalize_username(string $username): string
{
    $username = trim($username);
    return function_exists('mb_strtolower')
        ? mb_strtolower($username, 'UTF-8')
        : strtolower($username);
}

function auth_validate_username(string $username): ?string
{
    $username = trim($username);
    $length = function_exists('mb_strlen') ? mb_strlen($username, 'UTF-8') : strlen($username);
    if ($length < 3 || $length > 32) {
        return 'Nazwa użytkownika musi mieć od 3 do 32 znaków.';
    }
    if (preg_match('/^[\p{L}\p{N}][\p{L}\p{N}_.-]{2,31}$/u', $username) !== 1) {
        return 'Użyj wyłącznie liter, cyfr, kropki, myślnika lub podkreślenia.';
    }
    return null;
}

function auth_validate_password(string $password, string $username = ''): ?string
{
    if (strlen($password) < 10) {
        return 'Hasło musi mieć co najmniej 10 znaków.';
    }
    if (strlen($password) > 255) {
        return 'Hasło jest zbyt długie.';
    }
    if (!preg_match('/[A-ZĄĆĘŁŃÓŚŹŻ]/u', $password) ||
        !preg_match('/[a-ząćęłńóśźż]/u', $password) ||
        !preg_match('/[0-9]/', $password)) {
        return 'Hasło musi zawierać wielką literę, małą literę i cyfrę.';
    }
    if ($username !== '' && auth_normalize_username($password) === auth_normalize_username($username)) {
        return 'Hasło nie może być takie samo jak nazwa użytkownika.';
    }
    return null;
}

function auth_csrf_token(): string
{
    $sessionBinding = session_id();
    if ($sessionBinding === '') {
        $sessionBinding = (string) ($_COOKIE[session_name()] ?? 'no-session');
    }
    $secret = defined('PULSE_RATE_HASH_SECRET') ? PULSE_RATE_HASH_SECRET : 'local-auth-csrf-secret';
    $token = hash_hmac('sha256', 'public-auth-csrf|' . $sessionBinding, $secret);
    $_SESSION['auth_csrf_token'] = $token;
    // Double-submit cookie keeps the form usable on hosts with unreliable
    // session persistence while preserving CSRF protection.
    if (($_COOKIE['auth_csrf_token'] ?? '') !== $token && !headers_sent()) {
        setcookie('auth_csrf_token', $token, [
            'expires' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    return $token;
}

function auth_verify_csrf(?string $token): void
{
    $expected = auth_csrf_token();
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1 || !hash_equals($expected, $token)) {
        throw new RuntimeException('Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
    }
}

function auth_session_user_id(): int
{
    $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
    $lastActivity = (int) ($_SESSION['auth_last_activity'] ?? 0);
    if ($userId <= 0 || $lastActivity <= 0 || time() - $lastActivity > AUTH_IDLE_TIMEOUT) {
        auth_clear_session();
        return 0;
    }
    $_SESSION['auth_last_activity'] = time();
    return $userId;
}

function auth_current_user(): ?array
{
    $userId = auth_session_user_id();
    if ($userId <= 0 || !auth_ensure_schema()) {
        return null;
    }
    global $pdo;
    $statement = $pdo->prepare('SELECT id, username, role, created_at, last_seen FROM auth_users WHERE id = ? LIMIT 1');
    $statement->execute([$userId]);
    $user = $statement->fetch();
    if (!$user) {
        auth_clear_session();
        return null;
    }

    if (empty($_SESSION['auth_last_seen_update']) || time() - (int) $_SESSION['auth_last_seen_update'] >= 300) {
        $update = $pdo->prepare('UPDATE auth_users SET last_seen = CURRENT_TIMESTAMP WHERE id = ?');
        $update->execute([$userId]);
        $_SESSION['auth_last_seen_update'] = time();
    }
    return $user;
}

function auth_is_logged_in(): bool
{
    return auth_current_user() !== null;
}

function auth_set_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['auth_user_id'] = (int) $user['id'];
    $_SESSION['auth_role'] = (int) ($user['role'] ?? 0);
    $_SESSION['auth_username'] = (string) $user['username'];
    $_SESSION['auth_last_activity'] = time();
    $_SESSION['auth_last_seen_update'] = time();
    $_SESSION['auth_csrf_token'] = bin2hex(random_bytes(32));
}

function auth_clear_session(): void
{
    unset(
        $_SESSION['auth_user_id'],
        $_SESSION['auth_role'],
        $_SESSION['auth_username'],
        $_SESSION['auth_last_activity'],
        $_SESSION['auth_last_seen_update'],
        $_SESSION['auth_csrf_token']
    );
}

function auth_logout(): void
{
    // The administrator panel and the chatroom share the PHP session cookie.
    // Remove only the public-account namespace so logging out here does not
    // unexpectedly destroy an administrator session.
    auth_clear_session();
    session_regenerate_id(true);
}

function auth_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function auth_bucket_key(string $scope, string $username = ''): string
{
    $secret = defined('PULSE_RATE_HASH_SECRET') ? PULSE_RATE_HASH_SECRET : 'local-auth-rate-limit';
    $identity = $scope . '|' . auth_client_ip() . '|' . auth_normalize_username($username);
    return hash_hmac('sha256', $identity, $secret);
}

function auth_rate_is_blocked(string $scope, string $username, int $maxAttempts, int $window, int $lockSeconds): bool
{
    if (!auth_ensure_schema()) {
        return false;
    }
    global $pdo;
    $key = auth_bucket_key($scope, $username);
    $statement = $pdo->prepare('SELECT attempts, window_started_at, locked_until FROM auth_rate_limits WHERE bucket_key = ? LIMIT 1');
    $statement->execute([$key]);
    $row = $statement->fetch();
    if (!$row) {
        return false;
    }
    if (!empty($row['locked_until']) && strtotime((string) $row['locked_until']) > time()) {
        return true;
    }
    if (strtotime((string) $row['window_started_at']) + $window <= time()) {
        return false;
    }
    return (int) $row['attempts'] >= $maxAttempts;
}

function auth_rate_record_failure(string $scope, string $username, int $maxAttempts, int $window, int $lockSeconds): void
{
    if (!auth_ensure_schema()) {
        return;
    }
    global $pdo;
    $key = auth_bucket_key($scope, $username);
    $now = date('Y-m-d H:i:s');
    $statement = $pdo->prepare('SELECT attempts, window_started_at FROM auth_rate_limits WHERE bucket_key = ? LIMIT 1');
    $statement->execute([$key]);
    $row = $statement->fetch();
    if (!$row || strtotime((string) $row['window_started_at']) + $window <= time()) {
        $upsert = $pdo->prepare(
            'INSERT INTO auth_rate_limits (bucket_key, attempts, window_started_at, locked_until)
             VALUES (?, 1, ?, NULL)
             ON DUPLICATE KEY UPDATE attempts = 1, window_started_at = VALUES(window_started_at), locked_until = NULL'
        );
        $upsert->execute([$key, $now]);
        return;
    }

    $attempts = (int) $row['attempts'] + 1;
    $lockedUntil = $attempts >= $maxAttempts ? date('Y-m-d H:i:s', time() + $lockSeconds) : null;
    $update = $pdo->prepare('UPDATE auth_rate_limits SET attempts = ?, locked_until = ? WHERE bucket_key = ?');
    $update->execute([$attempts, $lockedUntil, $key]);
}

function auth_rate_clear(string $scope, string $username): void
{
    if (!auth_ensure_schema()) {
        return;
    }
    global $pdo;
    $statement = $pdo->prepare('DELETE FROM auth_rate_limits WHERE bucket_key = ?');
    $statement->execute([auth_bucket_key($scope, $username)]);
}

function auth_username_exists(string $username): bool
{
    global $pdo;
    $statement = $pdo->prepare('SELECT id FROM auth_users WHERE username = ? COLLATE utf8mb4_unicode_ci LIMIT 1');
    $statement->execute([$username]);
    if ($statement->fetchColumn()) {
        return true;
    }
    $adminStatement = $pdo->prepare("SELECT id FROM users WHERE username = ? COLLATE utf8mb4_unicode_ci AND role = 'admin' LIMIT 1");
    $adminStatement->execute([$username]);
    return (bool) $adminStatement->fetchColumn();
}

function auth_owned_ad(int $id, int $userId): ?array
{
    global $pdo;
    $statement = $pdo->prepare('SELECT * FROM ads WHERE id = ? AND owner_id = ? LIMIT 1');
    $statement->execute([$id, $userId]);
    return $statement->fetch() ?: null;
}

function auth_owned_post(int $id, int $userId): ?array
{
    global $pdo;
    $statement = $pdo->prepare('SELECT * FROM blog_posts WHERE id = ? AND owner_id = ? LIMIT 1');
    $statement->execute([$id, $userId]);
    return $statement->fetch() ?: null;
}

function auth_page_start(string $title): void
{
    $GLOBALS['pageTitle'] = $title;
    $GLOBALS['pageDescription'] = $title . ' — 66600.PL';
    require dirname(__DIR__) . '/includes/header.php';
}

function auth_page_end(): void
{
    require dirname(__DIR__) . '/includes/footer.php';
}

function auth_require_user(): array
{
    $user = auth_current_user();
    if ($user === null) {
        redirect(SITE_URL . '/auth/login.php');
    }
    return $user;
}

function auth_form_value(string $key): string
{
    return sanitize((string) ($_POST[$key] ?? ''));
}
