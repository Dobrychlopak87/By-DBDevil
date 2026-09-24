<?php
declare(strict_types=1);

final class Config
{
    /** @return array<string, mixed> */
    public static function load(?string $path = null): array
    {
        $path ??= (string) (getenv('APP_PRIVATE_CONFIG_PATH') ?: dirname(__DIR__) . '/.private/66-600-security-config.php');
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Konfiguracja serwisu jest chwilowo niedostępna.');
        }

        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException('Konfiguracja serwisu jest chwilowo niedostępna.');
        }

        self::validate($config);
        return $config;
    }

    /** @param array<string, mixed> $config */
    private static function validate(array $config): void
    {
        $db = $config['db'] ?? null;
        $chat = $config['chat_database'] ?? null;
        $secret = $config['pulse_rate_hash_secret'] ?? null;
        if (!is_array($db) || !is_array($chat) || !is_string($secret) || strlen($secret) < 32) {
            throw new RuntimeException('Konfiguracja serwisu jest chwilowo niedostępna.');
        }
        foreach (['host', 'user', 'pass', 'name'] as $field) {
            if (!isset($db[$field]) || !is_string($db[$field]) || $db[$field] === '') {
                throw new RuntimeException('Konfiguracja serwisu jest chwilowo niedostępna.');
            }
        }
        foreach (['dsn', 'username', 'password'] as $field) {
            if (!isset($chat[$field]) || !is_string($chat[$field])) {
                throw new RuntimeException('Konfiguracja serwisu jest chwilowo niedostępna.');
            }
        }
        foreach ([$db, $chat, ['pulse_rate_hash_secret' => $secret]] as $section) {
            foreach ($section as $value) {
                if (is_string($value) && str_contains($value, 'CHANGE_ME')) {
                    throw new RuntimeException('Konfiguracja serwisu jest chwilowo niedostępna.');
                }
            }
        }
    }
}
