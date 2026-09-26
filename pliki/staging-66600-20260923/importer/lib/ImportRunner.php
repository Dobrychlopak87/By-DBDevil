<?php
declare(strict_types=1);

/**
 * Importer danych istniejącej instalacji do nowej instalacji.
 * Zasady: działa po wykonaniu backupu, ma tryb podglądu bez zapisu,
 * importuje tabelami, zachowuje identyfikatory bez konfliktów, mapuje
 * stare adresy wyłącznie w polach URL, pomija sesje/logi/cache/backupy,
 * jest powtarzalny bez duplikatów i zatrzymuje się przy błędzie krytycznym.
 */
final class ImportRunner
{
    private PDO $source;
    private PDO $target;
    private string $oldBaseUrl;
    private string $newBaseUrl;

    /** Tabele treści importowane z zachowaniem identyfikatorów. */
    private const TABLES = [
        'ad_categories',
        'ads',
        'ad_gallery',
        'blog_categories',
        'blog_posts',
        'blog_post_gallery',
        'chronicle_comments',
        'chronicle_post_votes',
        'chronicle_comment_likes',
        'polls',
        'poll_options',
        'poll_votes',
        'calendar_events',
        'pulse_notices',
        'public_menu_items',
        'users',
        'auth_users',
    ];

    /** Pola URL — wyłącznie w nich mapowane są adresy starej domeny. */
    private const URL_FIELDS = [
        'ads.link',
        'ads.contact_url',
    ];

    /** Tabele pomijane świadomie (sesje, logi, cache, runtime, statystyki). */
    public const SKIPPED_TABLES = [
        'schema_migrations',
        'admin_login_attempts',
        'auth_rate_limits',
        'site_visit_stats',
        'chat_sessions',
        'chat_messages',
        'chat_message_images',
        'chat_private_messages',
        'chat_nick_accounts',
        'chat_nick_claims',
        'chat_nickname_blocks',
        'chat_admin_log',
    ];

    public function __construct(PDO $source, PDO $target, string $oldBaseUrl, string $newBaseUrl)
    {
        $this->source = $source;
        $this->target = $target;
        $this->oldBaseUrl = rtrim($oldBaseUrl, '/');
        $this->newBaseUrl = rtrim($newBaseUrl, '/');
    }

    /** Podgląd bez zapisu: liczby rekordów źródłowych i konflikty ID w celu. */
    public function preview(): array
    {
        $report = [];
        foreach (self::TABLES as $table) {
            $sourceCount = $this->countTable($this->source, $table);
            if ($sourceCount === null) {
                $report[$table] = ['source' => 0, 'conflicts' => 0, 'note' => 'brak tabeli w źródle'];
                continue;
            }
            $targetCount = $this->countTable($this->target, $table) ?? 0;
            $conflicts = 0;
            if ($sourceCount > 0 && $targetCount > 0) {
                $conflicts = $this->countIdConflicts($table);
            }
            $report[$table] = ['source' => $sourceCount, 'conflicts' => $conflicts, 'note' => ''];
        }
        return $report;
    }

