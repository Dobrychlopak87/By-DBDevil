<?php
declare(strict_types=1);

/**
 * Walidacja wymagań serwera dla instalatora. Funkcje opcjonalne nie mogą
 * blokować instalacji — oznaczane są jako optional z bezpiecznym fallbackiem.
 */
final class InstallerRequirements
{
    public const MIN_PHP = '8.1.0';

    /** @return array<int, array{name: string, ok: bool, optional: bool, details: string}> */
    public static function collect(string $baseDir): array
    {
        $checks = [];

        $checks[] = [
            'name' => 'Wersja PHP >= ' . self::MIN_PHP,
            'ok' => version_compare(PHP_VERSION, self::MIN_PHP, '>='),
            'optional' => false,
            'details' => 'Wykryto PHP ' . PHP_VERSION . '.',
        ];

        foreach (['pdo', 'pdo_mysql', 'json', 'fileinfo', 'mbstring', 'openssl'] as $extension) {
            $checks[] = [
                'name' => 'Rozszerzenie PHP: ' . $extension,
                'ok' => extension_loaded($extension),
                'optional' => false,
                'details' => extension_loaded($extension) ? 'dostępne' : 'brak',
            ];
        }

        foreach (['gd' => 'generowanie miniatur i konwersja AVIF', 'curl' => 'aktualizacja pogody'] as $extension => $purpose) {
            $checks[] = [
                'name' => 'Rozszerzenie opcjonalne: ' . $extension . ' (' . $purpose . ')',
                'ok' => extension_loaded($extension),
                'optional' => true,
                'details' => extension_loaded($extension) ? 'dostępne' : 'niedostępne — bezpieczny fallback',
            ];
        }

        $checks[] = [
            'name' => 'Obsługa sesji',
            'ok' => function_exists('session_status'),
            'optional' => false,
            'details' => function_exists('session_status') ? 'dostępna' : 'brak',
        ];

        foreach ([
            '.private' => dirname($baseDir) . '/.private',
            'session' => $baseDir . '/session',
            'assets/uploads' => $baseDir . '/assets/uploads',
            'assets/uploads/thumbs' => $baseDir . '/assets/uploads/thumbs',
            'chatroom/uploads' => $baseDir . '/chatroom/uploads',
        ] as $label => $path) {
            $writable = (is_dir($path) && is_writable($path)) || (!is_dir($path) && @mkdir($path, 0755, true));
            if ($writable && !is_dir($path)) {
                $writable = false;
            }
            $checks[] = [
                'name' => 'Katalog zapisywalny: ' . $label,
                'ok' => $writable,
                'optional' => false,
                'details' => $writable ? 'zapisywalny' : 'brak prawa zapisu',
            ];
        }

        return $checks;
    }

    /** @param array<int, array{name: string, ok: bool, optional: bool, details: string}> $checks */
    public static function blockingFailed(array $checks): bool
    {
        foreach ($checks as $check) {
            if (!$check['ok'] && !$check['optional']) {
                return true;
            }
        }
        return false;
    }
}
