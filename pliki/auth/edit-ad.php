<?php
require_once __DIR__ . '/lib.php';

$user = auth_require_user();
if (!auth_ensure_schema()) {
    http_response_code(503);
    exit('Moduł kont jest chwilowo niedostępny.');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$ad = auth_owned_ad($id, (int) $user['id']);
if (!$ad) {
    http_response_code(404);
    exit('Ogłoszenie nie zostało znalezione.');
}

$categories = getAdSelectableCategories();
$validCategoryIds = array_map('intval', array_column($categories, 'id'));
$error = '';
$title = (string) $ad['title'];
$description = (string) $ad['description'];
$categoryId = (int) $ad['category_id'];
$location = (string) ($ad['location'] ?? '');
$phone = (string) ($ad['phone'] ?? '');
$email = (string) ($ad['email'] ?? '');
$address = (string) ($ad['address'] ?? '');
$link = (string) ($ad['link'] ?? '');
$profileSubtitle = (string) ($ad['profile_subtitle'] ?? '');
$profileTags = implode(', ', getAdProfileTags($ad));
$contactUrl = (string) ($ad['contact_url'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        auth_verify_csrf($_POST['csrf_token'] ?? null);
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $location = trim((string) ($_POST['location'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $link = trim((string) ($_POST['link'] ?? ''));
        $profileSubtitle = trim((string) ($_POST['profile_subtitle'] ?? ''));
        $profileTags = trim((string) ($_POST['profile_tags'] ?? ''));
        $contactUrl = trim((string) ($_POST['contact_url'] ?? ''));
        $length = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        if ($title === '' || $length > 255) {
            throw new InvalidArgumentException('Podaj tytuł ogłoszenia (maksymalnie 255 znaków).');
        }
        if ($description === '') {
            throw new InvalidArgumentException('Dodaj treść ogłoszenia.');
        }
        if (!in_array($categoryId, $validCategoryIds, true)) {
            throw new InvalidArgumentException('Wybierz poprawną kategorię.');
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Podaj poprawny adres e-mail.');
        }
        if ($link !== '' && filter_var($link, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Podaj poprawny pełny adres linku.');
        }
        if (normalizeAdContactUrl($contactUrl) === '' && $phone === '' && $email === '') {
            throw new InvalidArgumentException('Podaj link kontaktowy, telefon lub e-mail.');
        }

        global $pdo;
        $statement = $pdo->prepare(
            "UPDATE ads SET title = ?, description = ?, category_id = ?, location = ?, phone = ?, email = ?,
             address = ?, link = ?, profile_subtitle = ?, profile_tags = ?, contact_url = ?,
             is_active = 0, submission_status = 'pending'
             WHERE id = ? AND owner_id = ?"
        );
        $statement->execute([
            $title,
            $description,
            $categoryId,
            $location !== '' ? $location : null,
            $phone !== '' ? $phone : null,
            $email !== '' ? $email : null,
            $address !== '' ? $address : null,
            $link !== '' ? $link : null,
            $profileSubtitle !== '' ? $profileSubtitle : null,
            json_encode(normalizeAdProfileTags($profileTags), JSON_UNESCAPED_UNICODE),
            normalizeAdContactUrl($contactUrl),
            $id,
            (int) $user['id']
        ]);
        redirect(SITE_URL . '/auth/my.php');
    } catch (InvalidArgumentException|RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Edytuj ogłoszenie';
$pageDescription = 'Edytuj własne ogłoszenie w serwisie 66600.PL.';
require dirname(__DIR__) . '/includes/header.php';
?>
<main class="page-header auth-page">
    <h1>Edytuj ogłoszenie</h1>
    <p>Po zapisaniu ogłoszenie wróci do moderacji i nie będzie publiczne do czasu akceptacji.</p>
    <?php if ($error !== ''): ?><div class="public-form-alert error"><?= sanitize($error) ?></div><?php endif; ?>
    <form class="public-form auth-form" method="post" action="<?= SITE_URL ?>/auth/edit-ad.php?id=<?= $id ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
        <div class="public-form-group"><label for="title">Tytuł</label><input id="title" name="title" maxlength="255" value="<?= sanitize($title) ?>" required></div>
        <div class="public-form-group"><label for="description">Treść</label><textarea id="description" name="description" rows="8" required><?= sanitize($description) ?></textarea></div>
        <div class="public-form-group"><label for="category_id">Kategoria</label><select id="category_id" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= sanitize($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="public-form-row">
            <div class="public-form-group"><label for="location">Lokalizacja</label><input id="location" name="location" maxlength="255" value="<?= sanitize($location) ?>"></div>
            <div class="public-form-group"><label for="address">Adres</label><input id="address" name="address" maxlength="255" value="<?= sanitize($address) ?>"></div>
        </div>
        <div class="public-form-row">
            <div class="public-form-group"><label for="phone">Telefon</label><input id="phone" name="phone" maxlength="40" value="<?= sanitize($phone) ?>"></div>
            <div class="public-form-group"><label for="email">E-mail</label><input id="email" name="email" maxlength="255" value="<?= sanitize($email) ?>"></div>
        </div>
        <div class="public-form-group"><label for="contact_url">Link kontaktowy</label><input id="contact_url" name="contact_url" maxlength="500" value="<?= sanitize($contactUrl) ?>"></div>
        <div class="public-form-group"><label for="link">Link ogłoszenia</label><input id="link" name="link" maxlength="500" value="<?= sanitize($link) ?>"></div>
        <div class="public-form-group"><label for="profile_subtitle">Podtytuł</label><input id="profile_subtitle" name="profile_subtitle" maxlength="255" value="<?= sanitize($profileSubtitle) ?>"></div>
        <div class="public-form-group"><label for="profile_tags">Tagi</label><input id="profile_tags" name="profile_tags" value="<?= sanitize($profileTags) ?>"><span class="public-field-hint">Oddziel tagi przecinkami.</span></div>
        <div class="public-form-actions"><button class="public-submit-button" type="submit">Zapisz do moderacji</button><a class="public-cancel-link" href="<?= SITE_URL ?>/auth/my.php">Anuluj</a></div>
    </form>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>