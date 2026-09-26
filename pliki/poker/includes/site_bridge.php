<?php
/**
 * Integracja Poker Polski z serwisem nadrzędnym (katalog wyżej, np. 66600.pl).
 *
 * Moduł jest w pełni opcjonalny: jeżeli poker nie leży w podkatalogu serwisu
 * (brak plików includes/config.php, auth/lib.php i .private/66-600-security-config.php
 * w katalogu nadrzędnym) albo w config.php ustawiono 'site_integration' => false,
 * gra działa samodzielnie, jak dotychczas.
 *
 * Co zapewnia integracja:
 *  - wspólne logowanie: użytkownik zalogowany na stronie (konto publiczne lub
 *    administrator) gra pod tym samym loginem, bez ponownego logowania;
 *  - podtrzymanie sesji strony: aktywność w grze odświeża znacznik aktywności
 *    konta na stronie, więc granie nie powoduje wylogowania z serwisu;
 *  - zaproszenia do stołu publikowane w Chatroomie serwisu.
 *
 * Moduł nie dołącza żadnych plików PHP serwisu (brak konfliktów nazw funkcji)
 * i nigdy nie wysyła ciasteczka sesji serwisu — jedynie odczytuje istniejącą sesję.
 */
declare(strict_types=1);

const POKER_SITE_SESSION_NAME = 'krosno_admin_session';
const POKER_SITE_AUTH_IDLE_TIMEOUT = 604800; // zgodnie z auth/lib.php serwisu (7 dni)
const POKER_SITE_ADMIN_IDLE_TIMEOUT = 1800;  // zgodnie z includes/config.php serwisu (30 min)
const POKER_SITE_VERIFY_INTERVAL = 300;      // ponowna weryfikacja konta w bazie serwisu co 5 min
const POKER_CHAT_BOT_NICKNAME = 'Stół pokerowy';
const POKER_CHAT_INVITE_TEXT = 'Przy stole ktoś oczekuje właśnie na rozgrywkę. Czy chcesz zagrać teraz w pokera? Jeżeli tak, to zapraszamy do udziału poprzez ten link:';
const POKER_CHAT_TABLE_COOLDOWN = 120;       // min. odstęp zaproszeń do tego samego stołu (s)
const POKER_CHAT_USER_COOLDOWN = 60;         // min. odstęp zaproszeń od tego samego gracza (s)

/** Katalog główny serwisu nadrzędnego albo null, gdy poker działa samodzielnie. */
function pokerSiteRoot(): ?string
{
    static $resolved = false;
    static $root = null;
    if ($resolved) {
        return $root;
    }
    $resolved = true;
    global $config;
    if (is_array($config) && array_key_exists('site_integration', $config) && !$config['site_integration']) {
        return $root = null;
    }
    $candidate = realpath(__DIR__ . '/../..');
    if ($candidate === false) {
        return $root = null;
    }
    if (is_file($candidate . '/includes/config.php')
        && is_file($candidate . '/auth/lib.php')
        && is_file($candidate . '/.private/66-600-security-config.php')) {
        $root = $candidate;
    }
    return $root;
}

function pokerSiteIntegrated(): bool
{
    return pokerSiteRoot() !== null;
}

/** @return array<string,mixed>|null Prywatna konfiguracja serwisu (dane bazy strony i Chatroomu). */
function pokerSitePrivateConfig(): ?array
{
    static $loaded = false;
    static $data = null;
    if ($loaded) {
        return $data;
    }
    $loaded = true;
    $root = pokerSiteRoot();
    if ($root === null) {
        return null;
    }
    try {
        $value = (static function (string $file) {
            return require $file;
        })($root . '/.private/66-600-security-config.php');
        $data = is_array($value) ? $value : null;
    } catch (Throwable $exception) {
        $data = null;
    }
    return $data;
}

function pokerRequestIsHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

/** Ścieżka pliku sesji serwisu dla podanego identyfikatora albo null, gdy nie da się jej ustalić. */
function pokerSiteSessionFile(string $sid): ?string
{
    $root = pokerSiteRoot();
    if ($root === null) {
        return null;
    }
    $custom = $root . '/session';
    if (is_dir($custom) && is_writable($custom)) {
        return $custom . '/sess_' . $sid;
    }
    $path = (string)ini_get('session.save_path');
    if ($path === '') {
        $path = sys_get_temp_dir();
    }
    if (strpos($path, ';') !== false) {
        return null; // katalogi wielopoziomowe — nie odczytujemy
    }
    return rtrim($path, '/\\') . '/sess_' . $sid;
}

