<?php
/**
 * Wspólny bootstrap aplikacji Poker Polski.
 * Kompatybilny z PHP 7.4+ i MySQL/MariaDB przez PDO.
 */
declare(strict_types=1);

const POKER_ROOT = __DIR__ . '/..';
const DEFAULT_CHIPS = 5000;
const MAX_PLAYERS = 4;
const SMALL_BLIND = 10;
const BIG_BLIND = 20;
const DAILY_REFILL_SECONDS = 86400;
const GUEST_INACTIVITY_SECONDS = 86400;
const TURN_SECONDS = 30;

$configFile = __DIR__ . '/../config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Aplikacja nie została jeszcze zainstalowana. Otwórz install.php.');
}
$config = require $configFile;
if (!is_array($config) || empty($config['installed'])) {
    http_response_code(503);
    exit('Aplikacja nie została jeszcze skonfigurowana. Otwórz install.php.');
}

require_once __DIR__ . '/site_bridge.php';
require_once __DIR__ . '/szu.php';
// Odczyt sesji serwisu nadrzędnego (wspólne logowanie) — musi nastąpić przed startem sesji gry.
$pokerSiteRaw = pokerReadSiteSession();

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', (string)GUEST_INACTIVITY_SECONDS);
$sessionSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');
// Ciasteczko sesji ograniczone do katalogu gry (np. /poker/), aby nie kolidowało z innymi aplikacjami na domenie.
$sessionPath = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
$sessionPath = rtrim($sessionPath === '.' ? '' : $sessionPath, '/') . '/';
session_set_cookie_params(array(
    'lifetime' => GUEST_INACTIVITY_SECONDS,
    'path' => $sessionPath,
    'secure' => $sessionSecure,
    'httponly' => true,
    'samesite' => 'Lax',
));
session_name('poker_polski_session');
$pokerManualSessionId = session_id() !== '';
if ($pokerManualSessionId) {
    // Po odczycie sesji serwisu PHP pamięta jej identyfikator — przywracamy identyfikator sesji gry.
    $pokerCookieId = $_COOKIE['poker_polski_session'] ?? '';
    session_id(is_string($pokerCookieId) && preg_match('/^[A-Za-z0-9,-]{22,256}$/', $pokerCookieId) === 1 ? $pokerCookieId : session_create_id());
}
session_start();
if ($pokerManualSessionId && !headers_sent()) {
    header_remove('Set-Cookie'); // ciasteczko sesji gry wysyłamy poniżej tylko raz
}
// Odnowienie czasu życia cookie przy każdej aktywności w aplikacji.
setcookie(session_name(), session_id(), array(
    'expires' => time() + GUEST_INACTIVITY_SECONDS,
    'path' => $sessionPath,
    'secure' => $sessionSecure,
    'httponly' => true,
    'samesite' => 'Lax',
));

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']),
        $config['db_user'],
        $config['db_pass'],
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        )
    );
} catch (PDOException $exception) {
    http_response_code(503);
    exit('Nie można połączyć się z bazą danych. Sprawdź dane w config.php.');
}

/** @return string */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return string */
function jsonResponse(array $payload, int $status = 200): string
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function requireJsonRequest(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(array('ok' => false, 'message' => 'Dozwolone są tylko żądania POST.')));
    }
}

/** @return array<string,mixed> */
function requestData(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);
    return is_array($json) ? $json : $_POST;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf'];
}

