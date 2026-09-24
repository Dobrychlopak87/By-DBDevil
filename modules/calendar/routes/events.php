<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/includes/config.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/auth/lib.php';
require_once dirname(__DIR__) . '/contract.php';

auth_ensure_schema();
ensureCalendarSchema();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function calendarJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function calendarLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function calendarUploadedFiles(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['tmp_name'] ?? null)) return [];
    $files = [];
    foreach ($_FILES[$field]['tmp_name'] as $index => $tmpName) {
        $files[] = [
            'name' => $_FILES[$field]['name'][$index] ?? '',
            'type' => $_FILES[$field]['type'][$index] ?? '',
            'tmp_name' => $tmpName,
            'error' => $_FILES[$field]['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES[$field]['size'][$index] ?? 0,
        ];
    }
    return $files;
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $events = array_map('calendarEventPayload', calendarEvents($_GET['from'] ?? null, $_GET['to'] ?? null));
        $chronicle = calendarChronicleEntries();
        $linked = [];
        foreach ($events as $event) {
            if ($event['chronicleId'] !== null) $linked[(int) $event['chronicleId']] = true;
        }
        foreach ($chronicle as $post) {
            if (!isset($linked[(int) $post['id']])) $events[] = calendarChroniclePayload($post);
        }
        usort($events, static function (array $left, array $right): int {
            return [$left['date'], $left['time'], (string) $left['id']] <=> [$right['date'], $right['time'], (string) $right['id']];
        });
        calendarJson(['ok' => true, 'events' => $events, 'chronicle' => $chronicle]);
    }
    if ($method !== 'POST') calendarJson(['ok' => false, 'error' => 'Niedozwolona metoda.'], 405);

    calendarVerifyPublicCsrf($_POST['csrf_token'] ?? null);
    if (trim((string) ($_POST['website'] ?? '')) !== '') calendarJson(['ok' => false, 'error' => 'Nieprawidłowe zgłoszenie.'], 400);
    if (!calendarCanPost()) calendarJson(['ok' => false, 'error' => 'Osiągnięto limit nowych wpisów. Spróbuj później.'], 429);

    $title = trim((string) ($_POST['title'] ?? ''));
    $date = trim((string) ($_POST['date'] ?? ''));
    $time = trim((string) ($_POST['time'] ?? ''));
    $place = trim((string) ($_POST['place'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $category = trim((string) ($_POST['type'] ?? 'Mieszkańcy'));
    $signature = trim((string) ($_POST['author_signature'] ?? ''));
    $chronicleId = (int) ($_POST['chronicleId'] ?? 0);
    $user = auth_current_user();
    if ($signature === '' && $user !== null) $signature = (string) $user['username'];

    $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Europe/Warsaw'));
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$dateObject || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) calendarJson(['ok' => false, 'error' => 'Podaj prawidłową datę.'], 422);
    if ($title === '' || calendarLength($title) > 180) calendarJson(['ok' => false, 'error' => 'Tytuł jest wymagany i może mieć maksymalnie 180 znaków.'], 422);
    if ($description === '' || calendarLength($description) > 10000) calendarJson(['ok' => false, 'error' => 'Treść wydarzenia jest wymagana i może mieć maksymalnie 10 000 znaków.'], 422);
    if ($signature === '' || calendarLength($signature) > 120) calendarJson(['ok' => false, 'error' => 'Podpis autora jest wymagany i może mieć maksymalnie 120 znaków.'], 422);
    if (calendarLength($time) > 20 || calendarLength($place) > 180) calendarJson(['ok' => false, 'error' => 'Godzina lub miejsce są zbyt długie.'], 422);
    if (!in_array($category, calendarCategories(), true)) $category = 'Mieszkańcy';

    $mainImage = $_FILES['image'] ?? null;
    $galleryFiles = calendarUploadedFiles('gallery');
    $galleryFiles = array_values(array_filter($galleryFiles, static fn(array $file): bool => $file['error'] !== UPLOAD_ERR_NO_FILE));
    if (count($galleryFiles) > 6) calendarJson(['ok' => false, 'error' => 'Możesz dodać maksymalnie 6 dodatkowych zdjęć.'], 422);

    global $pdo;
    $uploaded = [];
    $pdo->beginTransaction();
    try {
        $mainFilename = null;
        if (is_array($mainImage) && ($mainImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $result = uploadFile($mainImage);
            if (!$result['success']) throw new InvalidArgumentException($result['message']);
            $mainFilename = $result['filename'];
            $uploaded[] = $mainFilename;
        }

        $categories = getCommunityBlogCategories();
        $categoryId = (int) ($categories[0]['id'] ?? 0);
        if ($categoryId <= 0) throw new RuntimeException('Brak aktywnej kategorii Kroniki.');
        $ownerId = auth_session_user_id() ?: null;
        $postId = createCommunityBlogSubmission([
            'owner_id' => $ownerId,
            'title' => $title,
            'content' => $description,
            'image' => $mainFilename,
            'category_id' => $categoryId,
            'author_signature' => $signature,
            'author_source' => $user !== null ? 'account' : 'community',
        ]);
        if ($postId <= 0) throw new RuntimeException('Nie udało się utworzyć wpisu Kroniki.');
        $sourceUpdate = $pdo->prepare("UPDATE blog_posts SET source_type = 'calendar' WHERE id = ?");
        $sourceUpdate->execute([$postId]);

        $descriptions = $_POST['gallery_descriptions'] ?? [];
        foreach ($galleryFiles as $index => $file) {
            $result = uploadFile($file);
            if (!$result['success']) throw new InvalidArgumentException($result['message']);
            $uploaded[] = $result['filename'];
            addBlogPostGalleryImage($postId, $result['filename'], trim((string) ($descriptions[$index] ?? '')));
        }

        $eventStatement = $pdo->prepare('INSERT INTO calendar_events (event_date, event_time, title, description, location, category, chronicle_post_id, created_ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $eventStatement->execute([$date, $time, $title, $description, $place, $category, $postId, calendarClientIpHash()]);
        $eventId = (int) $pdo->lastInsertId();
        $pdo->commit();
        $_SESSION['calendar_last_submission_at'] = time();
        $_SESSION['calendar_public_csrf'] = bin2hex(random_bytes(32));
        calendarJson(['ok' => true, 'message' => 'Wydarzenie przekazano do moderacji.', 'event_id' => $eventId, 'post_id' => $postId]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($uploaded as $filename) deleteFile($filename);
        throw $exception;
    }
} catch (InvalidArgumentException $exception) {
    calendarJson(['ok' => false, 'error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log('66600 calendar API: ' . $exception->getMessage());
    calendarJson(['ok' => false, 'error' => 'Nie udało się zapisać wydarzenia.'], 500);
}
