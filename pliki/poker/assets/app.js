/* Poker Polski — klient v7. Czysty JavaScript, bez bibliotek i zewnętrznych zasobów. */
(() => {
  'use strict';

  // ---------------------------------------------------------------------------
  // Stan klienta
  // ---------------------------------------------------------------------------
  const readSoundPref = () => { try { return localStorage.getItem('poker-sound') !== 'off'; } catch (_) { return true; } };
  const state = {
    szuOpponents: 1,
    szuBusy: false,
    csrf: '',
    user: null,
    activeTableId: null,
    game: null,
    pollTimer: null,
    pollBusy: false,
    lobbyTimer: null,
    lobbyBusy: false,
    activeScreen: 'loading',
    turnClockTimer: null,
    receivedAt: 0,
    soundEnabled: readSoundPref(),
    audioContext: null,
    lastTickSecond: null,
    anim: { handNo: null, boardCount: 0, pot: 0, resultKey: null, bets: {}, message: '' },
    history: [],          // wiadomości z poprzednich rozdań (tylko w przeglądarce)
    historyHand: null,
    raiseValue: null,
    myTableId: null,
    inviteTableId: Number(new URLSearchParams(window.location.search).get('stol')) || null,
    inviteNotified: false,
    site: { integrated: false },
    chatCooldownUntil: 0,
    chatBusy: false,
    chatTimer: null,
  };

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
  const screens = { loading: $('#loading-screen'), auth: $('#auth-screen'), lobby: $('#lobby-screen'), game: $('#game-screen') };
  const rankLabels = { T: '10', J: 'W', Q: 'D', K: 'K', A: 'A' };
  const suitLabels = { s: '♠', h: '♥', d: '♦', c: '♣' };
  const phaseNames = { preflop: 'Przed flopem', flop: 'Flop', turn: 'Turn', river: 'River', result: 'Wynik', waiting: 'Oczekiwanie' };
  const TURN_SECONDS = 30;

  function el(tag, className = '', text = null) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== null && text !== undefined) node.textContent = String(text);
    return node;
  }
  const last = (list) => (list && list.length ? list[list.length - 1] : undefined);
  const fmt = (value) => new Intl.NumberFormat('pl-PL').format(Number(value || 0));
  const formatChips = (value) => `${fmt(value)} pkt`;

  function formatTimer(seconds) {
    const value = Math.max(0, Number(seconds || 0));
    const hours = Math.floor(value / 3600);
    const minutes = Math.floor((value % 3600) / 60);
    if (hours > 0) return `${hours} godz. ${minutes} min`;
    if (minutes > 0) return `${minutes} min`;
    return 'mniej niż minutę';
  }

  function avatarColor(name) {
    let hash = 0;
    for (const ch of String(name || '?')) hash = (hash * 31 + ch.codePointAt(0)) >>> 0;
    const hue = hash % 360;
    return `linear-gradient(135deg, hsl(${hue} 55% 48%), hsl(${(hue + 30) % 360} 60% 28%))`;
  }
  function makeAvatar(name, extra = '') {
    const node = el('span', `avatar ${extra}`.trim(), (String(name || '?').trim().slice(0, 1) || '?').toUpperCase());
    node.style.setProperty('--av', avatarColor(name));
    return node;
  }
  // Awatar komputerowego mistrza — monogram z pikiem (SVG, bez zewnętrznych plików).
  function makeSzuAvatar(extra = '') {
    const node = el('span', `avatar szu-avatar ${extra}`.trim());
    node.innerHTML = '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 4c-3 4.6-9 7.6-9 12.4 0 3 2.4 5 5 5 1.4 0 2.6-.5 3.3-1.4-.3 2.2-1.2 4-2.8 5.5h7c-1.6-1.5-2.5-3.3-2.8-5.5.7.9 1.9 1.4 3.3 1.4 2.6 0 5-2 5-5C25 11.6 19 8.6 16 4z" fill="currentColor"/></svg>';
    node.title = 'Wielki Szu — komputer';
    return node;
  }

  // ---------------------------------------------------------------------------
  // Dźwięki (syntezowane, bez plików)
  // ---------------------------------------------------------------------------
  function unlockAudio() {
    if (!state.soundEnabled || state.audioContext) return;
    const AudioCtor = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtor) return;
    try { state.audioContext = new AudioCtor(); if (state.audioContext.resume) state.audioContext.resume(); } catch (_) { /* brak audio */ }
  }
  function tone({ frequency, duration = 0.08, volume = 0.03, type = 'sine', delay = 0, endFrequency = null }) {
    if (!state.soundEnabled || !state.audioContext) return;
    const ctx = state.audioContext;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    const start = ctx.currentTime + delay;
    osc.type = type;
    osc.frequency.setValueAtTime(frequency, start);
    if (endFrequency) osc.frequency.exponentialRampToValueAtTime(endFrequency, start + duration);
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + 0.012);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
    osc.connect(gain); gain.connect(ctx.destination);
    osc.start(start); osc.stop(start + duration + 0.02);
  }
  function playSound(kind) {
    if (!state.soundEnabled) return;
    unlockAudio();
    if (kind === 'deal') tone({ frequency: 760, endFrequency: 350, duration: 0.055, volume: 0.018, type: 'triangle' });
    if (kind === 'chip') { tone({ frequency: 245, duration: 0.07, volume: 0.028, type: 'square' }); tone({ frequency: 370, duration: 0.05, volume: 0.017, delay: 0.035 }); }
    if (kind === 'fold') tone({ frequency: 260, endFrequency: 120, duration: 0.13, volume: 0.025, type: 'sawtooth' });
    if (kind === 'tick') tone({ frequency: 900, duration: 0.035, volume: 0.012 });
    if (kind === 'turn') { tone({ frequency: 660, duration: 0.09, volume: 0.03, type: 'triangle' }); tone({ frequency: 880, duration: 0.12, volume: 0.03, type: 'triangle', delay: 0.1 }); }
    if (kind === 'win') { tone({ frequency: 523, duration: 0.12, volume: 0.038, type: 'triangle' }); tone({ frequency: 659, duration: 0.14, volume: 0.035, type: 'triangle', delay: 0.12 }); tone({ frequency: 784, duration: 0.18, volume: 0.04, type: 'triangle', delay: 0.25 }); }
  }
  function updateSoundToggle() {
    const button = $('#sound-toggle');
    button.setAttribute('aria-pressed', String(state.soundEnabled));
    button.title = state.soundEnabled ? 'Wyłącz dźwięki' : 'Włącz dźwięki';
    $('use', button).setAttribute('href', state.soundEnabled ? '#i-sound-on' : '#i-sound-off');
  }
  function toggleSound() {
    state.soundEnabled = !state.soundEnabled;
    try { localStorage.setItem('poker-sound', state.soundEnabled ? 'on' : 'off'); } catch (_) { /* tryb prywatny */ }
    if (state.soundEnabled) { unlockAudio(); playSound('chip'); }
    updateSoundToggle();
  }

  // ---------------------------------------------------------------------------
  // Powiadomienia, ekrany, API
  // ---------------------------------------------------------------------------
  function toast(message, type = 'info') {
    if (!message) return;
    const region = $('#toast-region');
    const item = el('div', `toast ${type === 'error' ? 'error' : ''}`, message);
    region.appendChild(item);
    while (region.children.length > 3) region.firstElementChild.remove();
    window.setTimeout(() => { item.classList.add('out'); window.setTimeout(() => item.remove(), 300); }, 4200);
  }

  function showScreen(name) {
    Object.entries(screens).forEach(([key, node]) => node.classList.toggle('hidden', key !== name));
    state.activeScreen = name;
    document.body.dataset.screen = name;
  }

  async function api(action, payload = null, method = 'GET') {
    const options = { method, headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' };
    let url = `api.php?action=${encodeURIComponent(action)}`;
    if (method === 'GET' && payload) url += `&${new URLSearchParams(payload).toString()}`;
    if (method === 'POST') {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(Object.assign({}, payload || {}, { csrf: state.csrf }));
    }
    let response;
    try {
      response = await fetch(url, options);
    } catch (_) {
      throw new Error('Brak połączenia z serwerem. Sprawdź internet i spróbuj ponownie.');
    }
    const raw = await response.text();
    let body = null;
    try { body = JSON.parse(raw); } catch (_) {
      const plain = raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      throw new Error(plain && plain.length < 220 ? plain : 'Serwer zwrócił nieoczekiwaną odpowiedź.');
    }
    if (body.csrf) state.csrf = body.csrf;
    if (!response.ok || !body.ok) {
      const failure = new Error(body.message || 'Wystąpił błąd połączenia z serwerem.');
      failure.body = body;
      throw failure;
    }
    return body;
  }

  const isAuthError = (message) => /podaj nick|zaloguj/i.test(message || '');

  // ---------------------------------------------------------------------------
  // Konto i wejście
  // ---------------------------------------------------------------------------
  function setUser(user) {
    state.user = user;
    const chips = formatChips(user ? user.chips : 0);
    $('#lobby-chips').textContent = chips;
    $('#game-chips').textContent = chips;
    $('#lobby-username').textContent = user ? user.username : '—';
    const avatar = $('#lobby-avatar');
    avatar.textContent = user ? user.username.slice(0, 1).toUpperCase() : '?';
    avatar.style.setProperty('--av', avatarColor(user ? user.username : '?'));
    $('#refill-copy').textContent = `Pula odnowi się do 5 000 pkt za ${formatTimer(user ? user.seconds_until_refill : 0)}.`;
    $('#reserve-nick-banner').classList.toggle('hidden', !user || user.auth_type !== 'guest');
    // Konto serwisu: wylogowanie odbywa się na stronie, więc przycisk wylogowania w grze jest ukryty.
    const siteAccount = !!user && user.auth_type === 'site';
    $('#logout-button').classList.toggle('hidden', siteAccount);
    const chip = $('.user-chip');
    if (chip) chip.title = siteAccount ? `Zalogowano kontem ${state.site.name || 'serwisu'}` : '';
    let badge = $('#lobby-site-badge');
    if (false && siteAccount && !badge) {
      badge = el('span', 'site-badge', 'KONTO');
      badge.id = 'lobby-site-badge';
      $('#lobby-username').after(badge);
    }
    if (badge) badge.classList.add('hidden');
  }

  // Integracja z serwisem nadrzędnym (wspólne logowanie, link do strony głównej, Chatroom).
  function applySite(site) {
    state.site = site && typeof site === 'object' ? site : { integrated: false };
    const on = !!state.site.integrated;
    $$('[data-site-home]').forEach((a) => { a.classList.toggle('hidden', !on); if (on && state.site.home_url) a.setAttribute('href', state.site.home_url); });
    $$('[data-site-sep]').forEach((n) => n.classList.toggle('hidden', !on));
    $$('[data-site-name]').forEach((n) => { n.textContent = state.site.name || '66600.PL'; });
    $('#site-login-card').classList.toggle('hidden', !on);
    $('#site-login-divider').classList.toggle('hidden', !on);
    if (on && state.site.login_url) $('#site-login-link').setAttribute('href', state.site.login_url);
  }

  async function bootstrap() {
    try {
      const response = await api('session');
      state.csrf = response.csrf;
      applySite(response.site);
      if (!response.authenticated) { showAuth(); return; }
      setUser(response.user);
      await showLobby();
      // Po odświeżeniu strony wróć od razu do trwającej gry (bez linku zaproszenia).
      if (state.myTableId && !state.inviteTableId) enterTable(state.myTableId);
    } catch (error) {
      showAuth();
      toast(error.message, 'error');
    }
  }

  function showAuth() {
    stopLobbyPolling(); clearPolling(); stopTurnClock();
    showScreen('auth');
    window.setTimeout(() => { const input = $('#guest-form input[name="username"]'); if (input && window.innerWidth > 760) input.focus(); }, 50);
  }

  function switchAuthTab(tab) {
    $$('[data-auth-tab]').forEach((button) => {
      const active = button.dataset.authTab === tab;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', String(active));
    });
    $('#guest-form').classList.toggle('hidden', tab !== 'guest');
    $('#reserved-login-form').classList.toggle('hidden', tab !== 'reserved');
    const first = $(tab === 'guest' ? '#guest-form input' : '#reserved-login-form input');
    if (first) first.focus();
  }

  async function submitAuth(event, action) {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);
    const username = String(data.get('username') || '').trim();
    if (username.length < 3) { toast('Nick musi mieć co najmniej 3 znaki.', 'error'); return; }
    if (action === 'reserved_login' && String(data.get('password') || '').length < 8) { toast('Hasło ma co najmniej 8 znaków.', 'error'); return; }
    const button = $('button[type="submit"]', form);
    const label = button.textContent;
    button.disabled = true; button.textContent = '…';
    try {
      const response = await api(action, { username, password: data.get('password') }, 'POST');
      const session = await api('session');
      state.csrf = session.csrf;
      applySite(session.site);
      setUser(session.user);
      form.reset();
      await showLobby();
      if (state.myTableId && !state.inviteTableId) enterTable(state.myTableId);
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      button.disabled = false; button.textContent = label;
    }
  }

  async function logout() {
    try {
      await api('logout', {}, 'POST');
      state.user = null; state.activeTableId = null; state.game = null; state.myTableId = null;
      const session = await api('session').catch(() => null);
      if (session && session.csrf) state.csrf = session.csrf;
      if (session) applySite(session.site);
      switchAuthTab('guest');
      showAuth();
    } catch (error) {
      toast(error.message, 'error');
    }
  }

  // ---------------------------------------------------------------------------
  // Linki do stołów
  // ---------------------------------------------------------------------------
  function tableShareUrl(tableId) {
    const url = new URL(window.location.href);
    url.hash = '';
    url.search = '';
    url.searchParams.set('stol', String(tableId));
    return url.toString();
  }
  async function copyText(text) {
    try {
      if (navigator.clipboard && window.isSecureContext) { await navigator.clipboard.writeText(text); return true; }
    } catch (_) { /* tryb zgodności poniżej */ }
    const helper = el('textarea');
    helper.value = text;
    helper.setAttribute('readonly', '');
    helper.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none;';
    document.body.appendChild(helper);
    helper.select();
    let copied = false;
    try { copied = document.execCommand('copy'); } catch (_) { copied = false; }
    helper.remove();
    return copied;
  }
  async function shareTable(table) {
    const url = tableShareUrl(Number(table.id));
    const coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
    try {
      if (navigator.share && coarse) {
        await navigator.share({ title: `Poker Polski — ${table.name}`, text: `Dołącz do stołu „${table.name}” w Poker Polski.`, url });
        return;
      }
      const copied = await copyText(url);
      if (copied) toast('Skopiowano link.');
      else window.prompt('Skopiuj link do stołu:', url);
    } catch (error) {
      if (!error || error.name !== 'AbortError') toast('Nie udało się udostępnić linku.', 'error');
    }
  }

  // ---------------------------------------------------------------------------
  // Lobby
  // ---------------------------------------------------------------------------
  function makeTableCard(table) {
    const id = Number(table.id);
    const count = Number(table.player_count);
    const invited = Number(state.inviteTableId) === id;
    const mine = Number(state.myTableId) === id;
    const full = count >= 4;
    const card = el('article', `table-card${invited ? ' is-invited' : ''}${mine ? ' is-mine' : ''}`);
    card.dataset.tableId = String(id);
    if (invited) card.id = 'zaproszony-stol';

    const top = el('div', 'table-card-top');
    const titleWrap = el('div');
    const bots = Number(table.bot_count || 0);
    titleWrap.append(el('h3', '', table.name), el('div', 'sub', `Ciemne ${fmt(table.small_blind || 10)} / ${fmt(table.big_blind || 20)}${bots ? ` · Wielki Szu${bots > 1 ? ` ×${bots}` : ''}` : ''}`));
    const statusText = { waiting: 'Czeka', playing: 'W grze', showdown: 'Wynik', finished: 'Koniec' }[table.status] || 'Czeka';
    const tags = el('div');
    tags.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;gap:6px';
    tags.appendChild(el('span', `tag ${table.status}`, statusText));
    if (mine) tags.appendChild(el('span', 'tag mine', 'Twój stół'));
    else if (invited) tags.appendChild(el('span', 'tag invite', 'Zaproszenie'));
    top.append(titleWrap, tags);

    const mini = el('div', 'mini-table');
    mini.setAttribute('aria-hidden', 'true');
    mini.appendChild(el('div', 'mini-felt'));
    for (let i = 0; i < 4; i += 1) mini.appendChild(el('span', `mini-seat${i < count ? ' taken' : ''}`));

    const meta = el('div', 'table-card-meta');
    const seats = el('span');
    const strong = el('strong', '', `${count} / 4`);
    seats.append(strong);
    const hint = el('span', '', '');
    meta.append(seats, hint);

    const actions = el('div', 'table-card-actions');
    const join = el('button', `btn ${mine ? 'btn-green' : invited ? 'btn-gold' : 'btn-ghost'}`);
    join.type = 'button';
    if (mine) {
      join.textContent = 'Wróć do stołu';
      join.addEventListener('click', () => enterTable(id));
    } else {
      join.textContent = table.status === 'playing' ? 'W grze' : full ? 'Pełny' : 'Usiądź';
      join.disabled = table.status === 'playing' || full || (state.myTableId && !mine);
      if (state.myTableId && !mine && !full && table.status !== 'playing') join.title = 'Najpierw opuść swój obecny stół.';
      join.addEventListener('click', () => joinTable(id));
    }
    const share = el('button', 'icon-btn');
    share.type = 'button';
    share.title = 'Skopiuj link zaproszenia';
    share.setAttribute('aria-label', `Link do stołu ${table.name}`);
    share.innerHTML = '<svg><use href="#i-share"/></svg>';
    share.addEventListener('click', () => shareTable(table));
    actions.append(join, share);

    card.append(top, mini, meta, actions);
    return card;
  }

  async function showLobby() {
    clearPolling();
    stopTurnClock();
    closeSidePanel();
    state.activeTableId = null;
    state.game = null;
    showScreen('lobby');
    await refreshLobby();
    startLobbyPolling();
  }

  async function refreshLobby(manual = false) {
    if (state.lobbyBusy) return;
    state.lobbyBusy = true;
    const refreshBtn = $('#refresh-lobby-button');
    if (manual) { refreshBtn.classList.remove('spin'); void refreshBtn.offsetWidth; refreshBtn.classList.add('spin'); }
    try {
      const response = await api('tables');
      setUser(response.user);
      state.myTableId = response.my_table_id ? Number(response.my_table_id) : null;
      const list = $('#tables-list');
      list.replaceChildren();
      if (!response.tables.length) {
        const empty = el('div', 'empty-state');
        empty.append(el('strong', '', 'Brak stołów'));
        const btn = el('button', 'btn btn-gold', 'Utwórz stół');
        btn.type = 'button';
        btn.addEventListener('click', openNewTableModal);
        empty.appendChild(btn);
        list.appendChild(empty);
      } else {
        response.tables.forEach((table) => list.appendChild(makeTableCard(table)));
      }
      updateSzuCard();
      const mineTable = response.tables.find((t) => Number(t.id) === state.myTableId);
      $('#my-table-banner').classList.toggle('hidden', !state.myTableId);
      $('#my-table-name').textContent = mineTable ? mineTable.name : 'Twój stół';
      $('#return-table-button').dataset.tableId = state.myTableId || '';
      if (state.inviteTableId && !state.inviteNotified) {
        state.inviteNotified = true;
        const linked = response.tables.find((t) => Number(t.id) === Number(state.inviteTableId));
        if (linked) {
          window.setTimeout(() => { const n = $('#zaproszony-stol'); if (n) n.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 80);
          toast(`Zaproszenie: ${linked.name}`);
        } else {
          toast('Stół już nie istnieje.', 'error');
        }
      }
    } catch (error) {
      if (isAuthError(error.message)) { showAuth(); return; }
      if (manual || state.activeScreen === 'lobby') toast(error.message, 'error');
    } finally {
      state.lobbyBusy = false;
    }
  }

  function startLobbyPolling() {
    stopLobbyPolling();
    state.lobbyTimer = window.setInterval(() => {
      if (state.activeScreen === 'lobby' && !document.hidden) refreshLobby();
    }, 6000);
  }
  function stopLobbyPolling() {
    if (state.lobbyTimer !== null) window.clearInterval(state.lobbyTimer);
    state.lobbyTimer = null;
  }

  function resetTableView() {
    state.anim = { handNo: null, boardCount: 0, pot: 0, resultKey: null, bets: {}, message: '' };
    state.history = []; state.historyHand = null; state.raiseValue = null;
    $('#chat-invite-button').classList.add('hidden');
    $('#chat-invite-top').classList.add('hidden');
  }

  function enterTable(tableId) {
    stopLobbyPolling();
    resetTableView();
    state.activeTableId = Number(tableId);
    showScreen('game');
    renderSkeleton();
    refreshGame();
    startPolling();
  }

  async function joinTable(tableId) {
    try {
      const response = await api('join_table', { table_id: tableId }, 'POST');
      if (state.inviteTableId) {
        state.inviteTableId = null;
        try { window.history.replaceState(null, '', window.location.pathname); } catch (_) { /* ignoruj */ }
      }
      enterTable(Number(response.table_id));
    } catch (error) {
      toast(error.message, 'error');
      await refreshLobby();
    }
  }

  // ---------------------------------------------------------------------------
  // Wielki Szu — gra z komputerem
  // ---------------------------------------------------------------------------
  function updateSzuCard() {
    const btn = $('#play-szu-button');
    if (!btn) return;
    const seated = !!state.myTableId;
    btn.disabled = seated || state.szuBusy;
  }
  function setSzuOpponents(value) {
    state.szuOpponents = Math.max(1, Math.min(3, Number(value) || 1));
    $$('[data-szu-opp]').forEach((b) => {
      const active = Number(b.dataset.szuOpp) === state.szuOpponents;
      b.classList.toggle('is-active', active);
      b.setAttribute('aria-checked', active ? 'true' : 'false');
    });
  }
  async function playWithSzu() {
    if (state.szuBusy) return;
    state.szuBusy = true;
    updateSzuCard();
    try {
      const response = await api('play_szu', { opponents: state.szuOpponents || 1 }, 'POST');
      enterTable(Number(response.table_id));
    } catch (error) {
      toast(error.message, 'error');
      await refreshLobby();
    } finally {
      state.szuBusy = false;
      updateSzuCard();
    }
  }
  async function addSzu() {
    if (!state.activeTableId || state.szuBusy) return;
    state.szuBusy = true;
    try {
      const response = await api('add_bot', { table_id: state.activeTableId }, 'POST');
      if (response.game) renderGame(response.game);
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      state.szuBusy = false;
    }
  }
  async function removeSzu(botId) {
    if (!state.activeTableId || state.szuBusy) return;
    state.szuBusy = true;
    try {
      const response = await api('remove_bot', { table_id: state.activeTableId, bot_id: botId }, 'POST');
      if (response.game) renderGame(response.game);
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      state.szuBusy = false;
    }
  }
  async function resumeGame() {
    if (!state.activeTableId) return;
    try {
      const response = await api('resume', { table_id: state.activeTableId }, 'POST');
      if (response.game) renderGame(response.game);
    } catch (error) {
      toast(error.message, 'error');
    }
  }

  async function createTable(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const name = String(new FormData(form).get('name') || '').trim();
    if (name.length < 3) { toast('Nazwa stołu musi mieć co najmniej 3 znaki.', 'error'); return; }
    const button = $('button[type="submit"]', form);
    button.disabled = true;
    try {
      const response = await api('create_table', { name }, 'POST');
      closeModals();
      enterTable(Number(response.table_id));
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      button.disabled = false;
    }
  }

  // ---------------------------------------------------------------------------
  // Karty i ocena układu (wyłącznie podpowiedź dla gracza — rozstrzyga serwer)
  // ---------------------------------------------------------------------------
  function renderCard(card, extraClass = '') {
    if (!card) return el('div', `card slot ${extraClass}`.trim());
    const rank = card.slice(0, -1);
    const suit = card.slice(-1);
    const node = el('div', `card ${suit === 'h' || suit === 'd' ? 'red' : 'black'} ${extraClass}`.trim());
    node.setAttribute('aria-label', `${rankLabels[rank] || rank}${suitLabels[suit] || ''}`);
    node.append(el('span', 'rank', rankLabels[rank] || rank), el('span', 'suit-sm', suitLabels[suit] || ''), el('span', 'suit', suitLabels[suit] || ''));
    return node;
  }
  const renderBack = (extraClass = '') => el('div', `card back ${extraClass}`.trim());

  const RANK_VALUE = { 2: 2, 3: 3, 4: 4, 5: 5, 6: 6, 7: 7, 8: 8, 9: 9, T: 10, J: 11, Q: 12, K: 13, A: 14 };
  function handName(cards) {
    if (!cards || cards.length < 2) return '';
    const ranks = cards.map((c) => RANK_VALUE[c.slice(0, -1)]).filter(Boolean);
    const suits = cards.map((c) => c.slice(-1));
    const counts = {};
    ranks.forEach((r) => { counts[r] = (counts[r] || 0) + 1; });
    const groups = Object.values(counts).sort((a, b) => b - a);
    const suitCount = {};
    suits.forEach((s) => { suitCount[s] = (suitCount[s] || 0) + 1; });
    const flushSuit = Object.keys(suitCount).find((s) => suitCount[s] >= 5);
    const straightHigh = (values) => {
      const set = new Set(values);
      if (set.has(14)) set.add(1);
      for (let high = 14; high >= 5; high -= 1) {
        let ok = true;
        for (let k = 0; k < 5; k += 1) if (!set.has(high - k)) { ok = false; break; }
        if (ok) return high;
      }
      return 0;
    };
    if (flushSuit) {
      const sf = straightHigh(cards.filter((c) => c.slice(-1) === flushSuit).map((c) => RANK_VALUE[c.slice(0, -1)]));
      if (sf === 14) return 'Poker królewski';
      if (sf) return 'Poker';
    }
    if (groups[0] >= 4) return 'Kareta';
    if (groups[0] >= 3 && groups[1] >= 2) return 'Full';
    if (flushSuit) return 'Kolor';
    if (straightHigh(ranks)) return 'Strit';
    if (groups[0] === 3) return 'Trójka';
    if (groups[0] === 2 && groups[1] === 2) return 'Dwie pary';
    if (groups[0] === 2) return 'Para';
    return 'Wysoka karta';
  }

  // ---------------------------------------------------------------------------
  // Ostatnie akcje graczy (z dziennika serwera)
  // ---------------------------------------------------------------------------
  function lastActions(game) {
    const result = {};
    const messages = game.state.messages || [];
    const names = game.players.map((p) => p.username).sort((a, b) => b.length - a.length);
    for (let i = messages.length - 1; i >= 0; i -= 1) {
      const msg = messages[i];
      if (/^(Flop|Turn|River)\.$/.test(msg) || /^Rozdanie #/.test(msg)) break;
      let text = msg;
      const timeout = /^Czas minął — /.test(text);
      if (timeout) text = text.replace(/^Czas minął — /, '');
      const name = names.find((n) => text.indexOf(`${n} `) === 0);
      if (!name || result[name]) continue;
      const rest = text.slice(name.length + 1);
      let tag = null;
      if (/pasuje/.test(rest)) tag = { cls: 'fold', text: timeout ? 'Pas (czas)' : 'Pas' };
      else if (/czeka/.test(rest)) tag = { cls: 'check', text: timeout ? 'Czeka (czas)' : 'Czeka' };
      else if (/wchodzi za wszystko/.test(rest)) tag = { cls: 'allin', text: 'All-in' };
      else if (/sprawdza/.test(rest)) tag = { cls: 'call', text: 'Sprawdza' };
      else if (/podbija/.test(rest)) tag = { cls: 'raise', text: 'Podbija' };
      else if (/małą ciemną/.test(rest)) tag = { cls: 'check', text: 'Mała ciemna' };
      else if (/dużą ciemną/.test(rest)) tag = { cls: 'check', text: 'Duża ciemna' };
      if (tag) result[name] = tag;
    }
    return result;
  }

  // ---------------------------------------------------------------------------
  // Zegar tury
  // ---------------------------------------------------------------------------
  function secondsLeft(game = state.game) {
    const deadline = Number((game && game.state && game.state.turn_deadline_at) || 0);
    if (!deadline) return 0;
    const elapsed = (Date.now() - state.receivedAt) / 1000;
    return Math.max(0, deadline - (Number(game.server_time || 0) + elapsed));
  }

  function updateTurnClock() {
    const game = state.game;
    const activeId = Number((game && game.state && game.state.turn_user_id) || 0);
    const visible = !!game && game.table.status === 'playing' && activeId > 0;
    const exact = visible ? secondsLeft(game) : 0;
    const seconds = Math.ceil(exact);
    const urgent = visible && seconds <= 10;
    const ratio = Math.max(0, Math.min(1, exact / TURN_SECONDS));
    const timer = $('#turn-timer');
    timer.classList.toggle('hidden', !visible);
    timer.classList.toggle('critical', urgent);
    timer.classList.toggle('mine', !!(game && game.my_turn));
    if (visible) {
      $('strong', timer).textContent = String(seconds);
      timer.style.setProperty('--off', String(100 - ratio * 100));
      timer.title = `Czas na ruch: ${seconds} s`;
    }
    $$('.seat.is-turn').forEach((seat) => {
      seat.classList.toggle('critical', urgent);
      const ring = $('.avatar-ring', seat);
      if (ring) ring.style.setProperty('--p', String(ratio));
    });
    if (game && game.my_turn && urgent && seconds !== state.lastTickSecond && seconds > 0) playSound('tick');
    state.lastTickSecond = seconds;
    if (visible && seconds === 0 && game.my_turn) $$('#game-controls button').forEach((b) => { b.disabled = true; });
  }
  function startTurnClock() {
    if (state.turnClockTimer !== null) return;
    state.turnClockTimer = window.setInterval(updateTurnClock, 250);
  }
  function stopTurnClock() {
    if (state.turnClockTimer !== null) window.clearInterval(state.turnClockTimer);
    state.turnClockTimer = null;
    state.lastTickSecond = null;
    $('#turn-timer').classList.add('hidden');
  }

  // ---------------------------------------------------------------------------
  // Odświeżanie stołu
  // ---------------------------------------------------------------------------
  function clearPolling() {
    if (state.pollTimer !== null) window.clearInterval(state.pollTimer);
    state.pollTimer = null;
  }
  function startPolling() {
    clearPolling();
    state.pollTimer = window.setInterval(() => {
      if (state.activeScreen !== 'game' || !state.activeTableId || state.pollBusy) return;
      if (document.hidden && Math.random() < 0.6) return; // rzadziej w tle
      refreshGame(true);
    }, 1500);
  }

  async function refreshGame(silent = false) {
    if (!state.activeTableId || state.pollBusy) return;
    state.pollBusy = true;
    const tableId = state.activeTableId;
    try {
      const response = await api('table_state', { table_id: tableId });
      if (state.activeTableId === tableId && state.activeScreen === 'game') renderGame(response.game);
    } catch (error) {
      if (isAuthError(error.message)) { showAuth(); return; }
      if (!silent) toast(error.message, 'error');
      if (/nie istnieje/i.test(error.message)) await showLobby();
    } finally {
      state.pollBusy = false;
    }
  }

  // ---------------------------------------------------------------------------
  // Renderowanie stołu
  // ---------------------------------------------------------------------------
  function renderSkeleton() {
    $('#table-name').textContent = '';
    $('#hand-label').textContent = '';
    $('#board-cards').replaceChildren(...Array.from({ length: 5 }, () => renderCard(null)));
    $('#bets').replaceChildren();
    $('#pot').classList.add('empty');
    $('#table-message').textContent = '';
    $('#dealer-button').classList.add('hidden');
    for (let i = 0; i < 4; i += 1) $(`#seat-${i}`).replaceChildren();
    $('#game-controls').replaceChildren();
    $('#turn-copy').textContent = '';
    $('#my-hand-name').classList.add('hidden');
    $('#message-list').replaceChildren();
  }

  const playerName = (game, id) => {
    const p = game.players.find((player) => Number(player.user_id) === Number(id));
    return p ? p.username : 'Gracz';
  };
  function seatPosition(game, seat) {
    const me = game.players.find((p) => p.is_self);
    const base = me ? Number(me.seat) : 1;
    return (((Number(seat) - base) % 4) + 4) % 4;
  }

  function renderSeat(container, player, game, ctx) {
    container.className = `seat pos-${container.dataset.pos}`;
    container.replaceChildren();
    if (!player) {
      const canInvite = ctx.meSeated && game.table.status !== 'playing';
      const empty = el(canInvite ? 'button' : 'div', `seat-empty${canInvite ? ' seat-add-szu' : ''}`);
      const av = el('span', 'avatar', '+');
      if (canInvite) {
        empty.type = 'button';
        empty.title = 'Dosadź komputerowego przeciwnika';
        const txt = el('span', 'seat-add-text');
        txt.append(el('strong', '', 'Wielki Szu'));
        empty.append(av, txt);
        empty.addEventListener('click', addSzu);
      } else {
        empty.append(av, el('span', '', 'Wolne'));
      }
      container.appendChild(empty);
      return;
    }
    const isTurn = game.table.status === 'playing' && Number(game.state.turn_user_id) === Number(player.user_id);
    const winner = ctx.winners[Number(player.user_id)];
    container.classList.toggle('is-self', !!player.is_self);
    container.classList.toggle('is-szu', !!player.is_bot);
    container.classList.toggle('is-turn', isTurn);
    container.classList.toggle('is-folded', !!player.folded && game.table.status !== 'waiting');
    container.classList.toggle('is-winner', !!winner);

    // Karty
    const cards = el('div', 'seat-cards');
    const showFaces = Array.isArray(player.cards) && player.cards.length > 0;
    if (showFaces && !player.is_self) cards.classList.add('revealed');
    if (showFaces) {
      player.cards.forEach((card, index) => {
        const node = renderCard(card, ctx.isNewHand ? 'deal-in' : (!player.is_self && ctx.newResult ? 'flip-in' : ''));
        node.style.animationDelay = `${index * 90 + Number(container.dataset.pos) * 60}ms`;
        cards.appendChild(node);
      });
    } else if (Number(player.card_count) > 0 && !(player.folded && game.table.status !== 'playing')) {
      for (let i = 0; i < Number(player.card_count); i += 1) {
        const node = renderBack(ctx.isNewHand ? 'deal-in' : '');
        node.style.animationDelay = `${i * 90 + Number(container.dataset.pos) * 60}ms`;
        cards.appendChild(node);
      }
    }

    // Tabliczka
    const plate = el('div', 'plate');
    const wrap = el('div', 'avatar-wrap');
    const ring = el('span', 'avatar-ring');
    if (isTurn) ring.style.setProperty('--p', String(Math.max(0, Math.min(1, secondsLeft(game) / TURN_SECONDS))));
    wrap.append(ring, player.is_bot ? makeSzuAvatar() : makeAvatar(player.username));
    const text = el('div', 'plate-text');
    const nameNode = el('span', 'plate-name', player.is_self ? `${player.username} (Ty)` : player.username);
    text.append(nameNode);
    if (player.is_bot) {
      if (ctx.meSeated && game.table.status !== 'playing') {
        const kick = el('button', 'szu-remove');
        kick.type = 'button';
        kick.title = 'Odpraw Wielkiego Szu';
        kick.setAttribute('aria-label', `Odpraw: ${player.username}`);
        kick.innerHTML = '<svg><use href="#i-close"/></svg>';
        kick.addEventListener('click', (e) => { e.stopPropagation(); removeSzu(Number(player.user_id)); });
        container.appendChild(kick);
      }
    }
    text.append(el('span', 'plate-chips', player.all_in && game.table.status === 'playing' ? 'ALL-IN' : formatChips(player.chips)));
    plate.append(wrap, text);
    plate.title = `${player.username}${player.is_bot ? ' (komputer)' : ''} — ${formatChips(player.chips)}`;

    // Znacznik akcji / wyniku
    let tag = null;
    if (winner) tag = { cls: 'win', text: `+${fmt(winner.amount)}${winner.hand && ctx.resultType === 'showdown' ? ` · ${winner.hand}` : ''}` };
    else if (game.table.status === 'playing' || game.table.status === 'showdown') {
      if (player.folded) tag = { cls: 'fold', text: 'Pas' };
      else if (player.all_in) tag = { cls: 'allin', text: 'All-in' };
      else if (game.table.status === 'playing' && ctx.actions[player.username] && !isTurn) tag = ctx.actions[player.username];
    }
    if (tag) plate.appendChild(el('span', `seat-tag ${tag.cls}`, tag.text));

    container.append(cards, plate);
  }

  function renderBets(game, positions) {
    const bets = $('#bets');
    const prev = state.anim.bets || {};
    const next = {};
    bets.replaceChildren();
    game.players.forEach((player) => {
      const amount = Number(player.current_bet);
      if (amount <= 0 || game.table.status !== 'playing') return;
      const pos = positions[player.user_id];
      const node = el('div', `bet pos-${pos}`);
      const stack = el('span', 'stack');
      const layers = amount >= 500 ? 3 : amount >= 50 ? 2 : 1;
      for (let i = 0; i < layers; i += 1) stack.appendChild(el('i'));
      node.append(stack, el('span', '', fmt(amount)));
      if (Number(prev[player.user_id] || 0) !== amount) node.classList.add('pop');
      bets.appendChild(node);
      next[player.user_id] = amount;
    });
    return next;
  }

  function flyChips(fromRects, toEl, count = 3) {
    if (!toEl || !fromRects.length) return;
    const target = toEl.getBoundingClientRect();
    const tx = target.left + target.width / 2 - 9;
    const ty = target.top + target.height / 2 - 9;
    fromRects.forEach((rect, r) => {
      for (let i = 0; i < count; i += 1) {
        const chip = el('span', 'chip-fly');
        const sx = rect.left + rect.width / 2 - 9 + (i - 1) * 6;
        const sy = rect.top + rect.height / 2 - 9;
        chip.style.left = `${sx}px`;
        chip.style.top = `${sy}px`;
        document.body.appendChild(chip);
        window.setTimeout(() => {
          chip.style.transform = `translate(${tx - sx}px, ${ty - sy}px) scale(.8)`;
          chip.style.opacity = '0.2';
        }, 30 + i * 60 + r * 40);
        window.setTimeout(() => chip.remove(), 800 + i * 60 + r * 40);
      }
    });
  }

  function renderLog(game, isNewHand) {
    const messages = (game.state.messages || []).slice();
    const handNo = Number(game.state.hand_no || 0);
    if (isNewHand && state.historyHand !== null && state.lastMessages) {
      state.history = state.lastMessages.concat(state.history).slice(0, 60);
    }
    state.historyHand = handNo;
    state.lastMessages = messages.slice().reverse();
    const list = $('#message-list');
    list.replaceChildren();
    state.lastMessages.concat(state.history).forEach((msg) => {
      const li = el('li', /^Rozdanie #|^(Flop|Turn|River)\.$/.test(msg) ? 'sep' : '', msg);
      list.appendChild(li);
    });
  }

  function renderGame(game) {
    const prev = state.anim;
    const handNo = Number(game.state.hand_no || 0);
    const boardCards = game.state.board || [];
    const boardCount = boardCards.length;
    const pot = Number(game.state.pot || 0);
    const status = game.table.status;
    const result = status === 'showdown' ? game.state.last_result : null;
    const resultKey = result ? `${handNo}-${JSON.stringify(result.winners || [])}` : null;
    const isNewHand = prev.handNo !== null && prev.handNo !== handNo;
    const firstRender = prev.handNo === null;
    const hasNewBoard = !isNewHand && boardCount > prev.boardCount;
    const newResult = !!resultKey && resultKey !== prev.resultKey;
    const wasMyTurn = !!(state.game && state.game.my_turn);

    // Zapamiętaj pozycje zakładów przed zebraniem ich do puli.
    const collectRects = (hasNewBoard || newResult) && !firstRender ? $$('#bets .bet').map((b) => b.getBoundingClientRect()) : [];

    state.game = game;
    state.receivedAt = Date.now();
    const me = game.players.find((p) => p.is_self);
    if (me && state.user) setUser(Object.assign({}, state.user, { chips: Number(me.chips) }));

    // Nagłówek
    $('#table-name').textContent = game.table.name;
    const phase = phaseNames[game.state.phase] || 'Gra';
    $('#hand-label').textContent = handNo > 0 && status !== 'waiting' ? `Rozdanie #${handNo} · ${phase}` : `${game.players.length} / 4 graczy · ciemne ${fmt(game.table.small_blind)}/${fmt(game.table.big_blind)}`;

    // Pozycje miejsc (Ty zawsze na dole)
    const positions = {};
    game.players.forEach((p) => { positions[p.user_id] = seatPosition(game, p.seat); });
    const winners = {};
    if (result) (result.winners || []).forEach((w) => {
      const id = Number(w.user_id);
      if (winners[id]) { winners[id].amount += Number(w.amount); if (w.hand && winners[id].hand.indexOf(w.hand) === -1) winners[id].hand += `, ${w.hand}`; }
      else winners[id] = { amount: Number(w.amount), hand: w.hand || '' };
    });
    const ctx = { isNewHand: isNewHand || (firstRender && false), newResult, winners, actions: lastActions(game), resultType: result ? result.type : null, meSeated: game.players.some((p) => p.is_self) };
    for (let seatNo = 1; seatNo <= 4; seatNo += 1) {
      const pos = seatPosition(game, seatNo);
      const container = $(`#seat-${pos}`);
      container.dataset.pos = String(pos);
      renderSeat(container, game.players.find((p) => Number(p.seat) === seatNo), game, ctx);
    }

    // Rozdający
    const dealerBtn = $('#dealer-button');
    const dealer = game.players.find((p) => Number(p.seat) === Number(game.state.dealer_seat));
    if (dealer && status !== 'waiting') {
      dealerBtn.className = `dealer-button pos-${positions[dealer.user_id]}`;
    } else dealerBtn.className = 'dealer-button hidden';

    // Karty wspólne
    const board = $('#board-cards');
    board.replaceChildren();
    for (let i = 0; i < 5; i += 1) {
      const animate = !firstRender && i >= (isNewHand ? 0 : prev.boardCount) && i < boardCount;
      const node = renderCard(boardCards[i] || null, animate ? 'flip-in' : '');
      if (animate) node.style.animationDelay = `${(i - (isNewHand ? 0 : prev.boardCount)) * 120}ms`;
      board.appendChild(node);
    }

    // Pula i komunikat na stole
    const potEl = $('#pot');
    $('#pot-value').textContent = formatChips(pot);
    potEl.classList.toggle('empty', pot <= 0);
    const tableMsg = $('#table-message');
    tableMsg.className = 'table-message';
    if (result) {
      const names = Object.keys(winners).map((id) => playerName(game, id));
      const total = Object.values(winners).reduce((sum, w) => sum + w.amount, 0);
      tableMsg.classList.add('result');
      tableMsg.textContent = result.type === 'fold'
        ? `${names.join(', ')} wygrywa ${formatChips(total)}`
        : `${names.join(' i ')} ${names.length > 1 ? 'dzielą' : 'wygrywa'} ${formatChips(total)}`;
    } else if (status === 'waiting') {
      tableMsg.textContent = '';
    } else {
      tableMsg.textContent = '';
    }

    const newBets = renderBets(game, positions);
    renderLog(game, isNewHand);
    renderMyHand(game, me);
    renderControls(game, me);

    // Efekty
    if (collectRects.length) window.requestAnimationFrame(() => flyChips(collectRects, potEl, 2));
    if (isNewHand || hasNewBoard) playSound('deal');
    const betsChanged = Object.keys(newBets).some((id) => Number(newBets[id]) !== Number((prev.bets || {})[id] || 0));
    if (!firstRender && betsChanged && !isNewHand) {
      potEl.classList.remove('pulse'); void potEl.offsetWidth; potEl.classList.add('pulse');
      playSound('chip');
    }
    const newest = last(game.state.messages || []) || '';
    if (!firstRender && prev.message !== newest && /pasuje/.test(newest)) playSound('fold');
    if (newResult && !firstRender) {
      const table = $('#poker-table');
      table.classList.remove('celebrate'); void table.offsetWidth; table.classList.add('celebrate');
      window.setTimeout(() => {
        const potRect = potEl.getBoundingClientRect();
        Object.keys(winners).forEach((id) => {
          const plate = $(`#seat-${positions[id]} .plate`);
          if (plate) flyChips([potRect], plate, 5);
        });
      }, 350);
      playSound('win');
    }
    if (game.my_turn && !wasMyTurn && !firstRender) playSound('turn');

    state.anim = { handNo, boardCount, pot, resultKey, bets: newBets, message: newest };
    updateChatInvite(game);
    startTurnClock();
    updateTurnClock();
  }

  function renderMyHand(game, me) {
    const box = $('#my-hand-name');
    if (!me || !Array.isArray(me.cards) || me.cards.length < 2 || game.table.status === 'waiting' || me.folded) {
      box.classList.add('hidden');
      return;
    }
    box.classList.add('hidden');
  }

  // ---------------------------------------------------------------------------
  // Panel akcji
  // ---------------------------------------------------------------------------
  function actionButton(label, className, callback, key = '') {
    const button = el('button', `btn ${className}`);
    button.type = 'button';
    button.appendChild(document.createTextNode(label));
    if (key) { button.appendChild(el('kbd', '', key)); button.dataset.key = key.toLowerCase(); }
    button.addEventListener('click', callback);
    return button;
  }

  function setCopy(main, dim = '') {
    const copy = $('#turn-copy');
    copy.replaceChildren(document.createTextNode(main));
    if (dim) { copy.appendChild(document.createTextNode(' ')); copy.appendChild(el('span', 'dim', dim)); }
  }

  function renderControls(game, me) {
    const controls = $('#game-controls');
    const bar = $('#action-bar');
    bar.classList.toggle('my-turn', !!game.my_turn);
    const status = game.table.status;
    const activeRaise = document.activeElement && document.activeElement.closest && document.activeElement.closest('.raise-box');

    if (!me) {
      controls.replaceChildren();
      setCopy('');
      return;
    }
    const freeSeats = game.players.length < 4;
    const hasSzu = game.players.some((p) => p.is_bot);
    if (status === 'waiting') {
      if (game.state.paused) {
        const buttons = [];
        if (Number(me.chips) > 0) buttons.push(actionButton('Gram dalej', 'btn-green', resumeGame));
        buttons.push(actionButton('Opuść stół', 'btn-danger', leaveCurrentTable));
        controls.replaceChildren(...buttons);
        setCopy(Number(me.chips) > 0 ? 'Pauza' : 'Brak punktów');
        return;
      }
      const buttons = [];
      if (freeSeats) buttons.push(actionButton('+ Wielki Szu', 'btn-green', addSzu));
      buttons.push(actionButton('Link', 'btn-ghost', () => shareTable(game.table)));
      buttons.push(actionButton('Opuść stół', 'btn-danger', leaveCurrentTable));
      controls.replaceChildren(...buttons);
      setCopy('');
      return;
    }
    if (status === 'showdown') {
      const next = Number(game.state.next_hand_at || 0);
      const wait = next ? Math.max(0, Math.round(next - Number(game.server_time || 0))) : 0;
      const buttons = [];
      if (freeSeats) buttons.push(actionButton('+ Wielki Szu', 'btn-ghost', addSzu));
      buttons.push(actionButton('Opuść stół', 'btn-danger', leaveCurrentTable));
      controls.replaceChildren(...buttons);
      setCopy(wait ? `${wait} s` : '');
      return;
    }
    if (!game.my_turn) {
      state.raiseValue = null;
      controls.replaceChildren();
      setCopy('');
      return;
    }
    // Nie przebudowuj panelu, gdy gracz właśnie ustawia kwotę podbicia.
    if (activeRaise && controls.dataset.turnKey === `${game.state.hand_no}-${game.state.phase}-${game.state.current_bet}`) return;

    const currentBet = Number(game.state.current_bet);
    const toCall = Math.max(0, currentBet - Number(me.current_bet));
    const maximum = Number(me.current_bet) + Number(me.chips);
    const minRaise = Math.min(maximum, currentBet + Number(game.state.min_raise));
    const pot = Number(game.state.pot || 0);
    setCopy('');

    const main = el('div', 'act-main');
    main.appendChild(actionButton('Pas', 'btn-danger', () => sendGameAction('fold'), 'F'));
    const callLabel = toCall > 0 ? (toCall >= Number(me.chips) ? `All-in ${fmt(me.chips)}` : `Sprawdź ${fmt(toCall)}`) : 'Czekaj';
    main.appendChild(actionButton(callLabel, 'btn-ghost', () => sendGameAction(toCall > 0 ? 'call' : 'check'), 'C'));

    const nodes = [];
    if (maximum > currentBet && Number(me.chips) > toCall) {
      if (state.raiseValue === null || state.raiseValue < minRaise || state.raiseValue > maximum) state.raiseValue = minRaise;
      const box = el('div', 'raise-box');
      const presets = el('div', 'raise-presets');
      const range = el('input', 'raise-range');
      range.type = 'range'; range.min = String(minRaise); range.max = String(maximum); range.step = '1';
      range.setAttribute('aria-label', 'Wysokość podbicia');
      const input = el('input', 'raise-input');
      input.type = 'number'; input.inputMode = 'numeric'; input.min = String(minRaise); input.max = String(maximum); input.step = '1';
      input.setAttribute('aria-label', 'Łączna wysokość zakładu');
      const raiseBtn = actionButton('', 'btn-gold', () => {
        const value = Math.round(Number(input.value));
        if (!Number.isFinite(value) || value < minRaise || value > maximum) { toast(`Podaj kwotę od ${fmt(minRaise)} do ${fmt(maximum)}.`, 'error'); return; }
        sendGameAction('raise', value);
      }, 'R');
      const labelNode = raiseBtn.firstChild;
      const setValue = (value, from) => {
        const v = Math.max(minRaise, Math.min(maximum, Math.round(Number(value) || minRaise)));
        state.raiseValue = v;
        if (from !== 'range') range.value = String(v);
        if (from !== 'input') input.value = String(v);
        const fill = maximum > minRaise ? ((v - minRaise) / (maximum - minRaise)) * 100 : 100;
        range.style.setProperty('--fill', `${fill}%`);
        labelNode.textContent = v >= maximum ? `All-in ${fmt(v)}` : `${currentBet > 0 ? 'Podbij do' : 'Postaw'} ${fmt(v)}`;
      };
      const preset = (label, value) => {
        const b = el('button', '', label);
        b.type = 'button';
        b.addEventListener('click', () => setValue(value));
        presets.appendChild(b);
      };
      preset('Min', minRaise);
      if (pot > 0) {
        preset('½ puli', currentBet + toCall + Math.round((pot + toCall) / 2));
        preset('Pula', currentBet + toCall + pot + toCall);
      }
      preset('Max', maximum);
      range.addEventListener('input', () => setValue(range.value, 'range'));
      input.addEventListener('input', () => { if (input.value !== '') setValue(input.value, 'input'); });
      input.addEventListener('blur', () => setValue(input.value));
      input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); raiseBtn.click(); } });
      setValue(state.raiseValue);
      const row = el('div', 'raise-row');
      row.append(range, input);
      box.append(presets, row);
      nodes.push(box);
      main.appendChild(raiseBtn);
    } else {
      main.classList.add('two');
    }
    controls.replaceChildren(...nodes, main);
    controls.dataset.turnKey = `${game.state.hand_no}-${game.state.phase}-${game.state.current_bet}`;
  }

  async function sendGameAction(gameAction, raiseTo = 0) {
    $$('#game-controls button').forEach((b) => { b.disabled = true; });
    unlockAudio();
    try {
      const response = await api('game_action', { table_id: state.activeTableId, game_action: gameAction, raise_to: raiseTo }, 'POST');
      state.raiseValue = null;
      renderGame(response.game);
    } catch (error) {
      toast(error.message, 'error');
      await refreshGame(true);
    }
  }

  async function leaveCurrentTable() {
    if (!state.activeTableId) return;
    try {
      await api('leave_table', { table_id: state.activeTableId }, 'POST');
      state.myTableId = null;
      await showLobby();
    } catch (error) {
      toast(error.message, 'error');
    }
  }

  // ---------------------------------------------------------------------------
  // Panel boczny, modale, skróty
  // ---------------------------------------------------------------------------
  function openSidePanel() {
    $('#side-panel').classList.add('open');
    $('#panel-backdrop').classList.remove('hidden');
    $('#panel-toggle').setAttribute('aria-expanded', 'true');
  }
  function closeSidePanel() {
    $('#side-panel').classList.remove('open');
    $('#panel-backdrop').classList.add('hidden');
    $('#panel-toggle').setAttribute('aria-expanded', 'false');
  }
  // ---------------------------------------------------------------------------
  // Zaproszenie na Chatroom serwisu
  // ---------------------------------------------------------------------------
  function chatCooldownLeft() {
    return Math.max(0, Math.ceil((state.chatCooldownUntil - Date.now()) / 1000));
  }

  function updateChatInvite(game = state.game) {
    const felt = $('#chat-invite-button');
    const top = $('#chat-invite-top');
    const me = game ? game.players.find((p) => p.is_self) : null;
    const available = !!(state.site && state.site.chat_invite && game && me && game.players.length < 4 && state.activeScreen === 'game');
    const waiting = available && game.table.status === 'waiting';
    top.classList.toggle('hidden', !available);
    felt.classList.toggle('hidden', !waiting);
    const left = chatCooldownLeft();
    const disabled = state.chatBusy || left > 0;
    [felt, top].forEach((b) => { b.disabled = disabled; });
    const feltLabel = $('span', felt);
    if (state.chatBusy) feltLabel.textContent = 'Wysyłanie…';
    else if (left > 0) feltLabel.textContent = `Wysłano · ${left} s`;
    else feltLabel.textContent = 'Zaproś kogoś z chatroom';
    top.title = left > 0 ? `Zaproszenie wysłane — kolejne za ${left} s` : 'Zaproś kogoś z chatroom';
    if (left <= 0 && state.chatTimer !== null) { window.clearInterval(state.chatTimer); state.chatTimer = null; }
  }

  function startChatCooldown(seconds) {
    const value = Math.max(0, Number(seconds || 0));
    if (!value) return;
    state.chatCooldownUntil = Date.now() + value * 1000;
    if (state.chatTimer !== null) window.clearInterval(state.chatTimer);
    state.chatTimer = window.setInterval(() => updateChatInvite(), 1000);
    updateChatInvite();
  }

  async function sendChatInvite() {
    if (!state.game || state.chatBusy || chatCooldownLeft() > 0) return;
    state.chatBusy = true;
    updateChatInvite();
    try {
      const response = await api('chat_invite', { table_id: state.game.table.id }, 'POST');

      state.chatBusy = false;
      startChatCooldown(response.retry_after || 120);
    } catch (error) {
      state.chatBusy = false;
      const retry = error.body && Number(error.body.retry_after);
      if (retry > 0) startChatCooldown(retry);
      toast(error.message, 'error');
    } finally {
      state.chatBusy = false;
      updateChatInvite();
    }
  }

  function switchSideTab(tab) {
    $$('[data-side-tab]').forEach((b) => b.classList.toggle('active', b.dataset.sideTab === tab));
    $$('[data-side-pane]').forEach((p) => p.classList.toggle('hidden', p.dataset.sidePane !== tab));
  }

  function openModal(id) {
    const modal = $(id);
    modal.classList.remove('hidden');
    const input = $('input', modal);
    if (input) window.setTimeout(() => input.focus(), 30);
  }
  function closeModals() {
    $$('.modal').forEach((m) => { m.classList.add('hidden'); const f = $('form', m); if (f) f.reset(); });
  }
  const openNewTableModal = () => openModal('#new-table-modal');

  async function reserveNick(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);
    if (String(data.get('password') || '').length < 8) { toast('Hasło musi mieć co najmniej 8 znaków.', 'error'); return; }
    if (!data.get('acknowledge')) { toast('Potwierdź, że rozumiesz brak możliwości odzyskania hasła.', 'error'); return; }
    const button = $('button[type="submit"]', form);
    button.disabled = true;
    try {
      const response = await api('reserve_nick', { password: data.get('password') }, 'POST');
      setUser(Object.assign({}, state.user, { auth_type: 'reserved' }));
      closeModals();
      toast('Zapisano.');
    } catch (error) {
      toast(error.message, 'error');
    } finally {
      button.disabled = false;
    }
  }

  function onKeydown(event) {
    if (event.key === 'Escape') { closeModals(); closeSidePanel(); return; }
    if (state.activeScreen !== 'game' || !state.game || !state.game.my_turn) return;
    const target = event.target;
    if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') && target.type !== 'range') return;
    if (event.ctrlKey || event.metaKey || event.altKey) return;
    const key = event.key.toLowerCase();
    const button = $(`#game-controls button[data-key="${key}"]:not(:disabled)`);
    if (button) { event.preventDefault(); button.click(); }
  }

  // ---------------------------------------------------------------------------
  // Zdarzenia
  // ---------------------------------------------------------------------------
  $$('[data-auth-tab]').forEach((b) => b.addEventListener('click', () => switchAuthTab(b.dataset.authTab)));
  $('#guest-form').addEventListener('submit', (e) => submitAuth(e, 'guest_enter'));
  $('#reserved-login-form').addEventListener('submit', (e) => submitAuth(e, 'reserved_login'));
  $('#logout-button').addEventListener('click', logout);
  $('#sound-toggle').addEventListener('click', toggleSound);
  document.addEventListener('pointerdown', unlockAudio, { once: true });
  $('#create-table-button').addEventListener('click', openNewTableModal);
  $('#play-szu-button').addEventListener('click', playWithSzu);
  $$('[data-szu-opp]').forEach((b) => b.addEventListener('click', () => setSzuOpponents(b.dataset.szuOpp)));
  setSzuOpponents(1);
  $('#refresh-lobby-button').addEventListener('click', () => refreshLobby(true));
  $('#new-table-form').addEventListener('submit', createTable);
  $('#reserve-nick-button').addEventListener('click', () => openModal('#reserve-nick-modal'));
  $('#reserve-nick-form').addEventListener('submit', reserveNick);
  $$('[data-close-modal]').forEach((b) => b.addEventListener('click', closeModals));
  $$('.modal').forEach((m) => m.addEventListener('click', (e) => { if (e.target === m) closeModals(); }));
  $('#return-table-button').addEventListener('click', () => { const id = Number($('#return-table-button').dataset.tableId); if (id) enterTable(id); });
  $('#back-lobby-button').addEventListener('click', showLobby);
  $('#share-current-button').addEventListener('click', () => { if (state.game) shareTable(state.game.table); });
  $('#chat-invite-button').addEventListener('click', sendChatInvite);
  $('#chat-invite-top').addEventListener('click', sendChatInvite);
  $('#panel-toggle').addEventListener('click', () => ($('#side-panel').classList.contains('open') ? closeSidePanel() : openSidePanel()));
  $('#panel-close').addEventListener('click', closeSidePanel);
  $('#panel-backdrop').addEventListener('click', closeSidePanel);
  $$('[data-side-tab]').forEach((b) => b.addEventListener('click', () => switchSideTab(b.dataset.sideTab)));
  document.addEventListener('keydown', onKeydown);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && state.activeScreen === 'game') refreshGame(true);
    if (!document.hidden && state.activeScreen === 'lobby') refreshLobby();
  });
  window.addEventListener('beforeunload', () => { clearPolling(); stopTurnClock(); stopLobbyPolling(); });
  updateSoundToggle();

  bootstrap();
})();