/**
 * Odczytuje sesję serwisu (jeśli istnieje) i odświeża jej znacznik aktywności.
 * Musi zostać wywołana PRZED uruchomieniem sesji pokera.
 *
 * @return array{kind:string,id:int,username:string}|null
 */
function pokerReadSiteSession(): ?array
{
    if (!pokerSiteIntegrated() || session_status() === PHP_SESSION_ACTIVE) {
        return null;
    }
    $sid = $_COOKIE[POKER_SITE_SESSION_NAME] ?? '';
    if (!is_string($sid) || preg_match('/^[A-Za-z0-9,-]{22,256}$/', $sid) !== 1) {
        return null;
    }
    $file = pokerSiteSessionFile($sid);
    if ($file === null || !is_file($file)) {
        return null;
    }

    $root = (string)pokerSiteRoot();
    $previousName = session_name();
    $previousPath = session_save_path();
    $custom = $root . '/session';
    if (is_dir($custom) && is_writable($custom)) {
        session_save_path($custom);
    }
    session_name(POKER_SITE_SESSION_NAME);
    session_id($sid);

    $iniKeys = array('session.use_cookies', 'session.use_only_cookies', 'session.use_strict_mode', 'session.gc_probability',
        'session.cookie_path', 'session.cookie_httponly', 'session.cookie_secure');
    $iniBackup = array();
    foreach ($iniKeys as $iniKey) {
        $iniBackup[$iniKey] = ini_get($iniKey);
    }

    $identity = null;
    $started = @session_start(array(
        'use_cookies' => 0,          // nigdy nie wysyłamy ciasteczka serwisu
        'use_only_cookies' => 1,
        'use_strict_mode' => 1,
        'gc_probability' => 0,       // nie uruchamiamy GC w katalogu sesji serwisu
        'cookie_path' => '/',
        'cookie_httponly' => 1,
        'cookie_secure' => pokerRequestIsHttps() ? 1 : 0,
    ));
    if ($started) {
        $now = time();
        // Administrator serwisu (panel) — ta sama reguła wygasania co w includes/config.php.
        $adminActive = !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true
            && (($_SESSION['role'] ?? '') === 'admin')
            && (int)($_SESSION['user_id'] ?? 0) > 0
            && ($now - (int)($_SESSION['admin_last_activity'] ?? 0)) <= POKER_SITE_ADMIN_IDLE_TIMEOUT;
        if ($adminActive) {
            if ($now - (int)($_SESSION['admin_last_activity'] ?? 0) >= 30) {
                $_SESSION['admin_last_activity'] = $now;
            }
            $identity = array('kind' => 'admin', 'id' => (int)$_SESSION['user_id'], 'username' => (string)($_SESSION['username'] ?? ''));
        } else {
            // Konto publiczne serwisu — reguła z auth/lib.php (auth_session_user_id).
            $userId = (int)($_SESSION['auth_user_id'] ?? 0);
            $last = (int)($_SESSION['auth_last_activity'] ?? 0);
            if ($userId > 0 && $last > 0 && ($now - $last) <= POKER_SITE_AUTH_IDLE_TIMEOUT) {
                if ($now - $last >= 30) {
                    $_SESSION['auth_last_activity'] = $now;
                }
                $identity = array('kind' => 'user', 'id' => $userId, 'username' => (string)($_SESSION['auth_username'] ?? ''));
            }
        }
        session_write_close(); // zapis (lub samo odświeżenie znacznika czasu pliku) sesji serwisu
    }

    // Przywrócenie ustawień dla własnej sesji pokera.
    foreach ($iniBackup as $iniKey => $iniValue) {
        if ($iniValue !== false) {
            @ini_set($iniKey, (string)$iniValue);
        }
    }
    session_name($previousName);
    session_save_path($previousPath);
    return $identity;
}

