<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

function apiOut(array $payload, int $status = 200): void
{
    echo jsonResponse($payload, $status);
    exit;
}

function apiError(Throwable $exception): void
{
    apiOut(array('ok' => false, 'message' => $exception->getMessage()), 400);
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = (string)($_GET['action'] ?? '');
    $data = $method === 'POST' ? requestData() : $_GET;

    if ($action === 'session') {
        $user = currentUser($pdo);
        apiOut(array(
            'ok' => true,
            'authenticated' => $user !== null,
            'csrf' => csrfToken(),
            'user' => $user ? array(
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'auth_type' => $user['auth_type'],
                'chips' => (int)$user['chips'],
                'seconds_until_refill' => secondsUntilRefill($user),
            ) : null,
            'site' => pokerSiteClientInfo($user),
        ));
    }

    if ($action === 'guest_enter') {
        requireJsonRequest();
        verifyCsrf($data);
        $username = trim((string)($data['username'] ?? ''));
        if (!preg_match('/^[\p{L}\p{N}_-]{3,24}$/u', $username)) {
            throw new RuntimeException('Nick musi mieć 3–24 znaki i może zawierać litery, cyfry, podkreślenia oraz myślniki.');
        }
        if (pokerSiteIdentity() !== null) {
            throw new RuntimeException('Jesteś zalogowany na stronie — grasz pod nazwą swojego konta. Odśwież stronę.');
        }
        if (szuReservedName($username)) {
            throw new RuntimeException('Ten nick jest zarezerwowany dla komputerowego mistrza. Wybierz inny.');
        }
        if (pokerSiteUsernameTaken($username)) {
            throw new RuntimeException('Ten nick należy do konta na 66600.pl. Zaloguj się na stronie, aby grać pod nim.');
        }
        $check = $pdo->prepare('SELECT auth_type FROM users WHERE username = ? LIMIT 1');
        $check->execute(array($username));
        $existing = $check->fetch();
        if ($existing) {
            if ($existing['auth_type'] === 'reserved') {
                throw new RuntimeException('Ten nick jest zarezerwowany. Użyj opcji „Wejdź z hasłem”.');
            }
            if ($existing['auth_type'] === 'site') {
                throw new RuntimeException('Ten nick należy do konta na 66600.pl. Zaloguj się na stronie, aby grać pod nim.');
            }
            throw new RuntimeException('Ten tymczasowy nick jest właśnie używany. Wybierz inny.');
        }
        $statement = $pdo->prepare("INSERT INTO users (username, password_hash, auth_type, chips, last_refill_at, last_activity_at) VALUES (?, NULL, 'guest', ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
        $statement->execute(array($username, DEFAULT_CHIPS));
        beginUserSession((int)$pdo->lastInsertId());
        apiOut(array('ok' => true, 'message' => 'Witaj, ' . $username . '. Twój nick jest tymczasowy i wygasa po 24 godzinach braku aktywności.'));
    }

    if ($action === 'reserve_nick') {
        requireJsonRequest();
        verifyCsrf($data);
        $user = requireLogin($pdo);
        if ($user['auth_type'] === 'reserved') {
            throw new RuntimeException('Twój nick jest już zarezerwowany.');
        }
        if ($user['auth_type'] === 'site') {
            throw new RuntimeException('Grasz z konta strony — Twój nick jest już chroniony.');
        }
        $password = (string)($data['password'] ?? '');
        if (strlen($password) < 8) {
            throw new RuntimeException('Hasło do rezerwacji nicku musi mieć co najmniej 8 znaków.');
        }
        $save = $pdo->prepare("UPDATE users SET password_hash = ?, auth_type = 'reserved', last_activity_at = UTC_TIMESTAMP() WHERE id = ?");
        $save->execute(array(password_hash($password, PASSWORD_DEFAULT), (int)$user['id']));
        apiOut(array('ok' => true, 'message' => 'Nick „' . $user['username'] . '” został zarezerwowany. Hasła nie można odzyskać ani zresetować.'));
    }

    if ($action === 'reserved_login') {
        requireJsonRequest();
        verifyCsrf($data);
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $statement = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ? AND auth_type = 'reserved' LIMIT 1");
        $statement->execute(array($username));
        $account = $statement->fetch();
        if (!$account || !password_verify($password, (string)$account['password_hash'])) {
            throw new RuntimeException('Nieprawidłowy zarezerwowany nick lub hasło. Hasła nie można odzyskać.');
        }
        beginUserSession((int)$account['id']);
        $user = currentUser($pdo);
        apiOut(array('ok' => true, 'message' => 'Witaj ponownie, ' . $username . '.', 'chips' => (int)$user['chips']));
    }

    if ($action === 'logout') {
        requireJsonRequest();
        verifyCsrf($data);
        $_SESSION = array();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
        apiOut(array('ok' => true));
    }

    $user = requireLogin($pdo);

    if ($action === 'tables') {
        $mySeatStmt = $pdo->prepare('SELECT table_id FROM table_players WHERE user_id = ? LIMIT 1');
        $mySeatStmt->execute(array((int)$user['id']));
        $myTableId = (int)($mySeatStmt->fetchColumn() ?: 0);
        apiOut(array(
            'ok' => true,
            'tables' => fetchTables($pdo),
            'my_table_id' => $myTableId ?: null,
            'user' => array('id' => (int)$user['id'], 'username' => $user['username'], 'auth_type' => $user['auth_type'], 'chips' => (int)$user['chips'], 'seconds_until_refill' => secondsUntilRefill($user)),
        ));
    }

    if ($action === 'create_table') {
        requireJsonRequest();
        verifyCsrf($data);
        $name = trim((string)($data['name'] ?? ''));
        if (!preg_match('/^[\p{L}\p{N}\s_-]{3,32}$/u', $name)) {
            throw new RuntimeException('Nazwa stołu musi mieć 3–32 znaki.');
        }
        $state = pokerDefaultState();
        $statement = $pdo->prepare('INSERT INTO poker_tables (name, state_json) VALUES (?, ?)');
        $statement->execute(array($name, json_encode($state, JSON_UNESCAPED_UNICODE)));
        $tableId = (int)$pdo->lastInsertId();
        seatPlayer($pdo, $tableId, (int)$user['id']);
        apiOut(array('ok' => true, 'table_id' => $tableId, 'message' => 'Utworzono stół ' . $name . '.'));
    }

    if ($action === 'play_szu') {
        requireJsonRequest();
        verifyCsrf($data);
        $opponents = max(1, min(MAX_PLAYERS - 1, (int)($data['opponents'] ?? 1)));
        $mySeat = $pdo->prepare('SELECT table_id FROM table_players WHERE user_id = ? LIMIT 1');
        $mySeat->execute(array((int)$user['id']));
        if ($mySeat->fetchColumn()) {
            throw new RuntimeException('Najpierw opuść aktualny stół.');
        }
        if ((int)$user['chips'] < BIG_BLIND) {
            throw new RuntimeException('Masz za mało punktów, aby zagrać. Poczekaj na dzienne odnowienie puli.');
        }
        $name = $opponents === 1 ? 'Pojedynek z Wielkim Szu' : 'Wielki Szu x' . $opponents;
        $statement = $pdo->prepare('INSERT INTO poker_tables (name, state_json) VALUES (?, ?)');
        $szuState = pokerDefaultState();
        $szuState['szu_table'] = true;
        $statement->execute(array($name, json_encode($szuState, JSON_UNESCAPED_UNICODE)));
        $tableId = (int)$pdo->lastInsertId();
        try {
            seatPlayer($pdo, $tableId, (int)$user['id']);
            for ($i = 0; $i < $opponents; $i++) {
                szuAddBot($pdo, $tableId, (int)$user['id']);
            }
        } catch (Throwable $exception) {
            $pdo->prepare('DELETE FROM poker_tables WHERE id = ?')->execute(array($tableId));
            throw $exception;
        }
        tickTable($pdo, $tableId);
        apiOut(array('ok' => true, 'table_id' => $tableId, 'message' => $opponents === 1 ? 'Wielki Szu zasiada do stołu. Powodzenia!' : 'Wielki Szu przyprowadził wsparcie. Powodzenia!'));
    }

    if ($action === 'add_bot') {
        requireJsonRequest();
        verifyCsrf($data);
        $tableId = (int)($data['table_id'] ?? 0);
        if ($tableId < 1) { throw new RuntimeException('Nie wskazano stołu.'); }
        szuAddBot($pdo, $tableId, (int)$user['id']);
        tickTable($pdo, $tableId);
        apiOut(array('ok' => true, 'message' => 'Wielki Szu dosiada się do stołu.', 'game' => tableStateForUser($pdo, $tableId, (int)$user['id'])));
    }

    if ($action === 'remove_bot') {
        requireJsonRequest();
        verifyCsrf($data);
        $tableId = (int)($data['table_id'] ?? 0);
        $botId = (int)($data['bot_id'] ?? 0);
        szuRemoveBot($pdo, $tableId, $botId, (int)$user['id']);
        tickTable($pdo, $tableId);
        apiOut(array('ok' => true, 'message' => 'Wielki Szu odchodzi od stołu.', 'game' => tableStateForUser($pdo, $tableId, (int)$user['id'])));
    }

    if ($action === 'resume') {
        requireJsonRequest();
        verifyCsrf($data);
        $tableId = (int)($data['table_id'] ?? 0);
        szuRequireSeated($pdo, $tableId, (int)$user['id']);
        resumePlayer($pdo, $tableId, (int)$user['id']);
        tickTable($pdo, $tableId);
        apiOut(array('ok' => true, 'game' => tableStateForUser($pdo, $tableId, (int)$user['id'])));
    }

    if ($action === 'join_table') {
        requireJsonRequest();
        verifyCsrf($data);
        $tableId = (int)($data['table_id'] ?? 0);
        if ($tableId < 1) { throw new RuntimeException('Wybierz prawidłowy stół.'); }
        seatPlayer($pdo, $tableId, (int)$user['id']);
        tickTable($pdo, $tableId);
        apiOut(array('ok' => true, 'table_id' => $tableId));
    }

    if ($action === 'leave_table') {
        requireJsonRequest();
        verifyCsrf($data);
        $tableId = (int)($data['table_id'] ?? 0);
        leaveTable($pdo, $tableId, (int)$user['id']);
        apiOut(array('ok' => true, 'message' => 'Opuściłeś stół.'));
    }

    if ($action === 'table_state') {
        $tableId = (int)($data['table_id'] ?? 0);
        if ($tableId < 1) { throw new RuntimeException('Nie wskazano stołu.'); }
        tickTable($pdo, $tableId);
        $state = tableStateForUser($pdo, $tableId, (int)$user['id']);
        apiOut(array('ok' => true, 'game' => $state, 'csrf' => csrfToken()));
    }

    if ($action === 'chat_invite') {
        requireJsonRequest();
        verifyCsrf($data);
        $tableId = (int)($data['table_id'] ?? 0);
        if ($tableId < 1) { throw new RuntimeException('Nie wskazano stołu.'); }
        try {
            $result = pokerSendChatInvite($pdo, $user, $tableId);
        } catch (RuntimeException $exception) {
            $retry = 0;
            if (preg_match('/za (\d+) s\./u', $exception->getMessage(), $m) === 1) { $retry = (int)$m[1]; }
            apiOut(array('ok' => false, 'message' => $exception->getMessage(), 'retry_after' => $retry), $retry > 0 ? 429 : 400);
        }
        apiOut(array('ok' => true) + $result);
    }

    if ($action === 'game_action') {
        requireJsonRequest();
        verifyCsrf($data);
        $tableId = (int)($data['table_id'] ?? 0);
        $gameAction = (string)($data['game_action'] ?? '');
        $raiseTo = (int)($data['raise_to'] ?? 0);
        // Aktualizuje ewentualnie wygasłą turę przed próbą ręcznej akcji.
        tickTable($pdo, $tableId);
        makePlayerAction($pdo, $tableId, (int)$user['id'], $gameAction, $raiseTo);
        $state = tableStateForUser($pdo, $tableId, (int)$user['id']);
        apiOut(array('ok' => true, 'game' => $state));
    }

    throw new RuntimeException('Nieznana operacja.');
} catch (Throwable $exception) {
    apiError($exception);
}
