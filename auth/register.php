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
        if (auth_rate_is_blocked('register', $username, AUTH_MAX_REGISTER_ATTEMPTS, AUTH_REGISTER_WINDOW, AUTH_REGISTER_WINDOW)) {
            throw new RuntimeException('Zbyt wiele prób rejestracji. Spróbuj ponownie później.');
        }
        $usernameError = auth_validate_username($username);
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        if ($usernameError !== null) {
            throw new InvalidArgumentException($usernameError);
        }
        $passwordError = auth_validate_password($password, $username);
        if ($passwordError !== null) {
            throw new InvalidArgumentException($passwordError);
        }
        if (!hash_equals($password, $passwordConfirmation)) {
            throw new InvalidArgumentException('Hasła muszą być identyczne.');
        }
        if (auth_username_exists($username)) {
            throw new InvalidArgumentException('Ta nazwa użytkownika jest już zajęta.');
        }

        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $options = $algorithm === PASSWORD_BCRYPT ? ['cost' => 12] : [];
        $statement = $pdo->prepare('INSERT INTO auth_users (username, password_hash, role) VALUES (?, ?, 0)');
        $statement->execute([$username, password_hash($password, $algorithm, $options)]);
        $user = [
            'id' => (int) $pdo->lastInsertId(),
            'username' => $username,
            'role' => 0
        ];
        auth_rate_clear('register', $username);
        auth_set_session($user);
        redirect(SITE_URL . '/auth/my.php');
    } catch (InvalidArgumentException|RuntimeException|PDOException $exception) {
        if ($exception instanceof PDOException && (int) $exception->errorInfo[1] === 1062) {
            $error = 'Ta nazwa użytkownika jest już zajęta.';
        } else {
            $error = $exception->getMessage();
        }
        auth_rate_record_failure('register', $username, AUTH_MAX_REGISTER_ATTEMPTS, AUTH_REGISTER_WINDOW, AUTH_REGISTER_WINDOW);
    }
}

$pageTitle = 'Załóż konto';
$pageDescription = 'Załóż lokalne konto pseudonimowe w serwisie 66600.PL.';
require dirname(__DIR__) . '/includes/header.php';
?>
<main class="page-header auth-page">
    <h1>Załóż konto</h1>
    <p>Konto jest lokalnym pseudonimem. Nie wymagamy e-maila, telefonu ani potwierdzenia.</p>
    <?php if ($error !== ''): ?><div class="public-form-alert error"><?= sanitize($error) ?></div><?php endif; ?>
    <form class="public-form auth-form" method="post" action="<?= SITE_URL ?>/auth/register.php">
        <input type="hidden" name="csrf_token" value="<?= sanitize(auth_csrf_token()) ?>">
        <div class="public-form-group">
            <label for="username">Nazwa użytkownika</label>
            <input id="username" name="username" type="text" value="<?= sanitize($username) ?>" minlength="3" maxlength="32" autocomplete="username" required>
            <span class="public-field-hint">3–32 znaki: litery, cyfry, kropka, myślnik lub podkreślenie.</span>
        </div>
        <div class="public-form-group">
            <label for="password">Hasło</label>
            <input id="password" name="password" type="password" minlength="10" autocomplete="new-password" required>
            <span class="public-field-hint">Minimum 10 znaków, w tym wielka litera, mała litera i cyfra.</span>
        </div>
        <div class="public-form-group">
            <label for="password_confirmation">Powtórz hasło</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="10" autocomplete="new-password" required>
        </div>
        <div class="public-form-actions">
            <button class="public-submit-button" type="submit">Utwórz konto</button>
            <a class="public-cancel-link" href="<?= SITE_URL ?>/auth/login.php">Mam już konto</a>
        </div>
    </form>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