    /** Konflikty ID liczone po stronie PHP — źródło i cel mogą być na innych hostach. */
    private function countIdConflicts(string $table): int
    {
        if (!$this->tableExists($this->source, $table) || !$this->tableExists($this->target, $table)) {
            return 0;
        }
        $sourceColumns = $this->tableColumns($this->source, $table);
        if (!in_array('id', $sourceColumns, true) || !in_array('id', $this->tableColumns($this->target, $table), true)) {
            return 0;
        }

        $conflicts = 0;
        $checked = 0;
        $offset = 0;
        $limit = 500;
        while ($checked < 5000) {
            $ids = $this->source->query("SELECT id FROM `{$table}` ORDER BY id ASC LIMIT {$limit} OFFSET {$offset}")->fetchAll(PDO::FETCH_COLUMN);
            if ($ids === []) {
                break;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $check = $this->target->prepare("SELECT COUNT(*) FROM `{$table}` WHERE id IN ({$placeholders})");
            $check->execute(array_values($ids));
            $conflicts += (int) $check->fetchColumn();
            $checked += count($ids);
            $offset += $limit;
        }
        return $conflicts;
    }

    /** @return array<string, array{imported: int, skipped: int, errors: int}> */
    public function run(): array
    {
        $report = [];
        foreach (self::TABLES as $table) {
            $report[$table] = $this->importTable($table);
        }
        return $report;
    }

    /** @return array{imported: int, skipped: int, errors: int} */
    private function importTable(string $table): array
    {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => 0];

        $sourceExists = $this->tableExists($this->source, $table);
        $targetExists = $this->tableExists($this->target, $table);
        if (!$sourceExists || !$targetExists) {
            return $result;
        }

        $columns = array_values(array_intersect(
            $this->tableColumns($this->source, $table),
            $this->tableColumns($this->target, $table)
        ));
        if ($columns === []) {
            return $result;
        }
        $hasId = in_array('id', $columns, true);
        $urlFields = [];
        foreach (self::URL_FIELDS as $field) {
            [$fieldTable, $fieldColumn] = explode('.', $field, 2);
            if ($fieldTable === $table && in_array($fieldColumn, $columns, true)) {
                $urlFields[] = $fieldColumn;
            }
        }

        $offset = 0;
        $limit = 500;
        while (true) {
            $order = $hasId ? 'ORDER BY id ASC' : '';
            $rows = $this->source->query("SELECT * FROM `{$table}` {$order} LIMIT {$limit} OFFSET {$offset}")->fetchAll();
            if ($rows === []) {
                break;
            }

            $existingIds = [];
            if ($hasId) {
                $ids = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $check = $this->target->prepare("SELECT id FROM `{$table}` WHERE id IN ({$placeholders})");
                $check->execute($ids);
                $existingIds = array_fill_keys(array_map('intval', $check->fetchAll(PDO::FETCH_COLUMN)), true);
            }

            $this->target->beginTransaction();
            try {
                foreach ($rows as $row) {
                    $row = array_intersect_key($row, array_flip($columns));

                    if ($hasId && isset($existingIds[(int) $row['id']])) {
                        $result['skipped']++;
                        continue;
                    }

                    foreach ($urlFields as $urlColumn) {
                        if (isset($row[$urlColumn]) && is_string($row[$urlColumn]) && $this->oldBaseUrl !== '') {
                            $row[$urlColumn] = str_replace($this->oldBaseUrl, $this->newBaseUrl, $row[$urlColumn]);
                        }
                    }

                    $columnList = implode('`, `', array_keys($row));
                    $valuePlaceholders = implode(', ', array_fill(0, count($row), '?'));
                    $insert = $this->target->prepare("INSERT INTO `{$table}` (`{$columnList}`) VALUES ({$valuePlaceholders})");
                    $insert->execute(array_values($row));
                    $result['imported']++;
                }
                $this->target->commit();
            } catch (Throwable $exception) {
                if ($this->target->inTransaction()) {
                    $this->target->rollBack();
                }
                $result['errors']++;
                error_log('Import table failed: ' . $table);
                throw new RuntimeException('Import tabeli ' . $table . ' nie powiódł się i został zatrzymany.');
            }

            $offset += $limit;
        }

        return $result;
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        // Nazwy tabel pochodzą wyłącznie ze stałej listy TABLES/SKIPPED_TABLES.
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        $statement = $pdo->query("SHOW TABLES LIKE '{$table}'");
        return (bool) $statement->fetchColumn();
    }

    private function countTable(PDO $pdo, string $table): ?int
    {
        if (!$this->tableExists($pdo, $table)) {
            return null;
        }
        return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    }

    /** @return list<string> */
    private function tableColumns(PDO $pdo, string $table): array
    {
        return array_map('strval', $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN));
    }

}
