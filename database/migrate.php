<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Migracje można uruchamiać wyłącznie z CLI.\n");
    exit(1);
}

require dirname(__DIR__) . '/includes/config.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(120) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$migrations = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($migrations, SORT_STRING);
foreach ($migrations as $migration) {
    $version = basename($migration, '.sql');
    $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = ? LIMIT 1');
    $check->execute([$version]);
    if ($check->fetchColumn()) {
        printf("SKIP %s\n", $version);
        continue;
    }

    $sql = file_get_contents($migration);
    if (!is_string($sql) || trim($sql) === '') {
        throw new RuntimeException("Pusta migracja: {$version}");
    }
    // DDL w MySQL/MariaDB kończy transakcję niejawnym commitem, dlatego
    // migracje wykonujemy bez transakcji. Idempotentność zapewniają
    // IF NOT EXISTS oraz rejestr schema_migrations.
    try {
        $pdo->exec($sql);
        $insert = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
        $insert->execute([$version]);
        printf("APPLY %s\n", $version);
    } catch (Throwable $error) {
        fwrite(STDERR, "Błąd migracji {$version}: " . $error->getMessage() . "\n");
        throw $error;
    }
}
