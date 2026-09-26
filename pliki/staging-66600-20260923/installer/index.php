<?php
declare(strict_types=1);

/**
 * Instalator przeglądarkowy 66600.PL.
 * Kroki: wymagania -> baza -> adres instalacji -> schemat+migracje -> ziarno
 * -> konto administratora -> prywatna konfiguracja -> blokada instalacji.
 * Dane bazy pozostają wyłącznie w sesji instalatora do momentu zapisu
 * prywatnej konfiguracji. Po zakończeniu tworzony jest install.lock
 * i ponowne otwarcie instalatora jest niemożliwe.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/lib/Requirements.php';
require __DIR__ . '/lib/InstallerState.php';
require __DIR__ . '/lib/MigrationRunner.php';

const INSTALLER_TITLE = 'Instalator 66600.PL';

$installerBaseDir = dirname(__DIR__);
$lockFile = $installerBaseDir . '/install.lock';

function installerBaseUrl(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $directory = str_replace('/index.php', '', $script);
    if ($directory === '' || $directory === '.') {
        $directory = '';
    }
    return $directory;
}

function installerFail(string $message, int $status = 500): never
{
    http_response_code($status);
    installerRender('Błąd', '<p class="flash flash-error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>');
    exit;
}

if (is_file($lockFile)) {
    installerFail('Instalacja została już zakończona.', 404);
}

InstallerState::boot($installerBaseDir);

function installerRender(string $stepTitle, string $content): void
{
    $title = htmlspecialchars(INSTALLER_TITLE, ENT_QUOTES, 'UTF-8');
    $stepSafe = htmlspecialchars($stepTitle, ENT_QUOTES, 'UTF-8');
    $styles = 'body{font-family:system-ui,sans-serif;background:#101820;color:#e8edf2;margin:0;padding:24px}'
        . '.box{max-width:760px;margin:0 auto;background:#18222d;border:1px solid #2a3947;border-radius:10px;padding:28px}'
        . 'h1{font-size:20px;margin:0 0 6px}h2{font-size:16px;margin:22px 0 8px;color:#9fd3ff}'
        . 'label{display:block;margin:10px 0 4px;font-size:14px}input[type=text],input[type=password],input[type=email]{width:100%;padding:9px;border-radius:6px;border:1px solid #35485c;background:#0f1720;color:#e8edf2;box-sizing:border-box}'
        . '.btn{display:inline-block;margin-top:16px;padding:10px 20px;border-radius:6px;border:0;background:#2f81f7;color:#fff;font-size:15px;cursor:pointer;text-decoration:none}'
        . '.flash{padding:12px 14px;border-radius:6px;margin:14px 0;font-size:14px}.flash-error{background:#3a1d24;border:1px solid #7f2c3a}'
        . '.flash-ok{background:#17321f;border:1px solid #2e7d43}.flash-warn{background:#3a2f16;border:1px solid #8a6a1f}'
        . 'table{width:100%;border-collapse:collapse;font-size:14px}td,th{border-bottom:1px solid #2a3947;padding:7px 8px;text-align:left}'
        . '.ok{color:#6fdd8f}.bad{color:#ff8f8f}.opt{color:#e5c66b}code{background:#0f1720;padding:2px 6px;border-radius:4px}';
    echo "<!DOCTYPE html><html lang=\"pl\"><head><meta charset=\"UTF-8\">"
        . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
        . "<meta name=\"robots\" content=\"noindex,nofollow\"><title>{$title}</title>"
        . "<style>{$styles}</style></head><body><div class=\"box\">"
        . "<h1>{$title}</h1><h2>{$stepSafe}</h2>{$content}"
        . '</div></body></html>';
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$base = installerBaseUrl();
$step = InstallerState::currentStep();
$csrf = InstallerState::csrfToken();

// ---------------------------------------------------------------- wymagania
if ($step === 'requirements') {
    $checks = InstallerRequirements::collect($installerBaseDir);
    $blocking = InstallerRequirements::blockingFailed($checks);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        if ($blocking) {
            installerFail('Wymagania serwera nie są spełnione — instalacja nie może być kontynuowana.', 400);
        }
        InstallerState::markDone('requirements');
        InstallerState::setStep('database');
        header('Location: ' . $base . '/index.php', true, 303);
        exit;
    }

    $rows = '';
    foreach ($checks as $check) {
        $class = $check['ok'] ? 'ok' : ($check['optional'] ? 'opt' : 'bad');
        $mark = $check['ok'] ? '✓' : ($check['optional'] ? '~' : '✗');
        $rows .= '<tr><td class="' . $class . '">' . $mark . '</td><td>' . e($check['name']) . '</td><td>' . e($check['details']) . '</td></tr>';
    }
    $button = $blocking
        ? '<p class="flash flash-error">Spełnij wymagania obowiązkowe i odśwież stronę.</p>'
        : '<form method="post"><input type="hidden" name="csrf" value="' . e($csrf) . '"><button class="btn" type="submit">Dalej: baza danych</button></form>';
    installerRender('Krok 1/8 — wymagania serwera', '<table><tr><th></th><th>Wymaganie</th><th>Stan</th></tr>' . $rows . '</table>' . $button);
}

// ------------------------------------------------------------------- baza
if ($step === 'database') {
    $error = '';
    $warning = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        $host = trim((string) ($_POST['db_host'] ?? ''));
        $name = trim((string) ($_POST['db_name'] ?? ''));
        $user = trim((string) ($_POST['db_user'] ?? ''));
        $pass = (string) ($_POST['db_pass'] ?? '');

        if ($host === '' || $name === '' || $user === '') {
            $error = 'Uzupełnij host, nazwę bazy i użytkownika.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            $error = 'Nazwa bazy może zawierać wyłącznie litery, cyfry i podkreślenia.';
        } else {
            try {
                $pdo = InstallerMigrationRunner::connect($host, $name, $user, $pass);
                $runner = new InstallerMigrationRunner($pdo);
                $tables = $runner->tableCount();
                if ($tables > 0) {
                    $warning = 'Baza zawiera ' . $tables . ' tabel. Instalacja użyje IF NOT EXISTS, ale dla czystej instalacji zalecana jest pusta baza.';
                }
                InstallerState::set('db_host', $host);
                InstallerState::set('db_name', $name);
                InstallerState::set('db_user', $user);
                InstallerState::set('db_pass', $pass);
                InstallerState::set('db_server', $runner->serverVersion());
                InstallerState::markDone('database');
                InstallerState::setStep('location');
                header('Location: ' . $base . '/index.php', true, 303);
                exit;
            } catch (Throwable $exception) {
                error_log('Installer DB test failed.');
                $error = 'Nie udało się połączyć z bazą. Sprawdź dane dostępowe i nazwę bazy.';
            }
        }
    }

    $form = '<form method="post">'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . ($error !== '' ? '<p class="flash flash-error">' . e($error) . '</p>' : '')
        . ($warning !== '' ? '<p class="flash flash-warn">' . e($warning) . '</p>' : '')
        . '<label>Host bazy danych</label><input type="text" name="db_host" value="' . e(InstallerState::get('db_host', 'localhost')) . '" required>'
        . '<label>Nazwa bazy danych</label><input type="text" name="db_name" value="' . e(InstallerState::get('db_name', '')) . '" required>'
        . '<label>Użytkownik bazy danych</label><input type="text" name="db_user" value="' . e(InstallerState::get('db_user', '')) . '" required>'
        . '<label>Hasło bazy danych</label><input type="password" name="db_pass" value="">'
        . '<button class="btn" type="submit">Sprawdź połączenie i dalej</button></form>'
        . '<p>Dane bazy są przechowywane wyłącznie w sesji instalatora do momentu zapisu prywatnej konfiguracji.</p>';
    installerRender('Krok 2/8 — połączenie z bazą', $form);
}

// ------------------------------------------------------------------ adres
if ($step === 'location') {
    $error = '';

    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $detectedUrl = rtrim($scheme . '://' . $host . $base, '/');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        $siteUrl = rtrim(trim((string) ($_POST['site_url'] ?? '')), '/');
        $canonical = strtolower(trim((string) ($_POST['canonical_host'] ?? '')));

        if (!preg_match('#^https?://[a-z0-9.-]+(?::[0-9]{1,5})?(/.*)?$#i', $siteUrl)) {
            $error = 'Adres instalacji musi zaczynać się od http:// lub https://';
        } else {
            InstallerState::set('site_url', $siteUrl);
            InstallerState::set('canonical_host', $canonical);
            InstallerState::markDone('location');
            InstallerState::setStep('schema');
            header('Location: ' . $base . '/index.php', true, 303);
            exit;
        }
    }

    $form = '<form method="post">'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . ($error !== '' ? '<p class="flash flash-error">' . e($error) . '</p>' : '')
        . '<label>Adres instalacji (SITE_URL)</label>'
        . '<input type="text" name="site_url" value="' . e(InstallerState::get('site_url', $detectedUrl)) . '" required>'
        . '<label>Kanoniczny host (CANONICAL_HOST, opcjonalnie — włącza przekierowania na ten host i HTTPS)</label>'
        . '<input type="text" name="canonical_host" value="' . e(InstallerState::get('canonical_host', '')) . '" placeholder="np. example.com">'
        . '<button class="btn" type="submit">Dalej: schemat bazy</button></form>'
        . '<p>Dla instalacji w podkatalogu adres musi zawierać ten podkatalog, np. <code>https://example.com/home</code>.</p>';
    installerRender('Krok 3/8 — adres instalacji', $form);
}

// --------------------------------------------------------------- schemat
if ($step === 'schema') {
    $db = InstallerState::database();
    if ($db === null) {
        InstallerState::setStep('database');
        header('Location: ' . $base . '/index.php', true, 303);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        try {
            $pdo = InstallerMigrationRunner::connect($db['host'], $db['name'], $db['user'], $db['pass']);
            $runner = new InstallerMigrationRunner($pdo);
            $schemaStatements = $runner->executeFile($installerBaseDir . '/database/schema.sql');
            $migrations = $runner->runMigrations($installerBaseDir . '/database/migrations');
            InstallerState::set('schema_stats', $schemaStatements . ' instrukcji schematu; migracje: ' . $migrations['applied'] . ' zastosowane, ' . $migrations['skipped'] . ' pominięte; tabel: ' . $runner->tableCount());
            InstallerState::markDone('schema');
            InstallerState::setStep('seed');
            header('Location: ' . $base . '/index.php', true, 303);
            exit;
        } catch (Throwable $exception) {
            error_log('Installer schema step failed.');
            installerFail('Nie udało się utworzyć schematu bazy. Szczegóły zapisano w logu serwera.');
        }
    }

    installerRender(
        'Krok 4/8 — schemat i migracje',
        '<p>Zostanie utworzony czysty schemat bazy oraz wykonane ponumerowane migracje.</p>'
        . '<form method="post"><input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<button class="btn" type="submit">Utwórz schemat i migracje</button></form>'
    );
}

// ------------------------------------------------------------------ ziarno
if ($step === 'seed') {
    $db = InstallerState::database();
    if ($db === null) {
        InstallerState::setStep('database');
        header('Location: ' . $base . '/index.php', true, 303);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        try {
            $pdo = InstallerMigrationRunner::connect($db['host'], $db['name'], $db['user'], $db['pass']);
            $runner = new InstallerMigrationRunner($pdo);
            $runner->executeFile($installerBaseDir . '/database/seed.sql');
            InstallerState::markDone('seed');
            InstallerState::setStep('admin');
            header('Location: ' . $base . '/index.php', true, 303);
            exit;
        } catch (Throwable $exception) {
            error_log('Installer seed step failed.');
            installerFail('Nie udało się wstawić danych startowych. Szczegóły zapisano w logu serwera.');
        }
    }

    installerRender(
        'Krok 5/8 — dane startowe',
        '<p>Zostaną wstawione wyłącznie minimalne dane startowe: kategorie i menu. Żadne dane produkcyjne nie są importowane automatycznie.</p>'
        . '<form method="post"><input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<button class="btn" type="submit">Wstaw dane startowe</button></form>'
    );
}

// ------------------------------------------------------------------ admin
if ($step === 'admin') {
    $db = InstallerState::database();
    if ($db === null) {
        InstallerState::setStep('database');
        header('Location: ' . $base . '/index.php', true, 303);
        exit;
    }
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        $username = trim((string) ($_POST['admin_user'] ?? ''));
        $password = (string) ($_POST['admin_pass'] ?? '');
        $email = trim((string) ($_POST['admin_email'] ?? ''));

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
            $error = 'Nazwa administratora: 3-50 znaków (litery, cyfry, kropka, myślnik, podkreślenie).';
        } elseif (strlen($password) < 12) {
            $error = 'Hasło administratora musi mieć co najmniej 12 znaków.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Nieprawidłowy adres e-mail.';
        } else {
            try {
                $pdo = InstallerMigrationRunner::connect($db['host'], $db['name'], $db['user'], $db['pass']);
                $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? LIMIT 1');
                $check->execute([$username]);
                if ((int) $check->fetchColumn() > 0) {
                    $error = 'Taki użytkownik już istnieje — użyj innej nazwy.';
                } else {
                    $insert = $pdo->prepare("INSERT INTO users (username, password, email, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
                    $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email]);
                    InstallerState::markDone('admin');
                    InstallerState::setStep('config');
                    header('Location: ' . $base . '/index.php', true, 303);
                    exit;
                }
            } catch (Throwable $exception) {
                error_log('Installer admin step failed.');
                $error = 'Nie udało się utworzyć konta administratora.';
            }
        }
    }

    $form = '<form method="post">'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . ($error !== '' ? '<p class="flash flash-error">' . e($error) . '</p>' : '')
        . '<label>Nazwa administratora</label><input type="text" name="admin_user" value="' . e($_POST['admin_user'] ?? '') . '" required>'
        . '<label>Hasło (minimum 12 znaków)</label><input type="password" name="admin_pass" required>'
        . '<label>E-mail kontaktowy (opcjonalnie)</label><input type="email" name="admin_email" value="' . e($_POST['admin_email'] ?? '') . '">'
        . '<button class="btn" type="submit">Utwórz konto i dalej</button></form>';
    installerRender('Krok 6/8 — konto administratora', $form);
}

// ------------------------------------------------------- prywatny config
if ($step === 'config') {
    $db = InstallerState::database();
    if ($db === null) {
        InstallerState::setStep('database');
        header('Location: ' . $base . '/index.php', true, 303);
        exit;
    }

    $defaultPath = $installerBaseDir . '/.private/66-600-security-config.php';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        $targetPath = trim((string) ($_POST['config_path'] ?? '')) ?: $defaultPath;

        try {
            $siteUrl = (string) InstallerState::get('site_url', '');
            $canonical = (string) InstallerState::get('canonical_host', '');
            $pulseSecret = bin2hex(random_bytes(32));
            $cronToken = bin2hex(random_bytes(32));

            $directory = dirname($targetPath);
            if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new RuntimeException('catalog');
            }

            $config = "<?php\n"
                . "// Prywatna konfiguracja wygenerowana przez instalator. Nie publikować.\n"
                . "return [\n"
                . "    'db' => [\n"
                . "        'host' => " . var_export($db['host'], true) . ",\n"
                . "        'user' => " . var_export($db['user'], true) . ",\n"
                . "        'pass' => " . var_export($db['pass'], true) . ",\n"
                . "        'name' => " . var_export($db['name'], true) . ",\n"
                . "    ],\n"
                . "    'chat_database' => [\n"
                . "        'dsn' => " . var_export('mysql:host=' . $db['host'] . ';dbname=' . $db['name'] . ';charset=utf8mb4', true) . ",\n"
                . "        'username' => " . var_export($db['user'], true) . ",\n"
                . "        'password' => " . var_export($db['pass'], true) . ",\n"
                . "    ],\n"
                . "    'pulse_rate_hash_secret' => " . var_export($pulseSecret, true) . ",\n"
                . "    'cron_cleanup_token' => " . var_export($cronToken, true) . ",\n"
                . "    'site_url' => " . var_export($siteUrl, true) . ",\n"
                . "    'canonical_host' => " . var_export($canonical, true) . ",\n"
                . "];\n";

            $temporary = $targetPath . '.tmp';
            if (@file_put_contents($temporary, $config, LOCK_EX) === false || !rename($temporary, $targetPath)) {
                @unlink($temporary);
                throw new RuntimeException('write');
            }
            @chmod($targetPath, 0640);

            $probe = dirname($targetPath) . '/.write-probe';
            if (@file_put_contents($probe, '1') === false) {
                throw new RuntimeException('probe');
            }
            @unlink($probe);

            $loaded = require $targetPath;
            if (!is_array($loaded) || !isset($loaded['db']['host'])) {
                throw new RuntimeException('format');
            }

            InstallerState::set('config_path', $targetPath);
            InstallerState::markDone('config');
            InstallerState::setStep('finalize');
            header('Location: ' . $base . '/index.php', true, 303);
            exit;
        } catch (Throwable $exception) {
            error_log('Installer config step failed.');
            $error = 'Nie udało się zapisać prywatnej konfiguracji. Sprawdź prawa zapisu katalogu docelowego.';
        }
    }

    $form = '<form method="post">'
        . '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . ($error !== '' ? '<p class="flash flash-error">' . e($error) . '</p>' : '')
        . '<label>Ścieżka prywatnej konfiguracji</label>'
        . '<input type="text" name="config_path" value="' . e($defaultPath) . '" required>'
        . '<p>Plik znajdzie się poza publicznym kodem paczki i jest dodatkowo blokowany przez <code>.htaccess</code>.</p>'
        . '<button class="btn" type="submit">Zapisz konfigurację</button></form>';
    installerRender('Krok 7/8 — prywatna konfiguracja', $form);
}

// ---------------------------------------------------------------- finał
if ($step === 'finalize') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!InstallerState::verifyCsrf($_POST['csrf'] ?? null)) {
            installerFail('Nieprawidłowy token formularza.', 400);
        }
        try {
            $summary = [
                'installed_at' => gmdate('c'),
                'site_url' => InstallerState::get('site_url', ''),
                'canonical_host' => InstallerState::get('canonical_host', ''),
                'config_path' => InstallerState::get('config_path', ''),
                'db_server' => InstallerState::get('db_server', ''),
            ];
            if (@file_put_contents($lockFile, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX) === false) {
                throw new RuntimeException('lock');
            }
            InstallerState::markDone('finalize');
            InstallerState::destroy();
            installerRender('Instalacja zakończona',
                '<p class="flash flash-ok">Instalacja została zakończona i zablokowana plikiem <code>install.lock</code>.</p>'
                . '<h2>Następne kroki</h2><ol>'
                . '<li>Usuń lub zablokuj katalog <code>installer/</code> przez FTP (albo pozostaw — <code>install.lock</code> blokuje ponowne uruchomienie).</li>'
                . '<li>Skonfiguruj zadania okresowe w panelu hostingu zgodnie z <code>docs/cron.md</code>.</li>'
                . '<li>Jeśli przenosisz dane istniejącej instalacji, użyj importera <code>importer/index.php</code> — import jest zawsze osobną, jawną operacją.</li>'
                . '<li>Ustaw <code>SetEnv SITE_URL</code> i ewentualnie <code>SetEnv CANONICAL_HOST</code> w głównym <code>.htaccess</code>, jeśli są potrzebne.</li>'
                . '</ol><p><a class="btn" href="' . e(dirname($base) ?: '/') . '">Przejdź do serwisu</a></p>');
        } catch (Throwable $exception) {
            error_log('Installer finalize failed.');
            installerFail('Nie udało się utworzyć blokady instalacji.');
        }
    }

    installerRender('Krok 8/8 — zakończenie',
        '<p>Instalator utworzy plik <code>install.lock</code> i zakończy działanie. Po zakończeniu ponowne otwarcie instalatora nie będzie możliwe.</p>'
        . '<form method="post"><input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<button class="btn" type="submit">Zakończ instalację</button></form>');
}

installerFail('Nieznany krok instalatora.', 400);
