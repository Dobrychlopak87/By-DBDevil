<?php
declare(strict_types=1);

final class Database
{
    /** @param array<string, mixed> $config */
    public static function connect(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            (string) $config['host'],
            (string) $config['name']
        );
        try {
            return new PDO($dsn, (string) $config['user'], (string) $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            error_log('Nie udało się połączyć z bazą danych aplikacji.');
            throw new RuntimeException('Serwis jest chwilowo niedostępny.', 0, $exception);
        }
    }
}