function verifyCsrf(array $data): void
{
    $token = (string)($data['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $token)) {
        throw new RuntimeException('Sesja wygasła. Odśwież stronę i spróbuj ponownie.');
    }
}

/**
 * Usuwa tymczasowych graczy, którzy nie wykonywali żadnej aktywności przez 24 godziny.
 * Przed usunięciem zwalnia ich miejsca i bezpiecznie resetuje dotknięte stoły do oczekiwania.
 */
function purgeInactiveGuests(PDO $pdo): void
{
    $cutoff = gmdate('Y-m-d H:i:s', time() - GUEST_INACTIVITY_SECONDS);
    $expiredStmt = $pdo->prepare("SELECT id FROM users WHERE auth_type = 'guest' AND last_activity_at < ?");
    $expiredStmt->execute(array($cutoff));
    $ids = array_map('intval', array_column($expiredStmt->fetchAll(), 'id'));
    if (!$ids) {
        return;
    }

    $marks = implode(',', array_fill(0, count($ids), '?'));
    $affectedStmt = $pdo->prepare("SELECT DISTINCT table_id FROM table_players WHERE user_id IN ($marks)");
    $affectedStmt->execute($ids);
    $tableIds = array_map('intval', array_column($affectedStmt->fetchAll(), 'table_id'));

    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id IN ($marks)");
    $deleteStmt->execute($ids);
    foreach ($tableIds as $tableId) {
        // Po usunięciu gościa zwracamy pozostałym osobom żetony z niedokończonej ręki.
        $remainingStmt = $pdo->prepare('SELECT id, user_id, chips, total_bet FROM table_players WHERE table_id = ?');
        $remainingStmt->execute(array($tableId));
        $remaining = $remainingStmt->fetchAll();
        $returnStmt = $pdo->prepare("UPDATE table_players SET chips = ?, in_hand = 0, folded = 0, all_in = 0, current_bet = 0, total_bet = 0, hole_cards_json = '[]' WHERE id = ?");
        $walletStmt = $pdo->prepare('UPDATE users SET chips = ? WHERE id = ?');
        foreach ($remaining as $player) {
            $restored = (int)$player['chips'] + (int)$player['total_bet'];
            $returnStmt->execute(array($restored, (int)$player['id']));
            $walletStmt->execute(array($restored, (int)$player['user_id']));
        }
        $state = pokerDefaultState();
        $state['messages'] = array('Ręka została bezpiecznie anulowana po wygaśnięciu nieaktywnego gościa. Zakłady pozostałych graczy zwrócono.');
        saveTableState($pdo, $tableId, 'waiting', $state);
    }
    szuCleanupOrphans($pdo);
}

const POKER_SCHEMA_VERSION = 3;

/** Automatyczna migracja bazy (bez konsoli): konta serwisu, dłuższe nicki, zaproszenia do Chatroomu. */
function pokerEnsureSchema(PDO $pdo): void
{
    if ((int)($_SESSION['poker_schema'] ?? 0) === POKER_SCHEMA_VERSION) {
        return;
    }
    try {
        $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'auth_type'")->fetch();
        if ($column && (stripos((string)$column['Type'], "'site'") === false || stripos((string)$column['Type'], "'bot'") === false)) {
            $pdo->exec("ALTER TABLE users MODIFY auth_type ENUM('guest','reserved','site','bot') NOT NULL DEFAULT 'guest'");
        }
        if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'site_uid'")->fetch()) {
            $pdo->exec('ALTER TABLE users ADD COLUMN site_uid VARCHAR(40) NULL DEFAULT NULL AFTER auth_type, ADD UNIQUE KEY uq_users_site_uid (site_uid)');
        }
        $name = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'")->fetch();
        if ($name && preg_match('/varchar\((\d+)\)/i', (string)$name['Type'], $m) === 1 && (int)$m[1] < 32) {
            $pdo->exec('ALTER TABLE users MODIFY username VARCHAR(32) NOT NULL');
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS poker_meta (
            meta_key VARCHAR(64) NOT NULL PRIMARY KEY,
            meta_value VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS poker_chat_invites (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            table_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_invites_table (table_id, created_at),
            KEY idx_invites_user (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        // Wielki Szu — statystyki stylu gry ludzi (model przeciwnika).
        $pdo->exec("CREATE TABLE IF NOT EXISTS poker_szu_stats (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            hands INT UNSIGNED NOT NULL DEFAULT 0,
            vpip INT UNSIGNED NOT NULL DEFAULT 0,
            pfr INT UNSIGNED NOT NULL DEFAULT 0,
            post_aggr INT UNSIGNED NOT NULL DEFAULT 0,
            post_calls INT UNSIGNED NOT NULL DEFAULT 0,
            faced_bet INT UNSIGNED NOT NULL DEFAULT 0,
            fold_to_bet INT UNSIGNED NOT NULL DEFAULT 0,
            showdowns INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $_SESSION['poker_schema'] = POKER_SCHEMA_VERSION;
    } catch (Throwable $exception) {
        error_log('Poker: migracja schematu nie powiodła się: ' . $exception->getMessage());
    }
}

/**
 * Zwraca identyfikator konta gry powiązanego z kontem serwisu (tworzy je przy pierwszym wejściu).
 * @param array{uid:string,kind:string,username:string} $site
 */
function pokerLinkSiteUser(PDO $pdo, array $site): int
{
    $find = $pdo->prepare('SELECT id, username FROM users WHERE site_uid = ? LIMIT 1');
    $find->execute(array($site['uid']));
    $row = $find->fetch();
    $name = function_exists('mb_substr') ? mb_substr($site['username'], 0, 32, 'UTF-8') : substr($site['username'], 0, 32);

    // Nick serwisu ma pierwszeństwo: ewentualne konto gościa/rezerwacji o tej samej nazwie otrzymuje przyrostek.
    if (szuReservedName($name)) {
        $name = (function_exists('mb_substr') ? mb_substr($name, 0, 25, 'UTF-8') : substr($name, 0, 25)) . '_gracz';
    }
    $conflict = $pdo->prepare("SELECT id FROM users WHERE username = ? AND (site_uid IS NULL OR site_uid <> ?) AND auth_type <> 'bot' LIMIT 1");
    $conflict->execute(array($name, $site['uid']));
    $conflictId = (int)($conflict->fetchColumn() ?: 0);
    if ($conflictId > 0) {
        $base = function_exists('mb_substr') ? mb_substr($name, 0, 24, 'UTF-8') : substr($name, 0, 24);
        $pdo->prepare('UPDATE users SET username = ? WHERE id = ?')->execute(array($base . '_' . $conflictId, $conflictId));
    }

    if ($row) {
        if ((string)$row['username'] !== $name) {
            $pdo->prepare('UPDATE users SET username = ? WHERE id = ?')->execute(array($name, (int)$row['id']));
        }
        return (int)$row['id'];
    }
    $insert = $pdo->prepare("INSERT INTO users (username, password_hash, auth_type, site_uid, chips, last_refill_at, last_activity_at)
        VALUES (?, NULL, 'site', ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
    $insert->execute(array($name, $site['uid'], DEFAULT_CHIPS));
    return (int)$pdo->lastInsertId();
}

/** Porzucone konto gościa (po zalogowaniu na stronie) zwalnia miejsce przy stole, jeśli to możliwe. */
function pokerRetireGuest(PDO $pdo, int $userId): void
{
    try {
        $stmt = $pdo->prepare("SELECT tp.table_id FROM table_players tp INNER JOIN users u ON u.id = tp.user_id
            WHERE tp.user_id = ? AND u.auth_type = 'guest' LIMIT 1");
        $stmt->execute(array($userId));
        $tableId = (int)($stmt->fetchColumn() ?: 0);
        if ($tableId > 0) {
            leaveTable($pdo, $tableId, $userId);
        }
    } catch (Throwable $exception) {
        // Trwa rozdanie — miejsce zwolni się automatycznie po wygaśnięciu gościa.
    }
}

function beginUserSession(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

/** @return array<string,mixed>|null */
function currentUser(PDO $pdo): ?array
{
    purgeInactiveGuests($pdo);
    pokerEnsureSchema($pdo);
    $id = (int)($_SESSION['user_id'] ?? 0);

    // Wspólne logowanie z serwisem: konto strony zawsze ma pierwszeństwo przed kontem gościa.
    $site = pokerSiteIdentity();
    if ($site !== null) {
        $linkedId = pokerLinkSiteUser($pdo, $site);
        if ($linkedId !== $id) {
            if ($id > 0) {
                pokerRetireGuest($pdo, $id);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $linkedId;
            $id = $linkedId;
        }
    }
    if ($id < 1) {
        return null;
    }
    $statement = $pdo->prepare('SELECT id, username, auth_type, chips, last_refill_at, last_activity_at, created_at FROM users WHERE id = ? LIMIT 1');
    $statement->execute(array($id));
    $user = $statement->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }
    if ($user['auth_type'] === 'site' && $site === null) {
        // Wylogowano się ze strony — wylogowanie obejmuje także grę.
        unset($_SESSION['user_id']);
        return null;
    }

    $activity = $pdo->prepare('UPDATE users SET last_activity_at = UTC_TIMESTAMP() WHERE id = ?');
    $activity->execute(array($id));
    $user['last_activity_at'] = gmdate('Y-m-d H:i:s');
    refillUserIfDue($pdo, $user);
    return $user;
}

/** @param array<string,mixed> $user */
function refillUserIfDue(PDO $pdo, array &$user): void
{
    $last = strtotime((string)$user['last_refill_at']);
    if ($last === false || time() - $last < DAILY_REFILL_SECONDS) {
        return;
    }

    $now = gmdate('Y-m-d H:i:s');
    $statement = $pdo->prepare('UPDATE users SET chips = ?, last_refill_at = ? WHERE id = ?');
    $statement->execute(array(DEFAULT_CHIPS, $now, (int)$user['id']));
    $user['chips'] = DEFAULT_CHIPS;
    $user['last_refill_at'] = $now;

    // Jeśli gracz czeka przy stole (poza rozdaniem), odśwież także jego stos żetonów.
    $statement = $pdo->prepare(
        "UPDATE table_players tp INNER JOIN poker_tables t ON t.id = tp.table_id
         SET tp.chips = ? WHERE tp.user_id = ? AND t.status IN ('waiting','finished')"
    );
    $statement->execute(array(DEFAULT_CHIPS, (int)$user['id']));
}

/** @return int */
function secondsUntilRefill(array $user): int
{
    $last = strtotime((string)$user['last_refill_at']);
    if ($last === false) {
        return 0;
    }
    return max(0, DAILY_REFILL_SECONDS - (time() - $last));
}

function requireLogin(PDO $pdo): array
{
    $user = currentUser($pdo);
    if ($user === null) {
        throw new RuntimeException('Podaj nick, aby wejść do gry.');
    }
    return $user;
}

/** @return array<int,array<string,mixed>> */
function fetchTables(PDO $pdo): array
{
    szuCleanupOrphans($pdo);
    $sql = "SELECT t.id, t.name, t.status, t.small_blind, t.big_blind, t.updated_at,
              COUNT(tp.id) AS player_count,
              COALESCE(SUM(u.auth_type = 'bot'), 0) AS bot_count
            FROM poker_tables t
            LEFT JOIN table_players tp ON tp.table_id = t.id
            LEFT JOIN users u ON u.id = tp.user_id
            GROUP BY t.id
            ORDER BY (t.status = 'playing') DESC, t.updated_at DESC, t.id DESC";
    return $pdo->query($sql)->fetchAll();
}

function seatPlayer(PDO $pdo, int $tableId, int $userId): void
{
    $pdo->beginTransaction();
    try {
        $tableStatement = $pdo->prepare('SELECT * FROM poker_tables WHERE id = ? FOR UPDATE');
        $tableStatement->execute(array($tableId));
        $table = $tableStatement->fetch();
        if (!$table) {
            throw new RuntimeException('Wybrany stół już nie istnieje.');
        }
        if ($table['status'] === 'playing') {
            throw new RuntimeException('Nie można dołączyć w trakcie rozdania. Wybierz stół oczekujący.');
        }

        $check = $pdo->prepare('SELECT table_id FROM table_players WHERE user_id = ? LIMIT 1 FOR UPDATE');
        $check->execute(array($userId));
        $existing = $check->fetch();
        if ($existing) {
            if ((int)$existing['table_id'] === $tableId) {
                $pdo->commit();
                return;
            }
            throw new RuntimeException('Najpierw opuść aktualny stół.');
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM table_players WHERE table_id = ? FOR UPDATE');
        $countStmt->execute(array($tableId));
        if ((int)$countStmt->fetchColumn() >= MAX_PLAYERS) {
            throw new RuntimeException('Przy tym stole nie ma już wolnych miejsc.');
        }

        $seatsStmt = $pdo->prepare('SELECT seat FROM table_players WHERE table_id = ? ORDER BY seat');
        $seatsStmt->execute(array($tableId));
        $used = array_map('intval', array_column($seatsStmt->fetchAll(), 'seat'));
        $seat = 1;
        while (in_array($seat, $used, true)) {
            $seat++;
        }

        $userStmt = $pdo->prepare('SELECT chips FROM users WHERE id = ? FOR UPDATE');
        $userStmt->execute(array($userId));
        $chips = (int)$userStmt->fetchColumn();
        if ($chips < BIG_BLIND) {
            throw new RuntimeException('Masz za mało punktów, aby wejść do stołu. Poczekaj na dzienne odnowienie puli.');
        }

        $insert = $pdo->prepare('INSERT INTO table_players (table_id, user_id, seat, chips) VALUES (?, ?, ?, ?)');
        $insert->execute(array($tableId, $userId, $seat, $chips));
        $pdo->prepare('UPDATE poker_tables SET updated_at = UTC_TIMESTAMP() WHERE id = ?')->execute(array($tableId));
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function leaveTable(PDO $pdo, int $tableId, int $userId): void
{
    $pdo->beginTransaction();
    try {
        $tableStmt = $pdo->prepare('SELECT status FROM poker_tables WHERE id = ? FOR UPDATE');
        $tableStmt->execute(array($tableId));
        $table = $tableStmt->fetch();
        if (!$table) {
            throw new RuntimeException('Stół nie istnieje.');
        }
        if ($table['status'] === 'playing') {
            throw new RuntimeException('Nie można opuścić stołu w trakcie rozdania. Zakończ bieżącą rękę.');
        }
        $playerStmt = $pdo->prepare('SELECT chips FROM table_players WHERE table_id = ? AND user_id = ? FOR UPDATE');
        $playerStmt->execute(array($tableId, $userId));
        $player = $playerStmt->fetch();
        if (!$player) {
            throw new RuntimeException('Nie siedzisz przy tym stole.');
        }
        $pdo->prepare('UPDATE users SET chips = ? WHERE id = ?')->execute(array((int)$player['chips'], $userId));
        $pdo->prepare('DELETE FROM table_players WHERE table_id = ? AND user_id = ?')->execute(array($tableId, $userId));
        // Ostatni człowiek odchodzi — Wielki Szu także zwalnia miejsca.
        $humansLeft = $pdo->prepare("SELECT COUNT(*) FROM table_players tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.table_id = ? AND u.auth_type <> 'bot'");
        $humansLeft->execute(array($tableId));
        if ((int)$humansLeft->fetchColumn() === 0) {
            $pdo->prepare("DELETE tp FROM table_players tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.table_id = ? AND u.auth_type = 'bot'")->execute(array($tableId));
        }
        $pdo->prepare('UPDATE poker_tables SET updated_at = UTC_TIMESTAMP() WHERE id = ?')->execute(array($tableId));
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
    szuDeleteEmptySzuTables($pdo);
}

function cardLabel(string $card): string
{
    $rank = substr($card, 0, -1);
    $suit = substr($card, -1);
    $ranks = array('T' => '10', 'J' => 'W', 'Q' => 'D', 'K' => 'K', 'A' => 'A');
    $suits = array('s' => '♠', 'h' => '♥', 'd' => '♦', 'c' => '♣');
    return ($ranks[$rank] ?? $rank) . ($suits[$suit] ?? $suit);
}

function cardColor(string $card): string
{
    return in_array(substr($card, -1), array('h', 'd'), true) ? 'red' : 'black';
}

function formatChips(int $chips): string
{
    return number_format($chips, 0, ',', ' ') . ' pkt';
}

function tableStateForUser(PDO $pdo, int $tableId, int $userId): array
{
    $tableStmt = $pdo->prepare('SELECT * FROM poker_tables WHERE id = ? LIMIT 1');
    $tableStmt->execute(array($tableId));
    $table = $tableStmt->fetch();
    if (!$table) {
        throw new RuntimeException('Stół nie istnieje.');
    }
    $state = json_decode((string)$table['state_json'], true) ?: pokerDefaultState();
    $playersStmt = $pdo->prepare(
        'SELECT tp.*, u.username, u.auth_type FROM table_players tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.table_id = ? ORDER BY tp.seat'
    );
    $playersStmt->execute(array($tableId));
    $players = $playersStmt->fetchAll();
    szuAliasPlayers($players);

    $public = array();
    $showCards = in_array((string)$table['status'], array('showdown', 'finished'), true);
    // Karty odkrywają tylko gracze biorący udział w odkryciu; wygrana po spasowaniu przeciwników nie ujawnia kart.
    $revealCards = $showCards && (($state['last_result']['type'] ?? '') === 'showdown');
    foreach ($players as $player) {
        $isSelf = (int)$player['user_id'] === $userId;
        $cards = json_decode((string)$player['hole_cards_json'], true) ?: array();
        $public[] = array(
            'user_id' => (int)$player['user_id'],
            'username' => $player['username'],
            'seat' => (int)$player['seat'],
            'chips' => (int)$player['chips'],
            'in_hand' => (bool)$player['in_hand'],
            'folded' => (bool)$player['folded'],
            'all_in' => (bool)$player['all_in'],
            'current_bet' => (int)$player['current_bet'],
            'total_bet' => (int)$player['total_bet'],
            'cards' => ($isSelf || ($revealCards && (int)$player['in_hand'] === 1 && (int)$player['folded'] === 0)) ? $cards : array(),
            'card_count' => count($cards),
            'is_self' => $isSelf,
            'is_bot' => szuIsBot($player),
        );
    }

    $clientState = $state;
    unset($clientState['deck'], $clientState['acted'], $clientState['log'], $clientState['afk'], $clientState['bot_act_at']);
    $clientState['paused'] = !empty($state['paused']);
    $clientState['afk_self'] = (int)($state['afk'][(string)$userId] ?? 0) >= 2;

    return array(
        'table' => array(
            'id' => (int)$table['id'],
            'name' => $table['name'],
            'status' => $table['status'],
            'small_blind' => (int)$table['small_blind'],
            'big_blind' => (int)$table['big_blind'],
        ),
        'state' => $clientState,
        'players' => $public,
        'my_turn' => (int)($state['turn_user_id'] ?? 0) === $userId && $table['status'] === 'playing',
        'my_user_id' => $userId,
        'server_time' => time(),
    );
}

function pokerDefaultState(): array
{
    return array(
        'hand_no' => 0,
        'phase' => 'waiting',
        'dealer_seat' => 1,
        'turn_user_id' => null,
        'turn_started_at' => null,
        'turn_deadline_at' => null,
        'current_bet' => 0,
        'min_raise' => BIG_BLIND,
        'pot' => 0,
        'board' => array(),
        'deck' => array(),
        'acted' => array(),
        'messages' => array('Oczekiwanie na graczy.'),
        'last_result' => null,
        'next_hand_at' => null,
        'log' => array(),
        'afk' => array(),
        'paused' => false,
    );
}

/** Ustawia aktywnego gracza i niepodrabialny, serwerowy koniec jego tury. */
function setTurn(array &$state, ?array $player): void
{
    $state['turn_user_id'] = $player ? (int)$player['user_id'] : null;
    $state['turn_started_at'] = $player ? time() : null;
    $state['turn_deadline_at'] = $player ? time() + TURN_SECONDS : null;
    // Komputer wykonuje ruch po krótkim, naturalnym „namyśle”.
    $state['bot_act_at'] = ($player && szuIsBot($player)) ? microtime(true) + szuThinkDelay() : null;
}

function nextSeat(array $players, int $afterSeat, callable $filter): ?array
{
    $sorted = $players;
    usort($sorted, function (array $a, array $b): int { return (int)$a['seat'] <=> (int)$b['seat']; });
    foreach ($sorted as $player) {
        if ((int)$player['seat'] > $afterSeat && $filter($player)) {
            return $player;
        }
    }
    foreach ($sorted as $player) {
        if ($filter($player)) {
            return $player;
        }
    }
    return null;
}

function activeForNewHand(array $players): array
{
    return array_values(array_filter($players, function (array $player): bool {
        return (int)$player['chips'] > 0;
    }));
}

function remainingHandPlayers(array $players): array
{
    return array_values(array_filter($players, function (array $player): bool {
        return (int)$player['in_hand'] === 1 && (int)$player['folded'] === 0;
    }));
}

function actionRequiredPlayers(array $players): array
{
    return array_values(array_filter($players, function (array $player): bool {
        return (int)$player['in_hand'] === 1 && (int)$player['folded'] === 0 && (int)$player['all_in'] === 0;
    }));
}

function createDeck(): array
{
    $deck = array();
    foreach (array('2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A') as $rank) {
        foreach (array('s', 'h', 'd', 'c') as $suit) {
            $deck[] = $rank . $suit;
        }
    }
    for ($i = count($deck) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        $tmp = $deck[$i];
        $deck[$i] = $deck[$j];
        $deck[$j] = $tmp;
    }
    return $deck;
}

function takeCard(array &$state): string
{
    if (empty($state['deck'])) {
        throw new RuntimeException('Talia jest pusta.');
    }
    return array_pop($state['deck']);
}

function saveTableState(PDO $pdo, int $tableId, string $status, array $state): void
{
    $stmt = $pdo->prepare('UPDATE poker_tables SET status = ?, state_json = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?');
    $stmt->execute(array($status, json_encode($state, JSON_UNESCAPED_UNICODE), $tableId));
}

function syncPlayerChips(PDO $pdo, array $players): void
{
    $stmt = $pdo->prepare('UPDATE users SET chips = ? WHERE id = ?');
    foreach ($players as $player) {
        $stmt->execute(array((int)$player['chips'], (int)$player['user_id']));
    }
}

function updatePlayers(PDO $pdo, array $players): void
{
    $stmt = $pdo->prepare(
        'UPDATE table_players SET chips = ?, in_hand = ?, folded = ?, all_in = ?, current_bet = ?, total_bet = ?, hole_cards_json = ? WHERE id = ?'
    );
    foreach ($players as $player) {
        $stmt->execute(array(
            (int)$player['chips'], (int)$player['in_hand'], (int)$player['folded'], (int)$player['all_in'],
            (int)$player['current_bet'], (int)$player['total_bet'], $player['hole_cards_json'], (int)$player['id']
        ));
    }
}

function postBlind(array &$player, int $amount): int
{
    $paid = min((int)$player['chips'], $amount);
    $player['chips'] -= $paid;
    $player['current_bet'] += $paid;
    $player['total_bet'] += $paid;
    if ((int)$player['chips'] === 0) {
        $player['all_in'] = 1;
    }
    return $paid;
}

function startNewHand(PDO $pdo, int $tableId, array $table, array $players, array $extraMessages = array()): array
{
    $eligible = activeForNewHand($players);
    if (count($eligible) < 2) {
        $state = pokerDefaultState();
        $state['messages'] = array('Oczekiwanie na graczy.');
        saveTableState($pdo, $tableId, 'waiting', $state);
        return $state;
    }

    $previous = json_decode((string)$table['state_json'], true) ?: pokerDefaultState();
    $previousDealer = (int)($previous['dealer_seat'] ?? 0);
    $dealer = nextSeat($eligible, $previousDealer, function (array $player): bool { return true; });
    if ($dealer === null) {
        $dealer = $eligible[0];
    }

    $state = pokerDefaultState();
    $state['hand_no'] = (int)($previous['hand_no'] ?? 0) + 1;
    $state['phase'] = 'preflop';
    $state['dealer_seat'] = (int)$dealer['seat'];
    $state['deck'] = createDeck();
    $state['messages'] = array('Rozdanie #' . $state['hand_no'] . ' rozpoczęte.');
    $state['afk'] = is_array($previous['afk'] ?? null) ? $previous['afk'] : array();
    $state['szu_table'] = !empty($previous['szu_table']);
    foreach ($extraMessages as $extraMessage) { $state['messages'][] = (string)$extraMessage; }

    foreach ($players as &$player) {
        $in = (int)$player['chips'] > 0;
        $player['in_hand'] = $in ? 1 : 0;
        $player['folded'] = 0;
        $player['all_in'] = 0;
        $player['current_bet'] = 0;
        $player['total_bet'] = 0;
        $player['hole_cards_json'] = $in ? json_encode(array(takeCard($state), takeCard($state))) : '[]';
    }
    unset($player);

    $handPlayers = remainingHandPlayers($players);
    if (count($handPlayers) === 2) {
        $smallBlindPlayer = $dealer;
        $bigBlindPlayer = nextSeat($handPlayers, (int)$dealer['seat'], function (array $player): bool { return true; });
    } else {
        $smallBlindPlayer = nextSeat($handPlayers, (int)$dealer['seat'], function (array $player): bool { return true; });
        $bigBlindPlayer = nextSeat($handPlayers, (int)$smallBlindPlayer['seat'], function (array $player): bool { return true; });
    }

    foreach ($players as &$player) {
        if ((int)$player['id'] === (int)$smallBlindPlayer['id']) {
            postBlind($player, (int)$table['small_blind']);
            $state['messages'][] = $player['username'] . ' wpłaca małą ciemną.';
            $state['log'][] = array((int)$player['user_id'], 'preflop', 'blind', (int)$player['current_bet'], 0);
        }
        if ((int)$player['id'] === (int)$bigBlindPlayer['id']) {
            postBlind($player, (int)$table['big_blind']);
            $state['messages'][] = $player['username'] . ' wpłaca dużą ciemną.';
            $state['log'][] = array((int)$player['user_id'], 'preflop', 'blind', (int)$player['current_bet'], 0);
        }
    }
    unset($player);

    $state['current_bet'] = (int)$table['big_blind'];
    $state['min_raise'] = (int)$table['big_blind'];
    $state['pot'] = array_sum(array_map(function (array $p): int { return (int)$p['total_bet']; }, $players));
    $state['acted'] = array();

    $next = nextSeat($handPlayers, (int)$bigBlindPlayer['seat'], function (array $player): bool {
        return (int)$player['all_in'] === 0;
    });
    setTurn($state, $next);
    updatePlayers($pdo, $players);
    syncPlayerChips($pdo, $players);
    saveTableState($pdo, $tableId, 'playing', $state);
    return $state;
}

/**
 * Najwyższy możliwy pięciokartowy układ z 5–7 kart.
 * Zwraca wektor porównawczy, w którym większa liczba oznacza silniejszy układ.
 * @return array{score:array<int,int>,name:string,best:array<int,string>}
 */
function evaluateHand(array $cards): array
{
    if (count($cards) < 5) {
        return array('score' => array(0), 'name' => 'Brak układu', 'best' => $cards);
    }
    $best = null;
    $n = count($cards);
    for ($a = 0; $a < $n - 4; $a++) {
        for ($b = $a + 1; $b < $n - 3; $b++) {
            for ($c = $b + 1; $c < $n - 2; $c++) {
                for ($d = $c + 1; $d < $n - 1; $d++) {
                    for ($e = $d + 1; $e < $n; $e++) {
                        $five = array($cards[$a], $cards[$b], $cards[$c], $cards[$d], $cards[$e]);
                        $candidate = evaluateFiveCards($five);
                        if ($best === null || compareScores($candidate['score'], $best['score']) > 0) {
                            $best = $candidate;
                        }
                    }
                }
            }
        }
    }
    return $best;
}

/** @return array{score:array<int,int>,name:string,best:array<int,string>} */
function evaluateFiveCards(array $cards): array
{
    $rankMap = array('2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'T'=>10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14);
    $ranks = array_map(function (string $card) use ($rankMap): int { return $rankMap[substr($card, 0, -1)]; }, $cards);
    rsort($ranks, SORT_NUMERIC);
    $counts = array_count_values($ranks);
    $groups = array();
    foreach ($counts as $rank => $count) {
        $groups[] = array('rank' => (int)$rank, 'count' => (int)$count);
    }
    usort($groups, function (array $a, array $b): int {
        return $b['count'] <=> $a['count'] ?: $b['rank'] <=> $a['rank'];
    });
    $flush = count(array_unique(array_map(function (string $card): string { return substr($card, -1); }, $cards))) === 1;
    $unique = array_values(array_unique($ranks));
    sort($unique, SORT_NUMERIC);
    $straightHigh = 0;
    if (count($unique) === 5) {
        if ($unique === array(2, 3, 4, 5, 14)) {
            $straightHigh = 5;
        } elseif ($unique[4] - $unique[0] === 4) {
            $straightHigh = $unique[4];
        }
    }
    if ($flush && $straightHigh > 0) {
        return array('score' => array(8, $straightHigh), 'name' => $straightHigh === 14 ? 'Poker królewski' : 'Poker', 'best' => $cards);
    }
    if ($groups[0]['count'] === 4) {
        return array('score' => array(7, $groups[0]['rank'], $groups[1]['rank']), 'name' => 'Kareta', 'best' => $cards);
    }
    if ($groups[0]['count'] === 3 && $groups[1]['count'] === 2) {
        return array('score' => array(6, $groups[0]['rank'], $groups[1]['rank']), 'name' => 'Full', 'best' => $cards);
    }
    if ($flush) {
        return array('score' => array_merge(array(5), $ranks), 'name' => 'Kolor', 'best' => $cards);
    }
    if ($straightHigh > 0) {
        return array('score' => array(4, $straightHigh), 'name' => 'Strit', 'best' => $cards);
    }
    if ($groups[0]['count'] === 3) {
        $kickers = array();
        foreach ($groups as $group) { if ($group['count'] === 1) { $kickers[] = $group['rank']; } }
        rsort($kickers, SORT_NUMERIC);
        return array('score' => array_merge(array(3, $groups[0]['rank']), $kickers), 'name' => 'Trójka', 'best' => $cards);
    }
    if ($groups[0]['count'] === 2 && $groups[1]['count'] === 2) {
        return array('score' => array(2, $groups[0]['rank'], $groups[1]['rank'], $groups[2]['rank']), 'name' => 'Dwie pary', 'best' => $cards);
    }
    if ($groups[0]['count'] === 2) {
        $kickers = array();
        foreach ($groups as $group) { if ($group['count'] === 1) { $kickers[] = $group['rank']; } }
        rsort($kickers, SORT_NUMERIC);
        return array('score' => array_merge(array(1, $groups[0]['rank']), $kickers), 'name' => 'Para', 'best' => $cards);
    }
    return array('score' => array_merge(array(0), $ranks), 'name' => 'Wysoka karta', 'best' => $cards);
}

function compareScores(array $left, array $right): int
{
    $max = max(count($left), count($right));
    for ($i = 0; $i < $max; $i++) {
        $a = (int)($left[$i] ?? 0);
        $b = (int)($right[$i] ?? 0);
        if ($a !== $b) {
            return $a <=> $b;
        }
    }
    return 0;
}

function advanceStreet(PDO $pdo, int $tableId, array &$state, array &$players): void
{
    foreach ($players as &$player) {
        $player['current_bet'] = 0;
    }
    unset($player);
    $state['current_bet'] = 0;
    $state['min_raise'] = BIG_BLIND;
    $state['acted'] = array();

    if ($state['phase'] === 'preflop') {
        takeCard($state); // spalona karta
        $state['board'][] = takeCard($state);
        $state['board'][] = takeCard($state);
        $state['board'][] = takeCard($state);
        $state['phase'] = 'flop';
        $state['messages'][] = 'Flop.';
    } elseif ($state['phase'] === 'flop') {
        takeCard($state);
        $state['board'][] = takeCard($state);
        $state['phase'] = 'turn';
        $state['messages'][] = 'Turn.';
    } elseif ($state['phase'] === 'turn') {
        takeCard($state);
        $state['board'][] = takeCard($state);
        $state['phase'] = 'river';
        $state['messages'][] = 'River.';
    } else {
        finishShowdown($pdo, $tableId, $state, $players);
        return;
    }

    $eligible = actionRequiredPlayers($players);
    if (count($eligible) === 0) {
        advanceStreet($pdo, $tableId, $state, $players);
        return;
    }
    $next = nextSeat($eligible, (int)$state['dealer_seat'], function (array $p): bool { return true; });
    setTurn($state, $next);
    updatePlayers($pdo, $players);
    syncPlayerChips($pdo, $players);
    saveTableState($pdo, $tableId, 'playing', $state);
}

function finishUncontested(PDO $pdo, int $tableId, array &$state, array &$players): void
{
    $remaining = remainingHandPlayers($players);
    if (count($remaining) !== 1) {
        throw new RuntimeException('Nie można rozstrzygnąć puli.');
    }
    $winnerId = (int)$remaining[0]['user_id'];
    $pot = array_sum(array_map(function (array $p): int { return (int)$p['total_bet']; }, $players));
    foreach ($players as &$player) {
        if ((int)$player['user_id'] === $winnerId) {
            $player['chips'] += $pot;
        }
    }
    unset($player);
    $state['pot'] = 0;
    setTurn($state, null);
    $state['phase'] = 'result';
    $state['last_result'] = array('type' => 'fold', 'winners' => array(array('user_id' => $winnerId, 'amount' => $pot, 'hand' => 'Wszyscy przeciwnicy spasowali')), 'pots' => array($pot));
    $state['messages'][] = $remaining[0]['username'] . ' wygrywa ' . formatChips($pot) . ', ponieważ pozostali spasowali.';
    $state['next_hand_at'] = time() + 8;
    szuRecordHand($pdo, $state, $players);
    updatePlayers($pdo, $players);
    syncPlayerChips($pdo, $players);
    saveTableState($pdo, $tableId, 'showdown', $state);
}

function finishShowdown(PDO $pdo, int $tableId, array &$state, array &$players): void
{
    $levels = array();
    foreach ($players as $player) {
        if ((int)$player['total_bet'] > 0) {
            $levels[] = (int)$player['total_bet'];
        }
    }
    sort($levels, SORT_NUMERIC);
    $levels = array_values(array_unique($levels));
    $previous = 0;
    $resultWinners = array();
    $potAmounts = array();
    foreach ($levels as $level) {
        $contributors = array_values(array_filter($players, function (array $p) use ($level): bool { return (int)$p['total_bet'] >= $level; }));
        $amount = ($level - $previous) * count($contributors);
        $previous = $level;
        if ($amount <= 0) {
            continue;
        }
        $eligible = array_values(array_filter($contributors, function (array $p): bool { return (int)$p['in_hand'] === 1 && (int)$p['folded'] === 0; }));
        if (!$eligible) {
            continue;
        }
        $evaluated = array();
        foreach ($eligible as $player) {
            $cards = array_merge(json_decode((string)$player['hole_cards_json'], true) ?: array(), $state['board']);
            $evaluated[(int)$player['user_id']] = evaluateHand($cards);
        }
        $bestScore = null;
        $winnerIds = array();
        foreach ($eligible as $player) {
            $id = (int)$player['user_id'];
            if ($bestScore === null || compareScores($evaluated[$id]['score'], $bestScore) > 0) {
                $bestScore = $evaluated[$id]['score'];
                $winnerIds = array($id);
            } elseif (compareScores($evaluated[$id]['score'], $bestScore) === 0) {
                $winnerIds[] = $id;
            }
        }
        $share = intdiv($amount, count($winnerIds));
        $remainder = $amount % count($winnerIds);
        foreach ($players as &$player) {
            if (in_array((int)$player['user_id'], $winnerIds, true)) {
                $bonus = $remainder > 0 ? 1 : 0;
                $player['chips'] += $share + $bonus;
                if ($remainder > 0) { $remainder--; }
            }
        }
        unset($player);
        foreach ($winnerIds as $winnerId) {
            $resultWinners[] = array(
                'user_id' => $winnerId,
                'amount' => $share,
                'hand' => $evaluated[$winnerId]['name'],
            );
        }
        $potAmounts[] = $amount;
    }
    $state['pot'] = 0;
    setTurn($state, null);
    $state['phase'] = 'result';
    $state['last_result'] = array('type' => 'showdown', 'winners' => $resultWinners, 'pots' => $potAmounts);
    $state['messages'][] = 'Odkrycie kart i rozstrzygnięcie puli.';
    $state['next_hand_at'] = time() + 10;
    szuRecordHand($pdo, $state, $players);
    updatePlayers($pdo, $players);
    syncPlayerChips($pdo, $players);
    saveTableState($pdo, $tableId, 'showdown', $state);
}

/** Rozstrzyga automatyczną akcję po upływie czasu: czekanie bez zakładu albo pas przy zakładzie do wyrównania. */
function timeoutCurrentTurn(PDO $pdo, int $tableId, array &$state, array &$players): void
{
    $userId = (int)($state['turn_user_id'] ?? 0);
    $playerIndex = null;
    foreach ($players as $index => $player) {
        if ((int)$player['user_id'] === $userId) {
            $playerIndex = $index;
            break;
        }
    }
    if ($playerIndex === null) {
        setTurn($state, null);
        saveTableState($pdo, $tableId, 'playing', $state);
        return;
    }

    $toCall = max(0, (int)$state['current_bet'] - (int)$players[$playerIndex]['current_bet']);
    $name = (string)$players[$playerIndex]['username'];
    if ($toCall > 0) {
        $players[$playerIndex]['folded'] = 1;
        $state['messages'][] = 'Czas minął — ' . $name . ' automatycznie pasuje.';
    } else {
        $state['messages'][] = 'Czas minął — ' . $name . ' automatycznie czeka.';
    }
    $state['acted'][(string)$userId] = true;
    $state['log'][] = array($userId, (string)$state['phase'], $toCall > 0 ? 'fold' : 'check', 0, $toCall);
    if (!szuIsBot($players[$playerIndex])) {
        $state['afk'][(string)$userId] = (int)($state['afk'][(string)$userId] ?? 0) + 1;
    }
    $state['pot'] = array_sum(array_map(function (array $p): int { return (int)$p['total_bet']; }, $players));

    $remaining = remainingHandPlayers($players);
    if (count($remaining) === 1) {
        finishUncontested($pdo, $tableId, $state, $players);
        return;
    }
    $required = actionRequiredPlayers($players);
    $roundDone = true;
    foreach ($required as $requiredPlayer) {
        $id = (string)$requiredPlayer['user_id'];
        if ((int)$requiredPlayer['current_bet'] !== (int)$state['current_bet'] || empty($state['acted'][$id])) {
            $roundDone = false;
            break;
        }
    }
    if ($roundDone) {
        advanceStreet($pdo, $tableId, $state, $players);
        return;
    }
    $next = nextSeat($players, (int)$players[$playerIndex]['seat'], function (array $candidate): bool {
        return (int)$candidate['in_hand'] === 1 && (int)$candidate['folded'] === 0 && (int)$candidate['all_in'] === 0;
    });
    setTurn($state, $next);
    updatePlayers($pdo, $players);
    syncPlayerChips($pdo, $players);
    saveTableState($pdo, $tableId, 'playing', $state);
}

function tickTable(PDO $pdo, int $tableId): void
{
    $pdo->beginTransaction();
    try {
        $tableStmt = $pdo->prepare('SELECT * FROM poker_tables WHERE id = ? FOR UPDATE');
        $tableStmt->execute(array($tableId));
        $table = $tableStmt->fetch();
        if (!$table) {
            throw new RuntimeException('Stół nie istnieje.');
        }
        $playersStmt = $pdo->prepare('SELECT tp.*, u.username, u.auth_type FROM table_players tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.table_id = ? ORDER BY tp.seat FOR UPDATE');
        $playersStmt->execute(array($tableId));
        $players = $playersStmt->fetchAll();
        szuAliasPlayers($players);
        $state = json_decode((string)$table['state_json'], true) ?: pokerDefaultState();
        $status = (string)$table['status'];

        $bots = 0; $humans = 0; $humansWithChips = 0;
        foreach ($players as $player) {
            if (szuIsBot($player)) { $bots++; continue; }
            $humans++;
            if ((int)$player['chips'] > 0) { $humansWithChips++; }
        }

        // Wielki Szu nie gra sam ze sobą — po odejściu ludzi zwalnia miejsca.
        if ($bots > 0 && $humans === 0 && $status !== 'playing') {
            $pdo->prepare("DELETE tp FROM table_players tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.table_id = ? AND u.auth_type = 'bot'")->execute(array($tableId));
            $fresh = pokerDefaultState();
            $fresh['hand_no'] = (int)($state['hand_no'] ?? 0);
            $fresh['szu_table'] = !empty($state['szu_table']);
            saveTableState($pdo, $tableId, 'waiting', $fresh);
            $pdo->commit();
            return;
        }

        $turnUserId = (int)($state['turn_user_id'] ?? 0);
        $turnPlayer = null;
        foreach ($players as $player) {
            if ((int)$player['user_id'] === $turnUserId) { $turnPlayer = $player; break; }
        }
        $botTurn = $status === 'playing' && $turnPlayer !== null && szuIsBot($turnPlayer);
        $turnMissingDeadline = $status === 'playing' && $turnUserId > 0 && empty($state['turn_deadline_at']);
        $turnExpired = $status === 'playing' && $turnUserId > 0 && !empty($state['turn_deadline_at']) && time() >= (int)$state['turn_deadline_at'];
        $shouldStart = $status === 'waiting' && count(activeForNewHand($players)) >= 2;
        $shouldRestart = $status === 'showdown' && !empty($state['next_hand_at']) && time() >= (int)$state['next_hand_at'];

        if ($botTurn) {
            if (empty($state['bot_act_at'])) {
                $state['bot_act_at'] = microtime(true) + szuThinkDelay();
                saveTableState($pdo, $tableId, 'playing', $state);
            } elseif (microtime(true) >= (float)$state['bot_act_at']) {
                szuTakeTurn($pdo, $tableId, $table, $state, $players);
            }
        } elseif ($turnExpired) {
            timeoutCurrentTurn($pdo, $tableId, $state, $players);
        } elseif ($turnMissingDeadline) {
            setTurn($state, $turnPlayer);
            saveTableState($pdo, $tableId, 'playing', $state);
        } elseif ($shouldStart || $shouldRestart) {
            $extra = array();
            if ($bots > 0) {
                if ($humansWithChips === 0) {
                    // Człowiek bez punktów — komputer nie rozgrywa rąk sam ze sobą.
                    if ($status !== 'waiting' || empty($state['paused'])) {
                        $wait = pokerDefaultState();
                        $wait['hand_no'] = (int)($state['hand_no'] ?? 0);
                        $wait['dealer_seat'] = (int)($state['dealer_seat'] ?? 1);
                        $wait['afk'] = $state['afk'] ?? array();
                        $wait['paused'] = true;
                        $wait['szu_table'] = !empty($state['szu_table']);
                        $wait['messages'] = array('Brak punktów.');
                        saveTableState($pdo, $tableId, 'waiting', $wait);
                    }
                    $pdo->commit();
                    return;
                }
                if (szuActiveHumans($players, $state) === 0) {
                    // Gracz nie wykonuje ruchów — wstrzymujemy grę, aby nie tracił punktów pod nieobecność.
                    if ($status !== 'waiting' || empty($state['paused'])) {
                        $wait = pokerDefaultState();
                        $wait['hand_no'] = (int)($state['hand_no'] ?? 0);
                        $wait['dealer_seat'] = (int)($state['dealer_seat'] ?? 1);
                        $wait['afk'] = $state['afk'] ?? array();
                        $wait['paused'] = true;
                        $wait['szu_table'] = !empty($state['szu_table']);
                        $wait['messages'] = array('Pauza.');
                        saveTableState($pdo, $tableId, 'waiting', $wait);
                    }
                    $pdo->commit();
                    return;
                }
                szuRebuyBots($pdo, $players, $extra);
            }
            startNewHand($pdo, $tableId, $table, $players, $extra);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $exception;
    }
}

/** Gracz wraca po nieaktywności — zdejmujemy wstrzymanie stołu. */
function resumePlayer(PDO $pdo, int $tableId, int $userId): void
{
    $pdo->beginTransaction();
    try {
        $tableStmt = $pdo->prepare('SELECT * FROM poker_tables WHERE id = ? FOR UPDATE');
        $tableStmt->execute(array($tableId));
        $table = $tableStmt->fetch();
        if (!$table) {
            throw new RuntimeException('Stół nie istnieje.');
        }
        $state = json_decode((string)$table['state_json'], true) ?: pokerDefaultState();
        unset($state['afk'][(string)$userId]);
        if ((string)$table['status'] === 'waiting') {
            $state['paused'] = false;
            $state['messages'] = array('Wracamy do gry.');
        }
        saveTableState($pdo, $tableId, (string)$table['status'], $state);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $exception;
    }
}

function makePlayerAction(PDO $pdo, int $tableId, int $userId, string $action, int $raiseTo = 0): void
{
    $pdo->beginTransaction();
    try {
        $tableStmt = $pdo->prepare('SELECT * FROM poker_tables WHERE id = ? FOR UPDATE');
        $tableStmt->execute(array($tableId));
        $table = $tableStmt->fetch();
        if (!$table || $table['status'] !== 'playing') {
            throw new RuntimeException('To rozdanie nie jest już aktywne.');
        }
        $playersStmt = $pdo->prepare('SELECT tp.*, u.username, u.auth_type FROM table_players tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.table_id = ? ORDER BY tp.seat FOR UPDATE');
        $playersStmt->execute(array($tableId));
        $players = $playersStmt->fetchAll();
        szuAliasPlayers($players);
        $state = json_decode((string)$table['state_json'], true) ?: pokerDefaultState();
        applyPlayerAction($pdo, $tableId, $state, $players, $userId, $action, $raiseTo);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $exception;
    }
}

/**
 * Wspólna logika ruchu (człowiek i Wielki Szu podlegają identycznym regułom).
 * Wywoływana wewnątrz transakcji z zablokowanym stołem.
 */
function applyPlayerAction(PDO $pdo, int $tableId, array &$state, array &$players, int $userId, string $action, int $raiseTo = 0): void
{
    if ((int)($state['turn_user_id'] ?? 0) !== $userId) {
        throw new RuntimeException('Poczekaj na swoją kolej.');
    }
    $playerIndex = null;
    foreach ($players as $index => $player) {
        if ((int)$player['user_id'] === $userId) { $playerIndex = $index; break; }
    }
    if ($playerIndex === null || (int)$players[$playerIndex]['folded'] === 1 || (int)$players[$playerIndex]['all_in'] === 1) {
        throw new RuntimeException('Nie możesz wykonać tej akcji.');
    }
    $player = &$players[$playerIndex];
    $toCall = max(0, (int)$state['current_bet'] - (int)$player['current_bet']);
    $name = (string)$player['username'];
    $logAmount = 0;

    if ($action === 'fold') {
        $player['folded'] = 1;
        $state['messages'][] = $name . ' pasuje.';
    } elseif ($action === 'check') {
        if ($toCall !== 0) { throw new RuntimeException('Nie możesz czekać — do wyrównania jest zakład.'); }
        $state['messages'][] = $name . ' czeka.';
    } elseif ($action === 'call') {
        if ($toCall === 0) { throw new RuntimeException('Nie ma zakładu do sprawdzenia. Wybierz czekanie.'); }
        $paid = postBlind($player, $toCall);
        $logAmount = $paid;
        $state['messages'][] = $name . ($paid < $toCall ? ' wchodzi za wszystko.' : ' sprawdza.');
    } elseif ($action === 'raise') {
        $maximum = (int)$player['current_bet'] + (int)$player['chips'];
        if ($raiseTo <= (int)$state['current_bet'] || $raiseTo > $maximum) {
            throw new RuntimeException('Podaj prawidłową wysokość podbicia.');
        }
        $raiseSize = $raiseTo - (int)$state['current_bet'];
        if ($raiseSize < (int)$state['min_raise'] && $raiseTo !== $maximum) {
            throw new RuntimeException('Podbicie musi wynosić co najmniej ' . formatChips((int)$state['min_raise']) . '.');
        }
        $paid = $raiseTo - (int)$player['current_bet'];
        postBlind($player, $paid);
        $state['current_bet'] = $raiseTo;
        if ($raiseSize >= (int)$state['min_raise']) { $state['min_raise'] = $raiseSize; }
        $state['acted'] = array();
        $logAmount = $raiseTo;
        $state['messages'][] = $name . ($player['all_in'] ? ' wchodzi za wszystko do ' : ' podbija do ') . formatChips($raiseTo) . '.';
    } else {
        throw new RuntimeException('Nieznana akcja.');
    }
    $isBot = szuIsBot($player);
    unset($player);
    $state['acted'][(string)$userId] = true;
    $state['log'][] = array($userId, (string)$state['phase'], $action, $logAmount, $toCall);
    if (!$isBot) {
        unset($state['afk'][(string)$userId]);
    }
    $state['pot'] = array_sum(array_map(function (array $p): int { return (int)$p['total_bet']; }, $players));

    $remaining = remainingHandPlayers($players);
    if (count($remaining) === 1) {
        finishUncontested($pdo, $tableId, $state, $players);
        return;
    }
    $required = actionRequiredPlayers($players);
    $roundDone = true;
    foreach ($required as $requiredPlayer) {
        $id = (string)$requiredPlayer['user_id'];
        if ((int)$requiredPlayer['current_bet'] !== (int)$state['current_bet'] || empty($state['acted'][$id])) {
            $roundDone = false;
            break;
        }
    }
    if ($roundDone) {
        advanceStreet($pdo, $tableId, $state, $players);
    } else {
        $currentSeat = (int)$players[$playerIndex]['seat'];
        $next = nextSeat($players, $currentSeat, function (array $candidate): bool {
            return (int)$candidate['in_hand'] === 1 && (int)$candidate['folded'] === 0 && (int)$candidate['all_in'] === 0;
        });
        setTurn($state, $next);
        updatePlayers($pdo, $players);
        syncPlayerChips($pdo, $players);
        saveTableState($pdo, $tableId, 'playing', $state);
    }
}
?>
