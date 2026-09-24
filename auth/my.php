<?php
require_once __DIR__ . '/lib.php';

$user = auth_require_user();
if (!auth_ensure_schema()) {
    http_response_code(503);
    exit('Moduł kont jest chwilowo niedostępny.');
}
global $pdo;

$adStatement = $pdo->prepare('SELECT id, title, submission_status, is_active, created_at FROM ads WHERE owner_id = ? ORDER BY created_at DESC, id DESC');
$adStatement->execute([(int) $user['id']]);
$myAds = $adStatement->fetchAll();
$postStatement = $pdo->prepare('SELECT id, title, submission_status, is_active, created_at FROM blog_posts WHERE owner_id = ? ORDER BY created_at DESC, id DESC');
$postStatement->execute([(int) $user['id']]);
$myPosts = $postStatement->fetchAll();
$authMessage = (string) ($_SESSION['auth_error'] ?? '');
unset($_SESSION['auth_error']);

$pageTitle = 'Moje zgłoszenia';
$pageDescription = 'Zarządzaj swoimi ogłoszeniami i wpisami w serwisie 66600.PL.';
require dirname(__DIR__) . '/includes/header.php';
?>
<main class="page-header auth-page auth-dashboard">
    <h1>Moje zgłoszenia</h1>
    <p>Zalogowano jako <strong><?= sanitize((string) $user['username']) ?></strong>. Edycja ponownie kieruje zgłoszenie do moderacji.</p>
    <?php if ($authMessage !== ''): ?><div class="public-form-alert error"><?= sanitize($authMessage) ?></div><?php endif; ?>
    <div class="auth-dashboard__actions">
        <a class="public-submit-button" href="<?= SITE_URL ?>/submit-ad.php">Dodaj ogłoszenie</a>
        <a class="public-submit-button secondary" href="<?= SITE_URL ?>/submit-post.php">Dodaj wpis</a>
        <form method="post" action="<?= SITE_URL ?>/auth/logout.php">
            <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
            <button class="public-cancel-link auth-logout-button" type="submit">Wyloguj</button>
        </form>
    </div>

    <section class="auth-list">
        <h2>Moje ogłoszenia</h2>
        <?php if ($myAds === []): ?>
            <p>Nie masz jeszcze zapisanych ogłoszeń tego konta.</p>
        <?php else: ?>
            <?php foreach ($myAds as $ad): ?>
                <article class="auth-list__item">
                    <div><strong><?= sanitize((string) $ad['title']) ?></strong><span><?= sanitize(getSubmissionStatusLabel((string) $ad['submission_status'])) ?></span></div>
                    <div class="auth-list__actions">
                        <a href="<?= SITE_URL ?>/auth/edit-ad.php?id=<?= (int) $ad['id'] ?>">Edytuj</a>
                        <form method="post" action="<?= SITE_URL ?>/auth/delete-ad.php">
                            <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                            <button type="submit">Usuń</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="auth-list">
        <h2>Moje wpisy kroniki</h2>
        <?php if ($myPosts === []): ?>
            <p>Nie masz jeszcze zapisanych wpisów tego konta.</p>
        <?php else: ?>
            <?php foreach ($myPosts as $post): ?>
                <article class="auth-list__item">
                    <div><strong><?= sanitize((string) $post['title']) ?></strong><span><?= sanitize(getSubmissionStatusLabel((string) $post['submission_status'])) ?></span></div>
                    <div class="auth-list__actions">
                        <a href="<?= SITE_URL ?>/auth/edit-post.php?id=<?= (int) $post['id'] ?>">Edytuj</a>
                        <form method="post" action="<?= SITE_URL ?>/auth/delete-post.php">
                            <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                            <button type="submit">Usuń</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>