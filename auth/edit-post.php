<?php
require_once __DIR__ . '/lib.php';

$user = auth_require_user();
if (!auth_ensure_schema()) {
    http_response_code(503);
    exit('Moduł kont jest chwilowo niedostępny.');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$post = auth_owned_post($id, (int) $user['id']);
if (!$post) {
    http_response_code(404);
    exit('Wpis nie został znaleziony.');
}

$categories = getBlogCategories();
$validCategoryIds = array_map('intval', array_column($categories, 'id'));
$title = (string) $post['title'];
$content = html_entity_decode(strip_tags((string) $post['content']), ENT_QUOTES, 'UTF-8');
$categoryId = (int) $post['category_id'];
$authorSignature = (string) ($post['author_signature'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        auth_verify_csrf($_POST['csrf_token'] ?? null);
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $authorSignature = trim((string) ($_POST['author_signature'] ?? ''));
        $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        $contentLength = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
        if ($title === '' || $titleLength > 255) {
            throw new InvalidArgumentException('Podaj tytuł wpisu (maksymalnie 255 znaków).');
        }
        if ($content === '' || $contentLength > 10000) {
            throw new InvalidArgumentException('Treść wpisu jest wymagana i może mieć maksymalnie 10 000 znaków.');
        }
        if (!in_array($categoryId, $validCategoryIds, true)) {
            throw new InvalidArgumentException('Wybierz kategorię kroniki.');
        }
        if ((function_exists('mb_strlen') ? mb_strlen($authorSignature, 'UTF-8') : strlen($authorSignature)) > 120) {
            throw new InvalidArgumentException('Podpis autora może mieć maksymalnie 120 znaków.');
        }

        $paragraphs = preg_split('/(?:\r\n|\r|\n){2,}/u', $content);
        $parts = [];
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph !== '') {
                $parts[] = '<p>' . nl2br(sanitize($paragraph), false) . '</p>';
            }
        }
        global $pdo;
        $statement = $pdo->prepare(
            "UPDATE blog_posts SET title = ?, content = ?, excerpt = ?, category_id = ?, author_signature = ?,
             submission_status = 'pending', is_active = 0 WHERE id = ? AND owner_id = ?"
        );
        $statement->execute([
            $title,
            implode("\n", $parts),
            createMetaDescription($content),
            $categoryId,
            $authorSignature !== '' ? $authorSignature : null,
            $id,
            (int) $user['id']
        ]);
        redirect(SITE_URL . '/auth/my.php');
    } catch (InvalidArgumentException|RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Edytuj wpis kroniki';
$pageDescription = 'Edytuj własny wpis kroniki w serwisie 66600.PL.';
require dirname(__DIR__) . '/includes/header.php';
?>
<main class="page-header auth-page">
    <h1>Edytuj wpis kroniki</h1>
    <p>Po zapisaniu wpis wróci do moderacji i nie będzie publiczny do czasu akceptacji.</p>
    <?php if ($error !== ''): ?><div class="public-form-alert error"><?= sanitize($error) ?></div><?php endif; ?>
    <form class="public-form auth-form" method="post" action="<?= SITE_URL ?>/auth/edit-post.php?id=<?= $id ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
        <div class="public-form-group"><label for="title">Tytuł</label><input id="title" name="title" maxlength="255" value="<?= sanitize($title) ?>" required></div>
        <div class="public-form-group"><label for="content">Treść wpisu</label><textarea id="content" name="content" rows="12" maxlength="10000" required><?= sanitize($content) ?></textarea></div>
        <div class="public-form-group"><label for="category_id">Kategoria</label><select id="category_id" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= sanitize($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="public-form-group"><label for="author_signature">Podpis autora</label><input id="author_signature" name="author_signature" maxlength="120" value="<?= sanitize($authorSignature) ?>"></div>
        <div class="public-form-actions"><button class="public-submit-button" type="submit">Zapisz do moderacji</button><a class="public-cancel-link" href="<?= SITE_URL ?>/auth/my.php">Anuluj</a></div>
    </form>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>