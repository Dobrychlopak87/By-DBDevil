<?php
declare(strict_types=1);

// Pierwsze uruchomienie: bez konfiguracji bazy od razu przekieruj do instalatora (ścieżka względna — działa w dowolnym podfolderze).
$pokerConfigFile = __DIR__ . '/config.php';
$pokerConfig = is_file($pokerConfigFile) ? require $pokerConfigFile : array();
if (!is_array($pokerConfig) || empty($pokerConfig['installed'])) {
    header('Location: install.php');
    exit;
}
$assetVersion = '8.1.0';
// Gdy gra działa w podkatalogu serwisu 66600.PL, używa jego czcionek i ikony (ścieżki względne, bez wpisanej domeny).
$siteRoot = dirname(__DIR__);
$siteFonts = is_file($siteRoot . '/assets/css/fonts.css');
$siteFavicon = is_file($siteRoot . '/assets/images/icons/favicon.svg');
?><!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="description" content="Poker Polski — Texas Hold'em online dla 2–4 osób. Gra na punkty wirtualne.">
  <title>Poker Polski — Texas Hold'em</title>
  <script src="assets/theme.js?v=<?= $assetVersion ?>"></script>
<?php if ($siteFavicon): ?>
  <link rel="icon" href="../assets/images/icons/favicon.svg" type="image/svg+xml">
<?php else: ?>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect x='2' y='2' width='60' height='60' rx='18' fill='%23334C60'/%3E%3Cpath d='M32 13c6 9 17 14 17 23a9 9 0 0 1-15 6l2 9h-8l2-9a9 9 0 0 1-15-6c0-9 11-14 17-23z' fill='%23FFDBBB'/%3E%3C/svg%3E">
<?php endif; ?>
<?php if ($siteFonts): ?>
  <link rel="stylesheet" href="../assets/css/fonts.css">
<?php endif; ?>
  <link rel="stylesheet" href="assets/style.css?v=<?= $assetVersion ?>">
