<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pulse.php';

pulseEnsureSchema();
$pageTitle = 'Puls miasta';
$pageDescription = 'Puls miasta to krótkie, lokalne komunikaty pilne bez reklam.';
$canonicalUrl = SITE_URL . '/pulse.php';
$notices = pulseActiveNotices();
require_once __DIR__ . '/includes/header.php';
?>
<section class="pulse-page pulse-page--list-only" aria-label="Puls miasta">
    <h1 class="sr-only">Puls miasta</h1>
    <div class="pulse-list" aria-label="Komunikaty Pulsu miasta">
        <?php if ($notices === []): ?>
            <article class="pulse-card pulse-card--permanent">
                <p class="pulse-card__message">Puls miasta : Pilne, bez reklam.</p>
            </article>
        <?php else: ?>
            <?php foreach ($notices as $notice): ?>
                <article class="pulse-card">
                    <p class="pulse-card__message<?= (($notice['author_source'] ?? 'community') === 'official') ? ' pulse-official-message' : '' ?>"><?= sanitize($notice['message']) ?></p>
                    <?php if (!empty($notice['signature']) || !empty($notice['phone']) || (($notice['author_source'] ?? 'community') === 'official')): ?>
                        <p class="pulse-card__contact<?= (($notice['author_source'] ?? 'community') === 'official') ? ' pulse-official-signature' : '' ?>">
                            <?php if (($notice['author_source'] ?? 'community') === 'official'): ?><strong>Oficjalnie</strong> · <?php endif; ?>
                            <?= !empty($notice['signature']) ? '— ' . sanitize($notice['signature']) : '' ?>
                            <?= !empty($notice['signature']) && !empty($notice['phone']) ? ' · ' : '' ?>
                            <?= !empty($notice['phone']) ? '<span class="pulse-card__phone">' . sanitize($notice['phone']) . '</span>' : '' ?>
                        </p>
                    <?php endif; ?>
                    <time class="pulse-card__time" datetime="<?= sanitize((string) $notice['published_at']) ?>"><?= sanitize(pulsePublishedTime($notice)) ?></time>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <details class="pulse-explainer">
        <summary>O co chodzi z Pulsem miasta?</summary>
        <p>Puls miasta służy do krótkiego przekazywania pilnych, lokalnych komunikatów, na przykład o zagubionym zwierzęciu, utrudnieniu albo potrzebie szybkiego kontaktu.</p>
        <p>Komunikat ma maksymalnie 160 znaków, jest publikowany od razu i jest widoczny przez 12 godzin. Podpis oraz telefon są opcjonalne i widoczne publicznie. To miejsce służy wyłącznie pilnym informacjom — bez reklam.</p>
    </details>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
