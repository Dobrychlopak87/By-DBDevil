<?php
declare(strict_types=1);

/**
 * Wykonywanie schematu, migracji i ziarna na czystej bazie.
 * Migracje są rejestrowane w schema_migrations i nie uruchamiają się
 * automatycznie podczas zwykłych żądań aplikacji.
 */
final class InstallerMigrationRunner
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function connect(string $host, string $name, string $user, string $pass): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name),
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function serverVersion(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /** @return list<string> */
    public static function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if (!$inString && ($char === '#' || ($char === '-' && substr($sql, $i, 3) === '-- '))) {
                $end = strpos($sql, "\n", $i);
                if ($end === false) {
                    break;
                }
                $i = $end;
                continue;
            }

            if ($inString) {
                if ($char === '\\' && $i + 1 < $length) {
                    $current .= $char . $sql[$i + 1];
                    $i++;
                    continue;
                }
                if ($char === $stringChar) {
                    $inString = false;
                }
                $current .= $char;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($current);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $statement = trim($current);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    public function executeFile(string $path): int
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Nie można odczytać pliku SQL.');
        }
        $sql = file_get_contents($path);
        if (!is_string($sql) || trim($sql) === '') {
            throw new RuntimeException('Plik SQL jest pusty.');
        }

        $count = 0;
        foreach (self::splitStatements($sql) as $statement) {
            $this->pdo->exec($statement);
            $count++;
        }
        return $count;
    }

    public function ensureMigrationsTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS `schema_migrations` (
            version VARCHAR(120) NOT NULL PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    /** @return array{applied: int, skipped: int} */
    public function runMigrations(string $directory): array
    {
        $this->ensureMigrationsTable();
        $files = glob(rtrim($directory, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $applied = 0;
        $skipped = 0;
        foreach ($files as $file) {
            $version = basename($file, '.sql');
            $check = $this->pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = ? LIMIT 1');
            $check->execute([$version]);
            if ($check->fetchColumn()) {
                $skipped++;
                continue;
            }
            $this->executeFile($file);
            $insert = $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
            $insert->execute([$version]);
            $applied++;
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }

    public function tableCount(): int
    {
        $statement = $this->pdo->query('SHOW TABLES');
        return count($statement->fetchAll());
    }

    public function hasRows(string $table): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        $statement = $this->pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return (bool) $statement->fetchColumn();
    }
}
