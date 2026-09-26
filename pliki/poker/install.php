<?php
declare(strict_types=1);

$configPath = __DIR__ . '/config.php';
$current = is_file($configPath) ? require $configPath : array('installed' => false);
if (!empty($current['installed'])) {
    header('Location: index.php');
    exit;
}

$errors = array();
$success = false;
$values = array('db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '');

// Kontrola środowiska hostingu — wyświetlana w instalatorze, bez potrzeby konsoli.
$checks = array(
    array('label' => 'PHP 7.4 lub nowsze', 'value' => PHP_VERSION, 'state' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'bad'),
    array('label' => 'Rozszerzenie PDO MySQL', 'value' => extension_loaded('pdo_mysql') ? 'dostępne' : 'brak', 'state' => extension_loaded('pdo_mysql') ? 'ok' : 'bad'),
    array('label' => 'Rozszerzenie JSON', 'value' => function_exists('json_encode') ? 'dostępne' : 'brak', 'state' => function_exists('json_encode') ? 'ok' : 'bad'),
    array('label' => 'Zapis pliku config.php', 'value' => (is_writable($configPath) || (!is_file($configPath) && is_writable(__DIR__))) ? 'możliwy' : 'zablokowany', 'state' => (is_writable($configPath) || (!is_file($configPath) && is_writable(__DIR__))) ? 'ok' : 'warn'),
);
$blocking = false;
foreach ($checks as $check) {
    if ($check['state'] === 'bad') { $blocking = true; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        $values[$key] = trim((string)($_POST[$key] ?? $default));
    }
    if ($values['db_host'] === '' || $values['db_name'] === '' || $values['db_user'] === '') {
        $errors[] = 'Uzupełnij adres serwera, nazwę bazy i użytkownika bazy danych.';
    }
    if ($blocking) {
        $errors[] = 'Serwer nie spełnia wymagań (patrz lista powyżej). Włącz brakujące elementy w panelu hostingu, np. w ustawieniach wersji PHP.';
    }
    if (!$errors) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $values['db_host'], $values['db_name']),
                $values['db_user'],
                $values['db_pass'],
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
            );
            $schema = array(
                "CREATE TABLE IF NOT EXISTS users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(32) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NULL,
                    auth_type ENUM('guest','reserved','site','bot') NOT NULL DEFAULT 'guest',
                    site_uid VARCHAR(40) NULL DEFAULT NULL,
                    chips INT NOT NULL DEFAULT 5000,
                    last_refill_at DATETIME NOT NULL,
                    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_users_guest_activity (auth_type, last_activity_at),
                    UNIQUE KEY uq_users_site_uid (site_uid)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS poker_tables (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(32) NOT NULL,
                    status ENUM('waiting','playing','showdown','finished') NOT NULL DEFAULT 'waiting',
                    small_blind INT NOT NULL DEFAULT 10,
                    big_blind INT NOT NULL DEFAULT 20,
                    state_json LONGTEXT NOT NULL,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS table_players (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    table_id INT UNSIGNED NOT NULL,
                    user_id INT UNSIGNED NOT NULL UNIQUE,
                    seat TINYINT UNSIGNED NOT NULL,
                    chips INT NOT NULL DEFAULT 5000,
                    in_hand TINYINT(1) NOT NULL DEFAULT 0,
                    folded TINYINT(1) NOT NULL DEFAULT 0,
                    all_in TINYINT(1) NOT NULL DEFAULT 0,
                    current_bet INT NOT NULL DEFAULT 0,
                    total_bet INT NOT NULL DEFAULT 0,
                    hole_cards_json VARCHAR(64) NOT NULL DEFAULT '[]',
                    UNIQUE KEY unique_table_seat (table_id, seat),
                    CONSTRAINT fk_players_table FOREIGN KEY (table_id) REFERENCES poker_tables(id) ON DELETE CASCADE,
                    CONSTRAINT fk_players_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS hand_history (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    table_id INT UNSIGNED NOT NULL,
                    hand_no INT NOT NULL,
                    payload_json LONGTEXT NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_history_table_hand (table_id, hand_no)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS poker_meta (
                    meta_key VARCHAR(64) NOT NULL PRIMARY KEY,
                    meta_value VARCHAR(255) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS poker_chat_invites (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    table_id INT UNSIGNED NOT NULL,
                    user_id INT UNSIGNED NOT NULL,
                    created_at DATETIME NOT NULL,
                    KEY idx_invites_table (table_id, created_at),
                    KEY idx_invites_user (user_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS poker_szu_stats (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            foreach ($schema as $query) {
                $pdo->exec($query);
            }
            // Migracja instalacji utworzonych przed trybem gościa/rezerwacji nicku.
            $migrations = array(
                "ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL",
                "ALTER TABLE users ADD COLUMN auth_type ENUM('guest','reserved') NOT NULL DEFAULT 'guest' AFTER password_hash",
                "ALTER TABLE users ADD COLUMN last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER last_refill_at",
                "ALTER TABLE users ADD KEY idx_users_guest_activity (auth_type, last_activity_at)",
                "UPDATE users SET auth_type = 'reserved' WHERE password_hash IS NOT NULL AND password_hash <> ''",
                "UPDATE users SET last_activity_at = UTC_TIMESTAMP() WHERE last_activity_at IS NULL",
                // Wspólne logowanie z serwisem nadrzędnym (v7).
                "ALTER TABLE users MODIFY username VARCHAR(32) NOT NULL",
                "ALTER TABLE users MODIFY auth_type ENUM('guest','reserved','site','bot') NOT NULL DEFAULT 'guest'",
                "ALTER TABLE users ADD COLUMN site_uid VARCHAR(40) NULL DEFAULT NULL AFTER auth_type",
                "ALTER TABLE users ADD UNIQUE KEY uq_users_site_uid (site_uid)",
            );
            foreach ($migrations as $query) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $exception) {
                    // Istniejące kolumny/indeksy są prawidłowym stanem przy ponownym uruchomieniu instalatora.
                    if (!in_array((int)$exception->errorInfo[1], array(1060, 1061), true)) {
                        throw $exception;
                    }
                }
            }
            $tableCount = (int)$pdo->query('SELECT COUNT(*) FROM poker_tables')->fetchColumn();
            if ($tableCount === 0) {
                $state = json_encode(array(
                    'hand_no' => 0,
                    'phase' => 'waiting',
                    'dealer_seat' => 1,
                    'turn_user_id' => null,
                    'current_bet' => 0,
                    'min_raise' => 20,
                    'pot' => 0,
                    'board' => array(),
                    'deck' => array(),
                    'acted' => array(),
                    'messages' => array('Oczekiwanie na co najmniej dwóch graczy.'),
                    'last_result' => null,
                    'next_hand_at' => null,
                ), JSON_UNESCAPED_UNICODE);
                $seed = $pdo->prepare('INSERT INTO poker_tables (name, state_json) VALUES (?, ?)');
                $seed->execute(array('Stół Warszawa', $state));
            }

            $cleanupKey = bin2hex(random_bytes(24));
            $config = "<?php\nreturn array(\n"
                . "    'installed' => true,\n"
                . "    'db_host' => " . var_export($values['db_host'], true) . ",\n"
                . "    'db_name' => " . var_export($values['db_name'], true) . ",\n"
                . "    'db_user' => " . var_export($values['db_user'], true) . ",\n"
                . "    'db_pass' => " . var_export($values['db_pass'], true) . ",\n"
                . "    'cleanup_key' => " . var_export($cleanupKey, true) . ",\n"
                . ");\n?>\n";
            if (file_put_contents($configPath, $config, LOCK_EX) === false) {
                throw new RuntimeException('Baza została przygotowana, ale serwer nie pozwolił zapisać config.php. Nadaj katalogowi tymczasowe uprawnienie do zapisu (np. 755/775), odśwież instalator i spróbuj ponownie.');
            }
            // Wymusza odczyt świeżego config.php także na hostingach z aktywnym OPcache.
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($configPath, true);
            }
            $success = true;
        } catch (Throwable $exception) {
            $errors[] = 'Instalacja nie powiodła się: ' . $exception->getMessage();
        }
    }
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Instalacja — Poker Polski</title>
  <script src="assets/theme.js?v=7.0.0"></script>
<?php if (is_file(dirname(__DIR__) . '/assets/css/fonts.css')): ?>
  <link rel="stylesheet" href="../assets/css/fonts.css">
<?php endif; ?>
  <link rel="stylesheet" href="assets/style.css?v=7.0.0">
</head>
<body>
  <main class="install-page">
    <div class="install-card">
      <div class="brand"><span class="brand-mark">♠</span><span class="brand-name">Poker Polski</span></div>
      <?php if ($success): ?>
        <p class="eyebrow">Gotowe</p>
        <h1>Gra jest zainstalowana</h1>
        <div class="alert success">Utworzono tabele bazy danych, startowy „Stół Warszawa” i plik konfiguracji. Instalator jest teraz automatycznie zablokowany.</div>
        <p class="muted" style="margin-bottom:20px">Dla porządku możesz usunąć plik <code>install.php</code> przez FTP — nie jest to jednak wymagane do działania gry.</p>
        <a class="btn btn-gold btn-lg btn-block" href="index.php" style="text-decoration:none">Przejdź do gry</a>
      <?php else: ?>
        <p class="eyebrow">Instalacja · krok 1 z 1</p>
        <h1>Połącz grę z bazą danych</h1>
        <p class="muted">Wpisz dane bazy MySQL/MariaDB utworzonej w panelu hostingu. Wszystko dzieje się w przeglądarce — bez SSH i konsoli.</p>
        <ul class="checks">
          <?php foreach ($checks as $check): ?>
            <li class="<?= $check['state'] === 'ok' ? '' : h($check['state']) ?>"><?= h($check['label']) ?><b><?= h($check['value']) ?></b></li>
          <?php endforeach; ?>
        </ul>
        <?php foreach ($errors as $error): ?><div class="alert error"><?= h($error) ?></div><?php endforeach; ?>
        <form method="post" class="form-stack" autocomplete="off">
          <label class="field"><span>Adres serwera MySQL</span><input name="db_host" value="<?= h($values['db_host']) ?>" required placeholder="najczęściej localhost"></label>
          <label class="field"><span>Nazwa bazy danych</span><input name="db_name" value="<?= h($values['db_name']) ?>" required></label>
          <div class="field-row">
            <label class="field"><span>Użytkownik bazy</span><input name="db_user" value="<?= h($values['db_user']) ?>" required></label>
            <label class="field"><span>Hasło bazy</span><input type="password" name="db_pass" value="<?= h($values['db_pass']) ?>"></label>
          </div>
          <button class="btn btn-gold btn-lg btn-block" type="submit"<?= $blocking ? ' disabled' : '' ?>>Zainstaluj Poker Polski</button>
        </form>
        <p class="small muted">Dane znajdziesz w panelu hostingu w sekcji „Bazy danych MySQL”. Po instalacji ten formularz zostaje automatycznie zablokowany.</p>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