/** Połączenie z bazą serwisu (tylko odczyt kont). */
function pokerSitePdo(): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    $pdo = null;
    $cfg = pokerSitePrivateConfig();
    $db = is_array($cfg['db'] ?? null) ? $cfg['db'] : null;
    if (!$db || !isset($db['host'], $db['user'], $db['pass'], $db['name'])) {
        return null;
    }
    try {
        $dsn = 'mysql:host=' . $db['host'] . (isset($db['port']) ? ';port=' . (int)$db['port'] : '') . ';dbname=' . $db['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string)$db['user'], (string)$db['pass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ));
    } catch (Throwable $exception) {
        error_log('Poker: brak połączenia z bazą serwisu: ' . $exception->getMessage());
        $pdo = null;
    }
    return $pdo;
}

/** Połączenie z bazą Chatroomu serwisu. */
function pokerChatPdo(): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    $pdo = null;
    $cfg = pokerSitePrivateConfig();
    $chat = is_array($cfg['chat_database'] ?? null) ? $cfg['chat_database'] : null;
    if (!$chat || !isset($chat['dsn'], $chat['username'], $chat['password'])) {
        return null;
    }
    try {
        $pdo = new PDO((string)$chat['dsn'], (string)$chat['username'], (string)$chat['password'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ));
    } catch (Throwable $exception) {
        error_log('Poker: brak połączenia z bazą Chatroomu: ' . $exception->getMessage());
        $pdo = null;
    }
    return $pdo;
}

function pokerChatAvailable(): bool
{
    $cfg = pokerSitePrivateConfig();
    return pokerSiteIntegrated() && is_array($cfg['chat_database'] ?? null)
        && pokerSiteRoot() !== null && is_dir(pokerSiteRoot() . '/chatroom');
}

/**
 * Zweryfikowana tożsamość z sesji serwisu (sprawdzana w bazie serwisu co kilka minut).
 * Wymaga aktywnej sesji pokera (bufor weryfikacji).
 *
 * @return array{uid:string,kind:string,username:string}|null
 */
function pokerSiteIdentity(): ?array
{
    static $memo = false;
    if ($memo !== false) {
        return $memo;
    }
    $raw = $GLOBALS['pokerSiteRaw'] ?? null;
    if (!is_array($raw)) {
        unset($_SESSION['poker_site_check']);
        return $memo = null;
    }
    $uid = ($raw['kind'] === 'admin' ? 'a:' : 'u:') . (int)$raw['id'];
    $cache = $_SESSION['poker_site_check'] ?? null;
    if (is_array($cache) && ($cache['uid'] ?? '') === $uid && (time() - (int)($cache['at'] ?? 0)) < POKER_SITE_VERIFY_INTERVAL) {
        return $memo = array('uid' => $uid, 'kind' => $raw['kind'], 'username' => (string)$cache['username']);
    }

    $sitePdo = pokerSitePdo();
    $username = null;
    if ($sitePdo !== null) {
        try {
            if ($raw['kind'] === 'admin') {
                $stmt = $sitePdo->prepare("SELECT username, role, is_active FROM users WHERE id = ? LIMIT 1");
                $stmt->execute(array((int)$raw['id']));
                $row = $stmt->fetch();
                if ($row && ($row['role'] ?? '') === 'admin' && (int)($row['is_active'] ?? 1) === 1) {
                    $username = (string)$row['username'];
                }
            } else {
                $stmt = $sitePdo->prepare('SELECT username FROM auth_users WHERE id = ? LIMIT 1');
                $stmt->execute(array((int)$raw['id']));
                $value = $stmt->fetchColumn();
                if ($value !== false) {
                    $username = (string)$value;
                }
            }
        } catch (Throwable $exception) {
            // Chwilowy błąd bazy serwisu — ufamy sesji serwisu, ale nie buforujemy wyniku.
            $username = $raw['username'] !== '' ? $raw['username'] : null;
            return $memo = $username === null ? null : array('uid' => $uid, 'kind' => $raw['kind'], 'username' => $username);
        }
    } elseif ($raw['username'] !== '') {
        $username = $raw['username'];
    }

    if ($username === null || $username === '') {
        unset($_SESSION['poker_site_check']);
        return $memo = null;
    }
    $_SESSION['poker_site_check'] = array('uid' => $uid, 'username' => $username, 'at' => time());
    return $memo = array('uid' => $uid, 'kind' => $raw['kind'], 'username' => $username);
}

