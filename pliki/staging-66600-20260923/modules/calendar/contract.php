<?php
declare(strict_types=1);

function ensureCalendarSchema(): void
{
    global $pdo;
    static $ready = false;
    if ($ready) return;
    $table = $pdo->prepare("SHOW TABLES LIKE 'calendar_events'");
    $table->execute();
    if ($table->fetchColumn() === false) return;
    $columns = $pdo->query('SHOW COLUMNS FROM blog_posts')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('source_type', $columns, true)) return;
    $ready = true;
}

function calendarCategories(): array
{
    return ['Mieszkańcy', 'Kultura', 'Sport', 'Miasto', 'Przypomnienie'];
}

function calendarChronicleEntries(int $limit = 200): array
{
    global $pdo;
    $limit = max(1, min($limit, 500));
    $stmt = $pdo->prepare("SELECT bp.id, bp.title, bp.excerpt, bp.slug, bp.image, bp.created_at,
            bp.source_type, bc.name AS category_name,
            (SELECT COUNT(*) FROM blog_post_gallery bpg WHERE bpg.post_id = bp.id) AS gallery_count,
            ce.id AS linked_event_id
        FROM blog_posts bp
        LEFT JOIN blog_categories bc ON bc.id = bp.category_id
        LEFT JOIN calendar_events ce ON ce.chronicle_post_id = bp.id
        WHERE bp.is_active = 1 AND bp.submission_status = 'approved'
        ORDER BY bp.created_at DESC, bp.id DESC
        LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function calendarEvents(?string $from = null, ?string $to = null): array
{
    global $pdo;
    $sql = "SELECT e.*, bp.title AS chronicle_title, bp.excerpt AS chronicle_excerpt,
            bp.slug AS chronicle_slug, bp.image AS chronicle_image,
            (SELECT COUNT(*) FROM blog_post_gallery bpg WHERE bpg.post_id = bp.id) AS gallery_count
        FROM calendar_events e
        LEFT JOIN blog_posts bp ON bp.id = e.chronicle_post_id
        WHERE (e.chronicle_post_id IS NULL OR (bp.is_active = 1 AND bp.submission_status = 'approved'))";
    $params = [];
    if ($from !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $sql .= ' AND e.event_date >= ?'; $params[] = $from; }
    if ($to !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $sql .= ' AND e.event_date <= ?'; $params[] = $to; }
    $sql .= ' ORDER BY e.event_date ASC, e.event_time ASC, e.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function calendarEventPayload(array $event): array
{
    $linked = !empty($event['chronicle_post_id']) && !empty($event['chronicle_slug']);
    $postUrl = $linked ? getChroniclePostUrl((string) $event['chronicle_slug']) : null;
    $galleryUrl = $linked && (int) ($event['gallery_count'] ?? 0) > 0 ? $postUrl . '#chronicle-gallery' : null;
    $image = null;
    if ($linked && !empty($event['chronicle_image'])) {
        $image = getResponsiveUploadImage((string) $event['chronicle_image'], [480])['src'];
    }
    return [
        'id' => (int) $event['id'],
        'sourceType' => $linked ? 'calendar' : 'legacy-calendar',
        'label' => $linked ? 'Dodano z kalendarza' : 'Kalendarz',
        'date' => (string) $event['event_date'],
        'time' => (string) ($event['event_time'] ?? ''),
        'title' => $linked ? (string) $event['chronicle_title'] : (string) $event['title'],
        'description' => $linked ? (string) ($event['chronicle_excerpt'] ?? '') : (string) ($event['description'] ?? ''),
        'place' => (string) ($event['location'] ?? ''),
        'type' => (string) $event['category'],
        'chronicleId' => $event['chronicle_post_id'] !== null ? (int) $event['chronicle_post_id'] : null,
        'postUrl' => $postUrl,
        'galleryUrl' => $galleryUrl,
        'image' => $image,
    ];
}

function calendarChroniclePayload(array $post): array
{
    $postUrl = getChroniclePostUrl((string) $post['slug']);
    $hasGallery = (int) ($post['gallery_count'] ?? 0) > 0;
    return [
        'id' => 'chronicle-' . (int) $post['id'],
        'sourceType' => 'chronicle',
        'label' => 'Kronika Miasta',
        'date' => substr((string) $post['created_at'], 0, 10),
        'time' => '',
        'title' => (string) $post['title'],
        'description' => (string) ($post['excerpt'] ?? ''),
        'place' => 'Kronika Miasta',
        'type' => 'Kronika',
        'chronicleId' => (int) $post['id'],
        'postUrl' => $postUrl,
        'galleryUrl' => $hasGallery ? $postUrl . '#chronicle-gallery' : null,
        'image' => !empty($post['image']) ? getResponsiveUploadImage((string) $post['image'], [480])['src'] : null,
    ];
}

function calendarClientIpHash(): string
{
    return hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . hash('sha256', DB_NAME));
}

function calendarCanPost(): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM calendar_events WHERE created_ip_hash = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)');
    $stmt->execute([calendarClientIpHash()]);
    return (int) $stmt->fetchColumn() < 20;
}

function calendarPublicCsrfToken(): string
{
    if (empty($_SESSION['calendar_public_csrf'])) $_SESSION['calendar_public_csrf'] = bin2hex(random_bytes(32));
    return (string) $_SESSION['calendar_public_csrf'];
}

function calendarVerifyPublicCsrf(?string $token): void
{
    if (!is_string($token) || !hash_equals(calendarPublicCsrfToken(), $token)) {
        throw new RuntimeException('Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
    }
}
