<?php
// Panel administracyjny - Logowanie
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Sprawdź, czy użytkownik jest już zalogowany
if (isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/dashboard.php');
    exit();
}

$error = '';

// Obsługa formularza logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (isAdminLoginLocked()) {
        $error = 'Zbyt wiele nieudanych prób. Spróbuj ponownie za 15 minut.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Wprowadź nazwę użytkownika i hasło.';
    } else {
        // Sprawdź dane w bazie bez ujawniania, czy sama nazwa użytkownika istnieje.
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password']) && ($user['role'] ?? '') === 'admin') {
            // Zmiana identyfikatora ogranicza ryzyko przejęcia wcześniej nadanej sesji.
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
            $_SESSION['admin_last_activity'] = time();
            clearAdminLoginFailures();
            
            redirect(SITE_URL . '/admin/dashboard.php');
            exit();
        } else {
            registerAdminLoginFailure();
            $error = 'Nieprawidłowa nazwa użytkownika lub hasło.';
        }
    }
}

$pageTitle = 'Logowanie - Panel administracyjny';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/fonts.css?v=<?= ASSET_VERSION ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css?v=<?= ASSET_VERSION ?>">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: #000;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
            font-weight: 700;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: <?php echo !empty($error) ? 'block' : 'none'; ?>;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            padding: 0.875rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.85rem;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">66600</div>
            <h1>Panel administracyjny</h1>
            <p>Zaloguj się, aby zarządzać stroną</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message" style="display: block;"><?= sanitize($error) ?></div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="username">Nazwa użytkownika</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="Wprowadź nazwę użytkownika"
                    required
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Hasło</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Wprowadź hasło"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="login-btn">Zaloguj się</button>
        </form>
        
        <div class="login-footer">
            <p>© 2026 66600.PL. Wszelkie prawa zastrzeżone.</p>
        </div>
    </div>
</body>
</html>
