<?php
declare(strict_types=1);

/**
 * Stan instalatora w sesji. Dane bazy znajdują się wyłącznie w sesji do
 * momentu zapisu prywatnej konfiguracji — nigdy w logach ani w kodzie.
 */
final class InstallerState
{
    public const SESSION_NAME = 'installer_66600';
    public const STEPS = [
        'requirements',
        'database',
        'location',
        'schema',
        'seed',
        'admin',
        'config',
        'finalize',
    ];

    public static function boot(string $baseDir): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        session_name(self::SESSION_NAME);
        $sessionDir = $baseDir . '/session';
        if (is_dir($sessionDir) && is_writable($sessionDir)) {
            session_save_path($sessionDir);
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        if (empty($_SESSION['installer_csrf'])) {
            $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
        }
        if (empty($_SESSION['installer_step']) || !in_array($_SESSION['installer_step'], self::STEPS, true)) {
            $_SESSION['installer_step'] = self::STEPS[0];
        }
        if (!isset($_SESSION['installer_done'])) {
            $_SESSION['installer_done'] = [];
        }
    }

    public static function csrfToken(): string
    {
        return (string) ($_SESSION['installer_csrf'] ?? '');
    }

    public static function verifyCsrf(?string $token): bool
    {
        $expected = self::csrfToken();
        return is_string($token) && $token !== '' && hash_equals($expected, $token);
    }

    public static function currentStep(): string
    {
        return (string) ($_SESSION['installer_step'] ?? self::STEPS[0]);
    }

    public static function setStep(string $step): void
    {
        if (in_array($step, self::STEPS, true)) {
            $_SESSION['installer_step'] = $step;
        }
    }

    public static function markDone(string $step): void
    {
        $_SESSION['installer_done'][$step] = true;
    }

    public static function isDone(string $step): bool
    {
        return !empty($_SESSION['installer_done'][$step]);
    }

    public static function allDone(): bool
    {
        foreach (self::STEPS as $step) {
            if (empty($_SESSION['installer_done'][$step])) {
                return false;
            }
        }
        return true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_SESSION['installer_data'][$key] ?? null;
        return is_string($value) ? $value : $default;
    }

    public static function set(string $key, string $value): void
    {
        $_SESSION['installer_data'][$key] = $value;
    }

    /** @return array{host: string, name: string, user: string, pass: string}|null */
    public static function database(): ?array
    {
        $host = self::get('db_host');
        $name = self::get('db_name');
        $user = self::get('db_user');
        $pass = self::get('db_pass', '');
        if ($host === null || $name === null || $user === null || $pass === null) {
            return null;
        }
        return ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass];
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
