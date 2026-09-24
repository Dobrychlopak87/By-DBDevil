<?php

declare(strict_types=1);

/**
 * Izolowane helpery Pulsu miasta. Moduł korzysta ze wspólnego PDO z config.php.
 */
function pulseEnsureSchema(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    global $pdo;
    try {
        $statement = $pdo->prepare("SHOW TABLES LIKE 'pulse_notices'");
        $statement->execute();
        if ($statement->fetchColumn() === false) {
            return $ready = false;
        }
        $columns = $pdo->query('SHOW COLUMNS FROM pulse_notices')->fetchAll(PDO::FETCH_COLUMN);
        $ready = in_array('author_source', $columns, true);
    } catch (Throwable $exception) {
        $ready = false;
    }
    return $ready;
}

function pulseCsrfToken(): string
{
    $token = $_SESSION['pulse_csrf'] ?? '';
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['pulse_csrf'] = $token;
    }
    return $token;
}

function pulseVerifyCsrf(?string $token): bool
{
    return is_string($token) && hash_equals(pulseCsrfToken(), $token);
}

function pulseNormalizeText(string $value): string
{
    return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

function pulseTextLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function pulseValidateMessage(string $message): ?string
{
    if ($message === '') {
        return 'Wpisz treść komunikatu.';
    }
    if (pulseTextLength($message) > 160) {
        return 'Komunikat może mieć maksymalnie 160 znaków.';
    }
    if (preg_match('/https?:\/\/|www\./iu', $message) === 1) {
        return 'Nie dodawaj linków do komunikatu.';
    }
    if (preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $message) === 1) {
        return 'Nie dodawaj emoji do komunikatu.';
    }
    return null;
}

function pulseValidateSignature(string $signature): ?string
{
    if (pulseTextLength($signature) > 80) {
        return 'Podpis może mieć maksymalnie 80 znaków.';
    }
    if ($signature !== '' && preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $signature) === 1) {
        return 'Podpis nie może zawierać emoji.';
    }
    return null;
}

function pulseValidatePhone(string $phone): ?string
{
    if (pulseTextLength($phone) > 40) {
        return 'Telefon może mieć maksymalnie 40 znaków.';
    }
    if ($phone !== '' && preg_match('/[^0-9 +()\-]/', $phone) === 1) {
        return 'Podaj telefon w czytelnym formacie.';
    }
    return null;
}

function pulseRateHashSecret(): string
{
    if (!defined('PULSE_RATE_HASH_SECRET')
        || !is_string(PULSE_RATE_HASH_SECRET)
        || strlen(PULSE_RATE_HASH_SECRET) < 32) {
        throw new RuntimeException('Brak niezależnego sekretu limitowania Pulsu.');
    }

    return PULSE_RATE_HASH_SECRET;
}

function pulseClientHash(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash_hmac('sha256', 'pulse|' . $ip, pulseRateHashSecret());
}

function pulseRateLimitError(string $ipHash): ?string
{
    global $pdo;
    $hourStmt = $pdo->prepare("SELECT COUNT(*) FROM pulse_notices WHERE ip_hash = ? AND published_at >= (UTC_TIMESTAMP() - INTERVAL 1 HOUR) AND status <> 'deleted'");
    $hourStmt->execute([$ipHash]);
    if ((int) $hourStmt->fetchColumn() >= 1) {
        return 'Możesz dodać jeden wpis na godzinę.';
    }
    $dayStmt = $pdo->prepare("SELECT COUNT(*) FROM pulse_notices WHERE ip_hash = ? AND published_at >= (UTC_TIMESTAMP() - INTERVAL 24 HOUR) AND status <> 'deleted'");
    $dayStmt->execute([$ipHash]);
    if ((int) $dayStmt->fetchColumn() >= 2) {
        return 'Osiągnięto limit dwóch wpisów w ciągu 24 godzin.';
    }
    return null;
}

function pulseCreateNotice(string $message, string $signature, string $phone, string $ipHash, bool $official = false): int
{
    global $pdo;
    $authorSource = $official ? 'official' : 'community';
    $stmt = $pdo->prepare("INSERT INTO pulse_notices (message, signature, phone, author_source, status, ip_hash, published_at, expires_at) VALUES (?, ?, ?, ?, 'published', ?, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 12 HOUR))");
    $stmt->execute([$message, $signature !== '' ? $signature : null, $phone !== '' ? $phone : null, $authorSource, $ipHash]);
    return (int) $pdo->lastInsertId();
}

/**
 * Serwerowy blok nazwy dla pojedynczego klienta zapobiega ominięciu limitów
 * przez dwa równoległe żądania z tego samego adresu.
 *
 * @return array{ok: bool, id?: int, error?: string}
 */
function pulsePublishNotice(string $message, string $signature, string $phone, string $ipHash, bool $official = false): array
{
    global $pdo;
    $lockName = 'pulse-rate-' . substr($ipHash, 0, 48);
    $acquire = $pdo->prepare('SELECT GET_LOCK(?, 2)');
    $acquire->execute([$lockName]);

    if ((int) $acquire->fetchColumn() !== 1) {
        return ['ok' => false, 'error' => 'Nie udało się teraz sprawdzić limitu. Spróbuj ponownie za chwilę.'];
    }

    try {
        $error = pulseRateLimitError($ipHash);
        if ($error !== null) {
            return ['ok' => false, 'error' => $error];
        }

        return ['ok' => true, 'id' => pulseCreateNotice($message, $signature, $phone, $ipHash, $official)];
    } finally {
        try {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        } catch (Throwable $exception) {
            // Brak możliwości zwolnienia blokady nie może zmienić odpowiedzi użytkownika.
        }
    }
}

function pulseActiveNotices(int $limit = 50): array
{
    global $pdo;
    $limit = max(1, min($limit, 100));
    $stmt = $pdo->prepare("SELECT id, message, signature, phone, author_source, published_at, expires_at FROM pulse_notices WHERE status = 'published' AND expires_at > UTC_TIMESTAMP() ORDER BY published_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function pulseAllNotices(int $limit = 200): array
{
    global $pdo;
    $limit = max(1, min($limit, 500));
    $stmt = $pdo->prepare("SELECT id, message, signature, phone, author_source, status, ip_hash, published_at, expires_at, updated_at FROM pulse_notices ORDER BY published_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function pulseModerateNotice(int $id, string $status): bool
{
    if (!in_array($status, ['published', 'hidden', 'deleted'], true)) {
        return false;
    }
    global $pdo;
    $stmt = $pdo->prepare('UPDATE pulse_notices SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

function pulsePublishedTime(array $notice): string
{
    $value = (string) ($notice['published_at'] ?? '');
    if ($value === '') {
        return '';
    }

    try {
        $publishedAt = new DateTimeImmutable($value, new DateTimeZone('UTC'));
        return $publishedAt->setTimezone(new DateTimeZone('Europe/Warsaw'))->format('H:i');
    } catch (Throwable $exception) {
        return '';
    }
}

function pulseTickerItems(): array
{
    $items = [[
        'kind' => 'pulse',
        'label' => 'Puls miasta : Pilne, bez reklam.',
    ]];
    foreach (pulseActiveNotices() as $notice) {
        $items[] = [
            'kind' => 'notice',
            'time' => pulsePublishedTime($notice),
            'label' => (string) $notice['message'],
            'signature' => (string) ($notice['signature'] ?? ''),
            'phone' => (string) ($notice['phone'] ?? ''),
            'author_source' => (string) ($notice['author_source'] ?? 'community'),
        ];
    }
    return $items;
}