/** Czy nick należy do konta serwisu (konto publiczne lub administrator). */
function pokerSiteUsernameTaken(string $username): bool
{
    $sitePdo = pokerSitePdo();
    if ($sitePdo === null) {
        return false;
    }
    try {
        $stmt = $sitePdo->prepare('SELECT 1 FROM auth_users WHERE username = ? COLLATE utf8mb4_unicode_ci LIMIT 1');
        $stmt->execute(array($username));
        if ($stmt->fetchColumn()) {
            return true;
        }
        $stmt = $sitePdo->prepare('SELECT 1 FROM users WHERE username = ? COLLATE utf8mb4_unicode_ci LIMIT 1');
        $stmt->execute(array($username));
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

/** Publiczny adres katalogu gry, np. https://66600.pl/poker/ */
function pokerPublicBaseUrl(): string
{
    $origin = '';
    foreach (array(getenv('SITE_URL'), $_SERVER['SITE_URL'] ?? null, $_SERVER['REDIRECT_SITE_URL'] ?? null) as $candidate) {
        if (is_string($candidate) && preg_match('#^https?://[^/\s]+#i', $candidate, $match) === 1) {
            $origin = $match[0];
            break;
        }
    }
    if ($origin === '') {
        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost')));
        $origin = (pokerRequestIsHttps() ? 'https' : 'http') . '://' . ($host !== '' ? $host : 'localhost');
    }
    $dir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    $dir = rtrim($dir === '.' ? '' : $dir, '/');
    return $origin . $dir . '/';
}

/** Zwraca identyfikator sesji „bota” Chatroomu, w razie potrzeby tworząc ją. */
function pokerChatBotSession(PDO $pokerPdo, PDO $chatPdo): int
{
    $metaStmt = $pokerPdo->prepare("SELECT meta_value FROM poker_meta WHERE meta_key = 'chat_bot_session_id'");
    $metaStmt->execute();
    $botId = (int)($metaStmt->fetchColumn() ?: 0);

    if ($botId > 0) {
        $check = $chatPdo->prepare('SELECT id, is_active, token_hash, nickname FROM chat_sessions WHERE id = ? LIMIT 1');
        $check->execute(array($botId));
        $row = $check->fetch();
        if ($row && (int)$row['is_active'] === 1 && $row['token_hash'] === null && strpos((string)$row['nickname'], '_expired_') !== 0) {
            $chatPdo->prepare('UPDATE chat_sessions SET last_seen = UTC_TIMESTAMP(), last_message_at = UTC_TIMESTAMP() WHERE id = ?')->execute(array($botId));
            return $botId;
        }
    }

    $candidates = array(POKER_CHAT_BOT_NICKNAME, POKER_CHAT_BOT_NICKNAME . ' 66600', POKER_CHAT_BOT_NICKNAME . ' ' . random_int(100, 999));
    $insert = $chatPdo->prepare("INSERT INTO chat_sessions
        (token_hash, nickname, nickname_key, account_id, role, is_active, last_seen, terms_accepted_at, last_message_at)
        VALUES (NULL, ?, ?, NULL, 'guest', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())");
    foreach ($candidates as $nickname) {
        $key = function_exists('mb_strtolower') ? mb_strtolower($nickname, 'UTF-8') : strtolower($nickname);
        $taken = $chatPdo->prepare('SELECT id FROM chat_sessions WHERE nickname_key = ? AND is_active = 1 LIMIT 1');
        $taken->execute(array($key));
        if ($taken->fetchColumn()) {
            continue;
        }
        try {
            $insert->execute(array($nickname, $key));
        } catch (PDOException $exception) {
            continue;
        }
        $botId = (int)$chatPdo->lastInsertId();
        $save = $pokerPdo->prepare("INSERT INTO poker_meta (meta_key, meta_value) VALUES ('chat_bot_session_id', ?)
            ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)");
        $save->execute(array((string)$botId));
        return $botId;
    }
    throw new RuntimeException('Chatroom jest chwilowo niedostępny. Spróbuj ponownie za chwilę.');
}

/**
 * Publikuje w Chatroomie zaproszenie do stołu.
 * @return array{message:string,retry_after:int}
 */
function pokerSendChatInvite(PDO $pokerPdo, array $user, int $tableId): array
{
    if (!pokerChatAvailable()) {
        throw new RuntimeException('Chatroom nie jest dostępny w tej instalacji.');
    }
    $tableStmt = $pokerPdo->prepare('SELECT id, name FROM poker_tables WHERE id = ? LIMIT 1');
    $tableStmt->execute(array($tableId));
    $table = $tableStmt->fetch();
    if (!$table) {
        throw new RuntimeException('Wybrany stół już nie istnieje.');
    }
    $seatStmt = $pokerPdo->prepare('SELECT 1 FROM table_players WHERE table_id = ? AND user_id = ? LIMIT 1');
    $seatStmt->execute(array($tableId, (int)$user['id']));
    if (!$seatStmt->fetchColumn()) {
        throw new RuntimeException('Zaproszenie możesz wysłać tylko do stołu, przy którym siedzisz.');
    }
    $countStmt = $pokerPdo->prepare('SELECT COUNT(*) FROM table_players WHERE table_id = ?');
    $countStmt->execute(array($tableId));
    if ((int)$countStmt->fetchColumn() >= MAX_PLAYERS) {
        throw new RuntimeException('Przy tym stole nie ma już wolnych miejsc.');
    }

    $tableWait = $pokerPdo->prepare('SELECT GREATEST(0, ? - TIMESTAMPDIFF(SECOND, MAX(created_at), UTC_TIMESTAMP())) FROM poker_chat_invites WHERE table_id = ?');
    $tableWait->execute(array(POKER_CHAT_TABLE_COOLDOWN, $tableId));
    $waitTable = (int)($tableWait->fetchColumn() ?: 0);
    $userWait = $pokerPdo->prepare('SELECT GREATEST(0, ? - TIMESTAMPDIFF(SECOND, MAX(created_at), UTC_TIMESTAMP())) FROM poker_chat_invites WHERE user_id = ?');
    $userWait->execute(array(POKER_CHAT_USER_COOLDOWN, (int)$user['id']));
    $waitUser = (int)($userWait->fetchColumn() ?: 0);
    $wait = max($waitTable, $waitUser);
    if ($wait > 0) {
        $error = new RuntimeException('Zaproszenie zostało wysłane przed chwilą. Kolejne możesz wysłać za ' . $wait . ' s.');
        throw $error;
    }

    $chatPdo = pokerChatPdo();
    if ($chatPdo === null) {
        throw new RuntimeException('Chatroom jest chwilowo niedostępny. Spróbuj ponownie za chwilę.');
    }
    $link = pokerPublicBaseUrl() . '?stol=' . (int)$tableId;
    $body = POKER_CHAT_INVITE_TEXT . ' ' . $link;

    $botId = pokerChatBotSession($pokerPdo, $chatPdo);
    $chatPdo->prepare("INSERT INTO chat_messages (author_session_id, author_role, body) VALUES (?, 'guest', ?)")
        ->execute(array($botId, $body));
    $pokerPdo->prepare('INSERT INTO poker_chat_invites (table_id, user_id, created_at) VALUES (?, ?, UTC_TIMESTAMP())')
        ->execute(array($tableId, (int)$user['id']));
    $pokerPdo->exec('DELETE FROM poker_chat_invites WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)');

    return array('message' => 'Zaproszenie zostało wysłane na Chatroom.', 'retry_after' => POKER_CHAT_TABLE_COOLDOWN);
}

/** Dane integracji przekazywane do przeglądarki (adresy względne — bez wpisanej domeny). */
function pokerSiteClientInfo(?array $user): array
{
    if (!pokerSiteIntegrated()) {
        return array('integrated' => false);
    }
    return array(
        'integrated' => true,
        'name' => '66600.PL',
        'home_url' => '../',
        'login_url' => '../auth/login.php',
        'register_url' => '../auth/register.php',
        'account_url' => '../auth/my.php',
        'logged_in' => $user !== null && ($user['auth_type'] ?? '') === 'site',
        'chat_invite' => pokerChatAvailable(),
    );
}