</head>
<body>
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <symbol id="i-sound-on" viewBox="0 0 24 24"><path fill="currentColor" d="M3 9v6h4l5 4V5L7 9H3z"/><path d="M16 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-sound-off" viewBox="0 0 24 24"><path fill="currentColor" d="M3 9v6h4l5 4V5L7 9H3z"/><path d="M16 9l5 6M21 9l-5 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l-4-4 4-4M6 12h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-back" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-share" viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-history" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></symbol>
    <symbol id="i-refresh" viewBox="0 0 24 24"><path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v4h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
    <symbol id="i-lock" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
    <symbol id="i-moon" viewBox="0 0 24 24"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-chat" viewBox="0 0 24 24"><path d="M4 5h16v11H9l-5 4V5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 9.5h8M8 12.5h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M4 11l8-7 8 7v9h-5v-6H9v6H4v-9z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-exit" viewBox="0 0 24 24"><path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3M14 16l4-4-4-4M18 12H8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  </svg>

  <div id="toast-region" class="toast-region" aria-live="polite" aria-atomic="false"></div>

  <main id="app" class="app-shell">
    <!-- ŁADOWANIE -->
    <section id="loading-screen" class="screen loading-screen">
      <div class="brand-mark lg">♠</div>
            <div class="loading-dots" aria-label="Ładowanie"><span></span><span></span><span></span></div>
    </section>

    <!-- WEJŚCIE -->
    <section id="auth-screen" class="screen auth-screen hidden" aria-label="Wejście do gry">
      <div class="auth-intro">
        <div class="auth-top">
          <div class="brand"><span class="brand-mark">♠</span><span class="brand-name">Poker Polski</span></div>
          <div class="topbar-right">
            <a class="site-link hidden" data-site-home href="../" title="Wróć do serwisu"><svg><use href="#i-home"/></svg><span data-site-name>66600.PL</span></a>
            <button class="icon-btn theme-toggle" type="button" title="Zmień motyw" aria-label="Zmień motyw"><svg class="i-moon"><use href="#i-moon"/></svg><svg class="i-sun"><use href="#i-sun"/></svg></button>
          </div>
        </div>
        <h1>Texas Hold'em</h1>
      </div>

      <div class="auth-panel">
        <div id="site-login-card" class="site-login-card hidden">
          <a id="site-login-link" class="btn btn-green" href="../auth/login.php">Zaloguj się przez <span data-site-name>66600.PL</span></a>
        </div>
        <div id="site-login-divider" class="auth-divider hidden">lub</div>
        <div class="segmented" role="tablist" aria-label="Sposób wejścia">
          <button class="seg active" data-auth-tab="guest" role="tab" aria-selected="true" type="button">Gość</button>
          <button class="seg" data-auth-tab="reserved" role="tab" aria-selected="false" type="button">Nick z hasłem</button>
        </div>

        <form id="guest-form" class="form-stack" novalidate>
          <label class="field">
            <span>Nick</span>
            <input name="username" autocomplete="nickname" minlength="3" maxlength="24" required placeholder="" autofocus>
          </label>
          <button class="btn btn-gold btn-lg btn-block" type="submit">Graj</button>
        </form>

        <form id="reserved-login-form" class="form-stack hidden" novalidate>
          <label class="field"><span>Nick</span><input name="username" autocomplete="username" minlength="3" maxlength="24" required placeholder=""></label>
          <label class="field"><span>Hasło</span><input type="password" name="password" autocomplete="current-password" minlength="8" required placeholder="••••••••"></label>
          <button class="btn btn-gold btn-lg btn-block" type="submit">Graj</button>
        </form>
      </div>
    </section>

    <!-- LOBBY -->
    <section id="lobby-screen" class="screen lobby-screen hidden">
      <header class="topbar">
        <div class="topbar-left">
          <a class="site-link hidden" data-site-home href="../" title="Wróć do serwisu"><svg><use href="#i-home"/></svg><span data-site-name>66600.PL</span></a>
          <span class="brand-sep hidden" data-site-sep></span>
          <div class="brand"><span class="brand-mark">♠</span><span class="brand-name">Poker Polski</span></div>
        </div>
        <div class="topbar-right">
          <button class="icon-btn theme-toggle" type="button" title="Zmień motyw" aria-label="Zmień motyw"><svg class="i-moon"><use href="#i-moon"/></svg><svg class="i-sun"><use href="#i-sun"/></svg></button>
          <div class="user-chip">
            <span id="lobby-avatar" class="avatar sm">?</span>
            <div class="user-chip-text"><strong id="lobby-username">—</strong><span id="lobby-chips" class="chips-text">—</span></div>
          </div>
          <button id="logout-button" class="icon-btn" type="button" title="Wyloguj" aria-label="Wyloguj"><svg><use href="#i-logout"/></svg></button>
        </div>
      </header>

      <div class="lobby-content">
        <div class="lobby-head">
          <div>
            <h2>Stoły</h2>
          </div>
          <div class="lobby-head-actions">
            <button id="refresh-lobby-button" class="icon-btn" type="button" title="Odśwież listę" aria-label="Odśwież listę stołów"><svg><use href="#i-refresh"/></svg></button>
            <button id="create-table-button" class="btn btn-gold" type="button"><svg class="ico"><use href="#i-plus"/></svg>Nowy stół</button>
          </div>
        </div>

        <section id="szu-card" class="szu-card" aria-labelledby="szu-title">
          <div class="szu-portrait" aria-hidden="true">
            <svg viewBox="0 0 64 64"><path d="M32 7c-6 9.2-18 15.2-18 24.8 0 6 4.8 10 10 10 2.8 0 5.2-1 6.6-2.8-.6 4.4-2.4 8-5.6 11h14c-3.2-3-5-6.6-5.6-11 1.4 1.8 3.8 2.8 6.6 2.8 5.2 0 10-4 10-10C50 22.2 38 16.2 32 7z" fill="currentColor"/></svg>
          </div>
          <div class="szu-copy">
            <h3 id="szu-title">Wielki Szu</h3>
          </div>
          <div class="szu-actions">
            <div class="segmented" role="radiogroup" aria-label="Liczba przeciwników">
              <button type="button" role="radio" data-szu-opp="1" class="is-active" aria-checked="true">1</button>
              <button type="button" role="radio" data-szu-opp="2" aria-checked="false">2</button>
              <button type="button" role="radio" data-szu-opp="3" aria-checked="false">3</button>
            </div>
            <button id="play-szu-button" class="btn btn-green" type="button">Graj</button>
          </div>
        </section>

        <div id="my-table-banner" class="banner banner-live hidden">
          <span class="live-dot"></span>
          <div class="banner-text"><strong id="my-table-name">Twój stół</strong></div>
          <button id="return-table-button" class="btn btn-green" type="button">Wróć do stołu</button>
        </div>

        <div id="tables-list" class="table-grid" aria-live="polite"></div>

        <div class="lobby-aside">
          <div id="reserve-nick-banner" class="info-card hidden">
            <span class="info-icon"><svg><use href="#i-lock"/></svg></span>
            <div><strong>Zarezerwuj nick</strong></div>
            <button id="reserve-nick-button" class="btn btn-ghost" type="button">Ustaw hasło</button>
          </div>
          <span id="refill-copy" class="hidden"></span>
        </div>
      </div>
    </section>

    <!-- STÓŁ -->
    <section id="game-screen" class="screen game-screen hidden">
      <header class="game-topbar">
        <button id="back-lobby-button" class="back-btn" type="button"><svg><use href="#i-back"/></svg><span>Lobby</span></button>
        <div class="game-title">
          <strong id="table-name">Stół</strong>
          <span id="hand-label">Oczekiwanie</span>
        </div>
        <div class="game-tools">
          <div id="turn-timer" class="turn-timer hidden" aria-live="off" title="Czas na ruch">
            <svg viewBox="0 0 36 36" aria-hidden="true"><circle class="track" cx="18" cy="18" r="15.5"/><circle class="bar" cx="18" cy="18" r="15.5" pathLength="100"/></svg>
            <strong>30</strong>
          </div>
          <button id="chat-invite-top" class="tool-btn hidden" type="button" title="Zaproś kogoś z chatroom" aria-label="Zaproś kogoś z chatroom"><svg><use href="#i-chat"/></svg></button>
          <button id="share-current-button" class="icon-btn hide-sm" type="button" title="Skopiuj link do stołu" aria-label="Skopiuj link do stołu"><svg><use href="#i-share"/></svg></button>
          <button id="panel-toggle" class="icon-btn panel-toggle" type="button" title="Przebieg gry" aria-label="Przebieg gry" aria-expanded="false"><svg><use href="#i-history"/></svg></button>
          <button class="icon-btn theme-toggle hide-sm" type="button" title="Zmień motyw" aria-label="Zmień motyw"><svg class="i-moon"><use href="#i-moon"/></svg><svg class="i-sun"><use href="#i-sun"/></svg></button>
          <button id="sound-toggle" class="icon-btn" type="button" aria-pressed="true" title="Wyłącz dźwięki" aria-label="Dźwięki"><svg><use href="#i-sound-on"/></svg></button>
          <div class="chip-balance hide-xs"><span>Punkty</span><strong id="game-chips">—</strong></div>
        </div>
      </header>

      <div class="game-body">
        <div class="table-area">
          <div class="table-fit">
            <div id="poker-table" class="poker-table">
              <div class="rail"></div>
              <div class="felt">
                <div class="felt-logo">♠ POKER POLSKI ♠</div>
              </div>
              <div class="table-center">
                <div id="pot" class="pot"><span class="pot-chip"></span><span class="pot-label">Pula</span><strong id="pot-value">0</strong></div>
                <div id="board-cards" class="board-cards" aria-label="Karty wspólne"></div>
                <div id="table-message" class="table-message"></div>
                <button id="chat-invite-button" class="felt-invite hidden" type="button"><svg><use href="#i-chat"/></svg><span>Zaproś kogoś z chatroom</span></button>
              </div>
              <div id="bets" class="bets"></div>
              <div id="dealer-button" class="dealer-button hidden">D</div>
              <div id="seat-0" class="seat pos-0"></div>
              <div id="seat-1" class="seat pos-1"></div>
              <div id="seat-2" class="seat pos-2"></div>
              <div id="seat-3" class="seat pos-3"></div>
            </div>
          </div>
        </div>

        <aside id="side-panel" class="side-panel" aria-label="Informacje o stole">
          <div class="side-head">
            <strong class="side-title">Przebieg</strong>
            <button id="panel-close" class="icon-btn sm panel-close" type="button" aria-label="Zamknij"><svg><use href="#i-close"/></svg></button>
          </div>
          <ol id="message-list" class="message-list" data-side-pane="log"></ol>
        </aside>
        <div id="panel-backdrop" class="panel-backdrop hidden"></div>
      </div>

      <footer id="action-bar" class="action-bar">
        <div class="dock-info">
          <div id="my-hand-name" class="my-hand-name hidden"></div>
          <div id="turn-copy" class="turn-copy"></div>
        </div>
        <div id="game-controls" class="game-controls"></div>
      </footer>
    </section>
  </main>

  <!-- MODAL: rezerwacja nicku -->
  <div id="reserve-nick-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="reserve-nick-title">
    <div class="modal-card">
      <button class="icon-btn sm modal-close" data-close-modal type="button" aria-label="Zamknij"><svg><use href="#i-close"/></svg></button>
      <h2 id="reserve-nick-title">Zarezerwuj nick</h2>
      <form id="reserve-nick-form" class="form-stack">
        <label class="field"><span>Hasło (min. 8 znaków)</span><input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
        <label class="check"><input type="checkbox" name="acknowledge" required><span>Hasła nie da się odzyskać.</span></label>
        <button class="btn btn-gold btn-block" type="submit">Zapisz</button>
      </form>
    </div>
  </div>

  <!-- MODAL: nowy stół -->
  <div id="new-table-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="new-table-title">
    <div class="modal-card">
      <button class="icon-btn sm modal-close" data-close-modal type="button" aria-label="Zamknij"><svg><use href="#i-close"/></svg></button>
      <h2 id="new-table-title">Nowy stół</h2>
      <form id="new-table-form" class="form-stack">
        <label class="field"><span>Nazwa stołu</span><input name="name" maxlength="32" minlength="3" required></label>
        <button class="btn btn-gold btn-block" type="submit">Utwórz</button>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=<?= $assetVersion ?>" defer></script>
</body>
</html>
