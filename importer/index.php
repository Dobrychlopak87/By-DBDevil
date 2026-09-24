<?php
declare(strict_types=1);

/**
 * Importer danych istniejącej instalacji — osobna, jawna operacja.
 * Wymaga zakończonej instalacji (install.lock) i świadomego uruchomienia.
 * Tryb podglądu nie wykonuje żadnych zapisów.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/../installer/lib/MigrationRunner.php';
require __DIR__ . '/lib/ImportRunner.php';

$appRoot = dirname(__DIR__);
$lockFile = $appRoot . '/install.lock';

function importFail(string $message, int $status = 400): never
{
    http_response_code($status);
    importRender('Błąd', '<p class="flash flash-error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>');
    exit;
}

if (!is_file($lockFile)) {
    importFail('Importer działa dopiero po zakończonej instalacji (brak install.lock).', 404);
}

function importRender(string $stepTitle, string $content): void
{
    $styles = 'body{font-family:system-ui,sans-serif;background:#101820;color:#e8edf2;margin:0;padding:24px}'
        . '.box{max-width:820px;margin:0 auto;background:#18222d;border:1px solid #2a3947;border-radius:10px;padding:28px}'
        . 'h1{font-size:20px;margin:0 0 6px}h2{font-size:16px;margin:22px 0 8px;color:#9fd3ff}'
        . 'label{display:block;margin:10px 0 4px;font-size:14px}input[type=text],input[type=password]{width:100%;padding:9px;border-radius:6px;border:1px solid #35485c;background:#0f1720;color:#e8edf2;box-sizing:border-box}'
        . '.btn{display:inline-block;margin-top:16px;padding:10px 20px;border-radius:6px;border:0;background:#2f81f7;color:#fff;font-size:15px;cursor:pointer}'
        . '.flash{padding:12px 14px;border-radius:6px;margin:14px 0;font-size:14px}.flash-error{background:#3a1d24;border:1px solid #7f2c3a}'
        . '.flash-ok{background:#17321f;border:1px solid #2e7d43}.flash-warn{background:#3a2f16;border:1px solid #8a6a1f}'
        . 'table{width:100%;border-collapse:collapse;font-size:14px;margin-top:10px}td,th{border-bottom:1px solid #2a3947;padding:7px 8px;text-align:left}'
        . 'code{background:#0f1720;padding:2px 6px;border-radius:4px}';
    $title = htmlspecialchars('Importer 66600.PL', ENT_QUOTES, 'UTF-8');
    $stepSafe = htmlspecialchars($stepTitle, ENT_QUOTES, 'UTF-8');
    echo "<!DOCTYPE html><html lang=\"pl\"><head><meta charset=\"UTF-8\">"
        . "<meta name=\"robots\" content=\"noindex,nofollow\"><title>{$title}</title>"
        . "<style>{$styles}</style></head><body><div class=\"box\">"
        . "<h1>{$title}</h1><h2>{$stepSafe}</h2>{$content}</div></body></html>";
    exit;
}

function importE(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('importer_66600');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
if (empty($_SESSION['importer_csrf'])) {
    $_SESSION['importer_csrf'] = bin2hex(random_bytes(32));
}
$csrf = (string) $_SESSION['importer_csrf'];

function importVerifyCsrf(?string $token): bool
{
    $expected = (string) ($_SESSION['importer_csrf'] ?? '');
    return is_string($token) && $token !== '' && hash_equals($expected, $token);
}

$mode = $_POST['mode'] ?? ($_GET['mode'] ?? 'form');

if ($mode === 'form' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $form = '<form method="post">'
        . '<input type="hidden" name="csrf" value="' . importE($csrf) . '">'
        . '<input type="hidden" name="mode" value="preview">'
        . '<h2>Baza źródłowa (istniejąca instalacja)</h2>'
        . '<label>Host</label><input type="text" name="src_host" required>'
        . '<label>Nazwa bazy</label><input type="text" name="src_name" required>'
        . '<label>Użytkownik</label><input type="text" name="src_user" required>'
        . '<label>Hasło</label><input type="password" name="src_pass">'
        . '<h2>Mapowanie adresów</h2>'
        . '<label>Stary adres instalacji (mapowany wyłącznie w polach URL)</label><input type="text" name="old_url" placeholder="https://stara-domena.pl">'
        . '<p>Treści artykułów i ogłoszeń nie są zmieniane. Sesje, logi, cache, backupy i dane chatroomu są pomijane.</p>'
        . '<label><input type="checkbox" name="backup_confirmed" value="1"> Wykonałem backup docelowej bazy przed importem.</label>'
        . '<button class="btn" type="submit">Pokaż podgląd (bez zapisu)</button></form>';
    importRender('Import danych — podgląd', $form);
}

if (!importVerifyCsrf($_POST['csrf'] ?? null)) {
    importFail('Nieprawidłowy token formularza.');
}
if (empty($_POST['backup_confirmed'])) {
    importFail('Import wymaga potwierdzenia wykonania backupu docelowej bazy.');
}

$srcHost = trim((string) ($_POST['src_host'] ?? ''));
$srcName = trim((string) ($_POST['src_name'] ?? ''));
$srcUser = trim((string) ($_POST['src_user'] ?? ''));
$srcPass = (string) ($_POST['src_pass'] ?? '');
$oldUrl = rtrim(trim((string) ($_POST['old_url'] ?? '')), '/');

if ($srcHost === '' || $srcName === '' || $srcUser === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $srcName)) {
    importFail('Uzupełnij poprawne dane bazy źródłowej.');
}

// Baza docelowa — wyłącznie z prywatnej konfiguracji zainstalowanej aplikacji.
$privateConfig = null;
try {
    $configPath = $appRoot . '/.private/66-600-security-config.php';
    if (is_file($configPath)) {
        $privateConfig = require $configPath;
    }
} catch (Throwable $exception) {
    $privateConfig = null;
}
if (!is_array($privateConfig) || !isset($privateConfig['db']['host'])) {
    importFail('Nie można odczytać prywatnej konfiguracji aplikacji.', 500);
}
$db = $privateConfig['db'];
$newUrl = (string) ($privateConfig['site_url'] ?? '');

try {
    $source = InstallerMigrationRunner::connect($srcHost, $srcName, $srcUser, $srcPass);
    $target = InstallerMigrationRunner::connect((string) $db['host'], (string) $db['name'], (string) $db['user'], (string) $db['pass']);
} catch (Throwable $exception) {
    error_log('Importer connection failed.');
    importFail('Nie udało się połączyć z bazą źródłową lub docelową.');
}

$runner = new ImportRunner($source, $target, $oldUrl, $newUrl);

if ($mode === 'preview') {
    try {
        $report = $runner->preview();
    } catch (Throwable $exception) {
        error_log('Importer preview failed.');
        importFail('Podgląd importu nie powiódł się.');
    }

    $rows = '';
    foreach ($report as $table => $info) {
        $rows .= '<tr><td><code>' . importE($table) . '</code></td><td>' . (int) $info['source'] . '</td><td>'
            . (int) $info['conflicts'] . '</td><td>' . importE($info['note']) . '</td></tr>';
    }
    $skipped = '<li>' . implode('</li><li>', array_map('importE', ImportRunner::SKIPPED_TABLES)) . '</li>';

    $form = '<table><tr><th>Tabela</th><th>Rekordy w źródle</th><th>Konflikty ID</th><th>Uwagi</th></tr>' . $rows . '</table>'
        . '<h2>Pomijane świadomie</h2><ul>' . $skipped . '</ul>'
        . '<p>Konflikty ID zostaną pominięte (bez duplikatów). Podgląd nie wykonał żadnych zapisów.</p>'
        . '<form method="post">'
        . '<input type="hidden" name="csrf" value="' . importE($csrf) . '">'
        . '<input type="hidden" name="mode" value="run">'
        . '<input type="hidden" name="src_host" value="' . importE($srcHost) . '">'
        . '<input type="hidden" name="src_name" value="' . importE($srcName) . '">'
        . '<input type="hidden" name="src_user" value="' . importE($srcUser) . '">'
        . '<input type="hidden" name="src_pass" value="' . importE($srcPass) . '">'
        . '<input type="hidden" name="old_url" value="' . importE($oldUrl) . '">'
        . '<input type="hidden" name="backup_confirmed" value="1">'
        . '<button class="btn" type="submit">Uruchom import</button></form>';
    importRender('Podgląd importu — bez zapisu', $form);
}

if ($mode === 'run') {
    try {
        $report = $runner->run();
    } catch (Throwable $exception) {
        error_log('Importer run failed.');
        importFail('Import został zatrzymany z powodu błędu krytycznego. Szczegóły zapisano w logu serwera.');
    }

    $rows = '';
    $totals = ['imported' => 0, 'skipped' => 0, 'errors' => 0];
    foreach ($report as $table => $info) {
        foreach ($totals as $key => $value) {
            $totals[$key] += (int) $info[$key];
        }
        $rows .= '<tr><td><code>' . importE($table) . '</code></td><td>' . (int) $info['imported'] . '</td><td>'
            . (int) $info['skipped'] . '</td><td>' . (int) $info['errors'] . '</td></tr>';
    }

    importRender(
        'Raport importu',
        '<table><tr><th>Tabela</th><th>Zaimportowane</th><th>Pominięte</th><th>Błędy</th></tr>' . $rows . '</table>'
        . '<p class="flash flash-ok">Razem: zaimportowane ' . (int) $totals['imported'] . ', pominięte ' . (int) $totals['skipped']
        . ', błędy ' . (int) $totals['errors'] . '. Import można bezpiecznie powtórzyć — pominięte ID nie utworzą duplikatów.</p>'
        . '<p>Usuń katalog <code>importer/</code> przez FTP po zakończeniu migracji danych.</p>'
    );
}

importFail('Nieznany tryb importera.');
