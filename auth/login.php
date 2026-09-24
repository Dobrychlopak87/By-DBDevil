<?php
require_once __DIR__ . '/lib.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
auth_csrf_token();

if (auth_is_logged_in()) {
    redirect(SITE_URL . '/auth/my.php');
}

$error = '';
$username = trim((string) ($_POST['username'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        auth_verify_csrf($_POST['csrf_token'] ?? null);
        if (!auth_ensure_schema()) {
            throw new RuntimeException('Moduł kont jest chwilowo niedostępny.');
        }
        if (auth_rate_is_blocked('login', $username, AUTH_MAX_LOGIN_ATTEMPTS, AUTH_LOGIN_WINDOW, AUTH_LOGIN_WINDOW)) {
            throw new RuntimeException('Zbyt wiele nieudanych prób. Spróbuj ponownie za 15 minut.');
        }
        $password = (string) ($_POST['password'] ?? '');
        $statement = $pdo->prepare('SELECT id, username, password_hash, role FROM auth_users WHERE username = ? COLLATE utf8mb4_unicode_ci LIMIT 1');
        $statement->execute([$username]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $adminStatement = $pdo->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ? LIMIT 1");
            $adminStatement->execute([$username]);
            $admin = $adminStatement->fetch();
            if ($admin && ($admin['role'] ?? '') === 'admin' && (int) ($admin['is_active'] ?? 1) === 1 && password_verify($password, (string) $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = (int) $admin['id'];
                $_SESSION['username'] = (string) $admin['username'];
                $_SESSION['role'] = 'admin';
                $_SESSION['full_name'] = (string) ($admin['full_name'] ?: $admin['username']);
                $_SESSION['admin_last_activity'] = time();
                clearAdminLoginFailures();
                redirect(SITE_URL . '/admin/dashboard.php');
            }
            auth_rate_record_failure('login', $username, AUTH_MAX_LOGIN_ATTEMPTS, AUTH_LOGIN_WINDOW, AUTH_LOGIN_WINDOW);
            throw new RuntimeException('Nieprawidłowa nazwa użytkownika lub hasło.');
        }
        if (password_needs_rehash((string) $user['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
            $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $options = $algorithm === PASSWORD_BCRYPT ? ['cost' => 12] : [];
            $rehash = $pdo->prepare('UPDATE auth_users SET password_hash = ? WHERE id = ?');
            $rehash->execute([password_hash($password, $algorithm, $options), $user['id']]);
        }
        auth_rate_clear('login', $username);
        auth_set_session($user);
        redirect(SITE_URL . '/auth/my.php');
    } catch (RuntimeException|PDOException $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Logowanie';
$pageDescription = 'Zaloguj się do lokalnego konta pseudonimowego w serwisie 66600.PL.';
require dirname(__DIR__) . '/includes/header.php';
?>
<main class="page-header auth-page">
    <h1>Logowanie</h1>
    <p>Zaloguj się, aby edytować swoje zgłoszenia. Goście nadal mogą dodawać nowe zgłoszenia bez konta.</p>
    <?php if ($error !== ''): ?><div class="public-form-alert error"><?= sanitize($error) ?></div><?php endif; ?>
    <form class="public-form auth-form" method="post" action="<?= SITE_URL ?>/auth/login.php">
        <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
        <div class="public-form-group">
            <label for="username">Nazwa użytkownika</label>
            <input id="username" name="username" type="text" value="<?= sanitize($username) ?>" maxlength="32" autocomplete="username" required>
        </div>
        <div class="public-form-group">
            <label for="password">Hasło</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <div class="public-form-actions">
            <button class="public-submit-button" type="submit">Zaloguj się</button>
            <a class="public-cancel-link" href="<?= SITE_URL ?>/auth/register.php">Załóż konto</a>
        </div>
    </form>
    <p class="public-field-hint">Nie ma odzyskiwania hasła. W razie utraty hasła można założyć nowe konto pod innym pseudonimem.</p>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
