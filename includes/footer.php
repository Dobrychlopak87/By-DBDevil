<?php
// Wspólny interfejs udostępniania dla części publicznej serwisu.
$isAdminPage = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false;
$shareUrl = $canonicalUrl ?? (SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/'));
$shareTitle = $pageTitle ?? SITE_NAME;
$shareText = $pageDescription ?? getSiteSettings()['description'];
if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}
$contactCsrf = (string) $_SESSION['contact_csrf'];
if (!$isAdminPage) {
    require_once __DIR__ . '/pulse.php';
    pulseEnsureSchema();
    $pulseTickerItems = pulseTickerItems();
    $pulseCsrfToken = pulseCsrfToken();
} else {
    $pulseTickerItems = [];
    $pulseCsrfToken = '';
}
?>
    </main>

    <?php if (!$isAdminPage): ?>
    <div class="legal-footer" aria-label="Informacje prawne">
        <a href="<?= SITE_URL ?>/privacy-policy.php">Prywatność</a>
    </div>

    <!-- Dolna nawigacja części publicznej -->
    <nav class="bottom-nav" aria-label="Szybkie działania">
        <div class="bottom-nav-container">
            <button type="button" class="bottom-nav-btn pill-btn share-btn"
                    data-share-url="<?= sanitize($shareUrl) ?>"
                    data-share-title="<?= sanitize($shareTitle) ?>"
                    data-share-text="<?= sanitize($shareText) ?>">
                <?= getCategorySvg('share') ?>
                <span>Udostępnij</span>
            </button>

            <a class="plus-btn-circle" id="plus-btn" href="<?= SITE_URL ?>/submit-ad.php" data-open-submission="ads" aria-label="Dodaj ogłoszenie" title="Dodaj ogłoszenie">
                <?= getCategorySvg('plus') ?>
            </a>

            <button type="button" class="bottom-nav-btn pill-btn contact-btn" id="contact-btn">
                <?= getCategorySvg('email') ?>
                <span>Kontakt</span>
            </button>
        </div>
        <div class="pulse-ticker-shell" data-pulse-ticker-shell data-pulse-api="<?= SITE_URL ?>/pulse-api.php">
            <iframe id="pulse-ticker-frame" class="pulse-ticker-frame" src="<?= SITE_URL ?>/assets/pulse/pulse-ticker.html?v=<?= ASSET_VERSION ?>" title="Puls miasta" scrolling="no" tabindex="0"></iframe>
        </div>
    </nav>

    <!-- Tryb zapasowy dla przeglądarek bez systemowego udostępniania -->
    <div id="share-modal" class="modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="share-modal-title">
        <div class="modal-content share-modal-content">
            <div class="modal-header">
                <h3 id="share-modal-title">Udostępnij</h3>
                <button type="button" class="modal-close" id="close-share-modal" aria-label="Zamknij">×</button>
            </div>
            <div class="modal-body share-modal-body">
                <p>Wybierz dostępny sposób udostępnienia lub skopiuj link.</p>
                <input type="text" id="share-link" readonly aria-label="Link do udostępnienia">
                <div class="share-fallback-actions">
                    <button type="button" id="copy-share-link" class="copy-btn">Kopiuj link</button>
                    <a id="share-facebook" class="share-provider-btn" target="_blank" rel="noopener noreferrer">Facebook</a>
                    <a id="share-email" class="share-provider-btn" rel="noopener noreferrer">E-mail</a>
                </div>
                <p class="share-modal-note">Na telefonie i urządzeniach z obsługą systemowego udostępniania pojawi się lista aplikacji, np. Facebook, TikTok lub komunikatorów, które są dostępne na urządzeniu.</p>
            </div>
        </div>
    </div>
    <div id="contact-modal" class="modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="contact-modal-title" aria-describedby="contact-modal-description">
        <div class="modal-content contact-modal-content" role="document">
            <div class="contact-modal-header">
                <div>
                    <h3 id="contact-modal-title">Kontakt</h3>
                    <p id="contact-modal-description">Napisz do redakcji <?= sanitize(SITE_NAME) ?>.</p>
                </div>
                <button type="button" class="contact-modal-close" id="close-contact-modal" aria-label="Zamknij formularz kontaktowy">×</button>
            </div>
            <form id="contact-form" class="contact-form" action="<?= SITE_URL ?>/contact.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= sanitize($contactCsrf) ?>">
                <div class="contact-honeypot" aria-hidden="true">
                    <label for="contact-website">Strona internetowa</label>
                    <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>
                <div class="contact-form-group">
                    <label for="contact-name">Imię lub nazwa</label>
                    <input id="contact-name" name="name" type="text" maxlength="80" autocomplete="name" required>
                </div>
                <div class="contact-form-group">
                    <label for="contact-email">Adres e-mail</label>
                    <input id="contact-email" name="email" type="email" maxlength="254" autocomplete="email" required>
                </div>
                <div class="contact-form-group">
                    <label for="contact-subject">Temat</label>
                    <input id="contact-subject" name="subject" type="text" maxlength="140" required>
                </div>
                <div class="contact-form-group">
                    <label for="contact-message">Wiadomość</label>
                    <textarea id="contact-message" name="message" rows="6" maxlength="3000" required></textarea>
                </div>
                <p id="contact-form-status" class="contact-form-status" role="status" aria-live="polite" hidden></p>
                <button id="contact-submit" type="submit" class="contact-submit">Wyślij wiadomość</button>
            </form>
        </div>
    </div>
    <div id="share-feedback" class="share-feedback" role="status" aria-live="polite" hidden></div>
    <div id="pulse-feedback" class="pulse-feedback" role="status" aria-live="polite" hidden></div>
    <div id="pulse-modal" class="modal pulse-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pulse-modal-title" aria-describedby="pulse-modal-description" hidden>
        <div class="modal-content pulse-modal-content" role="document">
            <div class="contact-modal-header">
                <div>
                    <h3 id="pulse-modal-title">Dodaj komunikat</h3>
                    <p id="pulse-modal-description">Maksymalnie 160 znaków. Publikacja jest natychmiastowa, a wpis pozostaje widoczny przez 12 godzin.</p>
                </div>
                <button type="button" class="contact-modal-close" data-close-pulse aria-label="Zamknij formularz Pulsu miasta">×</button>
            </div>
            <form id="pulse-form" class="pulse-form" action="<?= SITE_URL ?>/pulse-submit.php" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= sanitize($pulseCsrfToken) ?>">
                <div class="contact-honeypot" aria-hidden="true"><label for="pulse-website">Strona internetowa</label><input id="pulse-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                <div class="contact-form-group"><label for="pulse-message">Treść komunikatu</label><textarea id="pulse-message" name="message" maxlength="160" rows="5" required></textarea><small class="pulse-form-counter" id="pulse-message-counter">0/160</small></div>
                <div class="pulse-form-grid"><div class="contact-form-group"><label for="pulse-signature">Imię lub podpis <span>(opcjonalnie)</span></label><input id="pulse-signature" name="signature" maxlength="80" autocomplete="name"></div><div class="contact-form-group"><label for="pulse-phone">Telefon <span>(opcjonalnie)</span></label><input id="pulse-phone" name="phone" maxlength="40" inputmode="tel" autocomplete="tel"></div></div>
                <p id="pulse-form-status" class="contact-form-status" role="status" aria-live="polite" hidden></p>
                <button id="pulse-submit" type="submit" class="contact-submit">Opublikuj komunikat</button>
            </form>
        </div>
    </div>
    <div id="submission-modal" class="modal submission-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="submission-modal-title" aria-describedby="submission-modal-description" hidden>
        <div class="modal-content submission-modal-content" role="document">
            <div class="contact-modal-header submission-modal-header">
                <div>
                    <h3 id="submission-modal-title">Dodaj zgłoszenie</h3>
                    <p id="submission-modal-description">Wypełnij formularz. Zgłoszenie zostanie sprawdzone przez administratora.</p>
                </div>
                <button type="button" class="contact-modal-close" data-close-submission aria-label="Zamknij formularz">×</button>
            </div>
            <div id="submission-modal-status" class="contact-form-status submission-modal-status" role="status" aria-live="polite" hidden></div>
            <div id="submission-modal-body" class="submission-modal-body"></div>
        </div>
    </div>
    <?php endif; ?>

    <script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= ASSET_VERSION ?>"></script>
    <?php if (!$isAdminPage): ?>
        <script src="<?= SITE_URL ?>/assets/js/share.js?v=<?= ASSET_VERSION ?>"></script>
        <script src="<?= SITE_URL ?>/assets/js/poll.js?v=<?= ASSET_VERSION ?>"></script>
        <script src="<?= SITE_URL ?>/assets/js/chronicle-interactions.js?v=<?= ASSET_VERSION ?>"></script>
        <script src="<?= SITE_URL ?>/assets/js/contact.js?v=<?= ASSET_VERSION ?>"></script>
        <script src="<?= SITE_URL ?>/assets/js/pulse.js?v=<?= ASSET_VERSION ?>"></script>
        <script src="<?= SITE_URL ?>/assets/js/submit-ad.js?v=<?= ASSET_VERSION ?>"></script>
        <script src="<?= SITE_URL ?>/assets/js/submission-modal.js?v=<?= ASSET_VERSION ?>"></script>
    <?php endif; ?>
</body>
</html>
