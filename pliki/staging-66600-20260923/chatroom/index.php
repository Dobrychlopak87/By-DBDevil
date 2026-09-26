<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Chat room';
$csrfToken = chat_csrf_token();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self' data: blob:; base-uri 'self'; form-action 'self'; frame-ancestors 'self'">
    <title><?= sanitize($pageTitle) ?> | 66600.PL</title>
    <script src="assets/theme-init.js?v=1"></script>
    <link rel="stylesheet" href="assets/chat.css?v=11">
</head>
<body>
    <a class="skip-link" href="#chat-messages">Przejdź do rozmowy</a>

    <main class="chat-shell" data-csrf-token="<?= sanitize($csrfToken) ?>">
        <header class="chat-topbar">
            <a class="brand" href="<?= SITE_URL ?>/" aria-label="Powrót na stronę główną 66600.pl">66600<span>.PL</span></a>
            <p class="room-title">Chat room</p>
            <div class="topbar-actions">
                <span class="connection-status" id="connection-status" role="status" aria-live="polite">Łączenie…</span>
                <button class="theme-toggle" type="button" id="theme-toggle" aria-pressed="false" title="Zmień motyw">Tryb jasny</button>
                <button class="text-button notification-toggle" type="button" id="sound-toggle" aria-pressed="true" title="Włącz lub wyłącz dźwięk nowych wiadomości">Dźwięk wł.</button>
                <button class="text-button" type="button" id="open-private" hidden>Napisz do administratora</button>
            </div>
        </header>

        <section class="chat-layout" aria-label="Pokój czatu">
            <section class="conversation-panel" aria-label="Wspólna rozmowa">
                <div class="chat-alert" id="chat-alert" role="status" aria-live="polite" hidden></div>
                <button class="new-messages-notice" id="new-messages-notice" type="button" hidden>Nowe wiadomości</button>
                <div class="messages" id="chat-messages" aria-live="polite" aria-label="Wiadomości czatu" tabindex="0">
                    <p class="empty-state">Ładowanie wiadomości…</p>
                </div>
            </section>

            <aside class="people-panel" aria-labelledby="users-title">
                <div class="people-header">
                    <h2 id="users-title">Użytkownicy</h2>
                    <button type="button" class="panel-toggle" id="users-toggle" aria-expanded="true" aria-controls="users-list">Zwiń</button>
                </div>
                <div class="users-list" id="users-list" aria-live="polite"></div>
                <section class="blocked-nicknames" id="blocked-nicknames" aria-labelledby="blocked-nicknames-title" hidden>
                    <h3 id="blocked-nicknames-title">Aktywne blokady nicków</h3>
                    <div id="blocked-nicknames-list" aria-live="polite"></div>
                </section>
            </aside>
        </section>
    </main>

    <footer class="composer-dock" id="composer-dock" aria-label="Napisz wiadomość">
        <form class="message-form" id="message-form" novalidate>
            <div class="image-preview" id="image-preview" hidden>
                <img id="image-preview-image" alt="Podgląd wybranego zdjęcia">
                <button type="button" class="remove-image" id="remove-image" aria-label="Usuń wybrane zdjęcie">×</button>
            </div>
            <div class="composer-row">
                <button class="composer-icon" id="select-image" type="button" aria-label="Dodaj zdjęcie" title="Dodaj zdjęcie">+</button>
                <input class="sr-only" id="image-input" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                <label class="sr-only" for="message-input">Napisz wiadomość</label>
                <textarea id="message-input" name="message" rows="1" maxlength="500" placeholder="Napisz wiadomość…" disabled></textarea>
                <span class="char-counter"><span id="message-length">0</span>/500</span>
                <button class="send-button" type="submit" id="send-message" disabled aria-label="Wyślij" title="Wyślij">
                    <span class="send-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M20.34 9.32l-14-7a3 3 0 0 0-4.08 3.9l2.4 5.37a1.06 1.06 0 0 1 0 .82l-2.4 5.37A3 3 0 0 0 5 22a3.14 3.14 0 0 0 1.35-.32l14-7a3 3 0 0 0 0-5.36Zm-.89 3.57-14 7a1 1 0 0 1-1.35-1.3l2.39-5.37A2 2 0 0 0 6.57 13h6.89a1 1 0 0 0 0-2H6.57a2 2 0 0 0-.08-.22L4.1 5.41a1 1 0 0 1 1.35-1.3l14 7a1 1 0 0 1 0 1.78Z"/></svg></span>
                    <span class="send-title">Wyślij</span>
                </button>
            </div>
        </form>
    </footer>

    <section class="modal" id="nickname-modal" role="dialog" aria-modal="true" aria-labelledby="nickname-title" hidden>
        <div class="modal-card entry-card">
            <p class="section-label">WEJŚCIE DO POKOJU</p>
            <h1 id="nickname-title">Wejdź do Chat roomu</h1>
            <p id="nickname-description">Wybierz sposób wejścia.</p>
            <div class="entry-choice" id="entry-choice">
                <form id="nickname-form" novalidate>
                    <label for="nickname-input">Nick tymczasowy</label>
                    <input id="nickname-input" name="nickname" type="text" minlength="3" maxlength="32" autocomplete="nickname" required>
                    <p class="form-error" id="nickname-error" role="alert" hidden></p>
                    <button class="primary-button full-button" type="submit">Wejdź jako gość</button>
                </form>
                <div class="entry-divider"><span>lub</span></div>
                <button class="secondary-button full-button" type="button" id="show-login">Zaloguj się nickiem</button>
                <button class="text-button full-button" type="button" id="show-register">Zarezerwuj nick</button>
            </div>
            <form id="account-login-form" class="entry-form" novalidate hidden>
                <label for="account-login-nickname">Nick</label>
                <input id="account-login-nickname" type="text" minlength="3" maxlength="32" autocomplete="username" required>
                <label for="account-login-password">Hasło <button class="tooltip-button" type="button" title="Hasło jest wymagane przy każdym wejściu. Nie ma możliwości jego odzyskania ani zresetowania." aria-label="Informacja o haśle">?</button></label>
                <input id="account-login-password" type="password" autocomplete="current-password" required>
                <p class="form-error" id="account-login-error" role="alert" hidden></p>
                <button class="primary-button full-button" type="submit">Wejdź do pokoju</button>
                <button class="text-button full-button" type="button" data-entry-back>Wróć</button>
            </form>
            <form id="account-register-form" class="entry-form" novalidate hidden>
                <label for="account-register-nickname">Nick</label>
                <input id="account-register-nickname" type="text" minlength="3" maxlength="32" autocomplete="username" required>
                <label for="account-register-password">Hasło <button class="tooltip-button" type="button" title="Hasło jest przechowywane jako nieodwracalny skrót. Nie można go odczytać, odzyskać ani zresetować." aria-label="Informacja o nieodwracalnym haśle">?</button></label>
                <input id="account-register-password" type="password" minlength="8" autocomplete="new-password" required>
                <label for="account-register-password-confirm">Powtórz hasło</label>
                <input id="account-register-password-confirm" type="password" minlength="8" autocomplete="new-password" required>
                <label class="consent-row"><input id="account-register-terms" type="checkbox" required><span>Rozumiem, że hasła nie można odzyskać ani zresetować.</span></label>
                <p class="form-error" id="account-register-error" role="alert" hidden></p>
                <button class="primary-button full-button" type="submit">Zarezerwuj nick</button>
                <button class="text-button full-button" type="button" data-entry-back>Wróć</button>
            </form>
        </div>
    </section>

    <section class="modal" id="terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-title" aria-describedby="terms-description" hidden>
        <div class="modal-card terms-card">
            <p class="section-label">ZASADY POKOJU</p>
            <h2 id="terms-title">Regulamin Chat roomu</h2>
            <p id="terms-description">Rozmawiajmy bez wulgaryzmów, danych prywatnych i treści, które nie powinny trafiać do przestrzeni publicznej. Wiadomości i zdjęcia są usuwane po 24 godzinach. Nick tymczasowy jest zwalniany po 24 godzinach braku aktywności.</p>
            <label class="consent-row terms-consent-row"><input id="terms-consent" type="checkbox"><span>Akceptuję regulamin Chat roomu.</span></label>
            <p class="form-error" id="terms-error" role="alert" hidden></p>
            <button class="primary-button full-button" type="button" id="accept-terms">Wejdź do pokoju</button>
        </div>
    </section>

    <section class="modal" id="private-modal" role="dialog" aria-modal="true" aria-labelledby="private-title" hidden>
        <div class="modal-card private-card">
            <div class="modal-heading-row">
                <div>
                    <p class="section-label">PRYWATNA SKRZYNKA</p>
                    <h2 id="private-title">Kontakt z administratorem</h2>
                </div>
                <button class="icon-button" type="button" id="close-private" aria-label="Zamknij prywatną skrzynkę">×</button>
            </div>
            <p id="private-target-note">Tu możesz poprosić o zastrzeżenie pseudonimu albo przekazać sprawę administratorowi.</p>
            <div class="private-messages" id="private-messages" aria-live="polite"></div>
            <form id="private-form" novalidate>
                <label class="sr-only" for="private-input">Treść wiadomości prywatnej</label>
                <textarea id="private-input" name="message" rows="3" maxlength="500" placeholder="Napisz prywatną wiadomość…"></textarea>
                <p class="form-error" id="private-error" role="alert" hidden></p>
                <button class="primary-button full-button" type="submit">Wyślij prywatnie</button>
            </form>
        </div>
    </section>

    <script type="module" src="assets/chat.js?v=10"></script>
</body>
</html>
