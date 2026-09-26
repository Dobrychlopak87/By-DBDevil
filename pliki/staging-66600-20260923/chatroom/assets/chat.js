const $ = (selector, scope = document) => scope.querySelector(selector);

const elements = {
    shell: $('.chat-shell'),
    status: $('#connection-status'),
    alert: $('#chat-alert'),
    messages: $('#chat-messages'),
    users: $('#users-list'),
    blocks: $('#blocked-nicknames'),
    blocksList: $('#blocked-nicknames-list'),
    composerDock: $('#composer-dock'),
    messageForm: $('#message-form'),
    messageInput: $('#message-input'),
    messageLength: $('#message-length'),
    sendButton: $('#send-message'),
    selectImage: $('#select-image'),
    imageInput: $('#image-input'),
    imagePreview: $('#image-preview'),
    imagePreviewImage: $('#image-preview-image'),
    removeImage: $('#remove-image'),
    nicknameModal: $('#nickname-modal'),
    nicknameForm: $('#nickname-form'),
    nicknameInput: $('#nickname-input'),
    nicknameError: $('#nickname-error'),
    entryChoice: $('#entry-choice'),
    showLogin: $('#show-login'),
    showRegister: $('#show-register'),
    accountLoginForm: $('#account-login-form'),
    accountLoginNickname: $('#account-login-nickname'),
    accountLoginPassword: $('#account-login-password'),
    accountLoginError: $('#account-login-error'),
    accountRegisterForm: $('#account-register-form'),
    accountRegisterNickname: $('#account-register-nickname'),
    accountRegisterPassword: $('#account-register-password'),
    accountRegisterPasswordConfirm: $('#account-register-password-confirm'),
    accountRegisterTerms: $('#account-register-terms'),
    accountRegisterError: $('#account-register-error'),
    termsModal: $('#terms-modal'),
    termsConsent: $('#terms-consent'),
    termsError: $('#terms-error'),
    acceptTerms: $('#accept-terms'),
    privateModal: $('#private-modal'),
    openPrivate: $('#open-private'),
    closePrivate: $('#close-private'),
    privateForm: $('#private-form'),
    privateInput: $('#private-input'),
    privateMessages: $('#private-messages'),
    privateError: $('#private-error'),
    privateTargetNote: $('#private-target-note'),
    usersToggle: $('#users-toggle'),
    peoplePanel: $('.people-panel'),
    themeToggle: $('#theme-toggle'),
    soundToggle: $('#sound-toggle'),
    newMessagesNotice: $('#new-messages-notice')
};

const state = {
    csrfToken: elements.shell.dataset.csrfToken ?? '',
    session: null,
    users: [],
    blocks: [],
    lastMessageId: 0,
    changedSince: '',
    pollingTimer: null,
    isPolling: false,
    selectedPrivateTarget: null,
    hasLoadedMessages: false,
    selectedImage: null,
    serverTime: '',
    unreadCount: 0,
    soundEnabled: true,
    audioContext: null,
    lastPrivateFocus: null
};

const ROLE_LABELS = { admin: 'Administrator', moderator: 'Moderator' };
const MAX_IMAGE_BYTES = 8 * 1024 * 1024;
const ALLOWED_IMAGE_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);
const THEME_STORAGE_KEY = '66600-theme';
const SOUND_STORAGE_KEY = '66600-chat-sound';

function getFocusableElements(container) {
    return Array.from(container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter((element) => element.getClientRects().length > 0 && !element.closest('[hidden], [aria-hidden="true"]'));
}

function trapFocus(event, container) {
    if (event.key !== 'Tab') return;
    const focusableElements = getFocusableElements(container);
    if (focusableElements.length === 0) return;
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
    } else if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
    }
}

function syncVisibleViewport() {
    const viewport = window.visualViewport;
    const visibleHeight = Math.round(viewport?.height ?? window.innerHeight);
    document.documentElement.style.setProperty('--chat-visible-height', `${visibleHeight}px`);
    // Fixed composer musi być przyklejony do dołu visual viewportu; Safari nie może
    // wypychać go różnicą pomiędzy layout viewportem i paskiem przeglądarki.
    document.documentElement.style.setProperty('--chat-viewport-bottom', '0px');
}

syncVisibleViewport();
window.addEventListener('resize', syncVisibleViewport, { passive: true });
window.visualViewport?.addEventListener('resize', syncVisibleViewport, { passive: true });
window.visualViewport?.addEventListener('scroll', syncVisibleViewport, { passive: true });

function currentTheme() {
    return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
}

function applyTheme(theme, persist = false) {
    const normalized = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = normalized;
    document.body.classList.toggle('dark-mode', normalized === 'dark');
    if (elements.themeToggle) {
        elements.themeToggle.textContent = normalized === 'dark' ? 'Tryb jasny' : 'Tryb ciemny';
        elements.themeToggle.title = normalized === 'dark' ? 'Włącz tryb jasny' : 'Włącz tryb ciemny';
        elements.themeToggle.setAttribute('aria-pressed', String(normalized === 'dark'));
    }
    if (!persist) return;
    try {
        localStorage.setItem(THEME_STORAGE_KEY, normalized);
    } catch {
        // Motyw działa w bieżącej karcie także wtedy, gdy przeglądarka blokuje pamięć lokalną.
    }
}

function initializeTheme() {
    applyTheme(currentTheme());
    elements.themeToggle?.addEventListener('click', () => {
        applyTheme(currentTheme() === 'dark' ? 'light' : 'dark', true);
    });
    window.addEventListener('storage', (event) => {
        if (event.key === THEME_STORAGE_KEY && (event.newValue === 'light' || event.newValue === 'dark')) {
            applyTheme(event.newValue);
        }
    });
}

function updateSoundToggle() {
    if (!elements.soundToggle) return;
    elements.soundToggle.textContent = state.soundEnabled ? 'Dźwięk wł.' : 'Dźwięk wył.';
    elements.soundToggle.title = state.soundEnabled ? 'Wyłącz dźwięk nowych wiadomości' : 'Włącz dźwięk nowych wiadomości';
    elements.soundToggle.setAttribute('aria-pressed', String(state.soundEnabled));
}

function initializeNotifications() {
    try {
        state.soundEnabled = localStorage.getItem(SOUND_STORAGE_KEY) !== 'off';
    } catch {
        state.soundEnabled = true;
    }
    updateSoundToggle();
    elements.soundToggle?.addEventListener('click', () => {
        state.soundEnabled = !state.soundEnabled;
        try { localStorage.setItem(SOUND_STORAGE_KEY, state.soundEnabled ? 'on' : 'off'); } catch { /* Ustawienie działa w bieżącej karcie. */ }
        updateSoundToggle();
        if (state.soundEnabled) unlockAudio();
    });
}

function unlockAudio() {
    if (!state.soundEnabled || !('AudioContext' in window)) return;
    try {
        state.audioContext ??= new AudioContext();
        if (state.audioContext.state === 'suspended') state.audioContext.resume().catch(() => {});
    } catch {
        state.audioContext = null;
    }
}

function playNotificationSound() {
    if (!state.soundEnabled || !state.audioContext || state.audioContext.state !== 'running') return;
    try {
        const now = state.audioContext.currentTime;
        const oscillator = state.audioContext.createOscillator();
        const gain = state.audioContext.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(660, now);
        oscillator.frequency.setValueAtTime(880, now + 0.08);
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.07, now + 0.015);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.18);
        oscillator.connect(gain).connect(state.audioContext.destination);
        oscillator.start(now);
        oscillator.stop(now + 0.2);
    } catch {
        // Brak dźwięku nie może zakłócić działania pokoju.
    }
}

function isNearBottom() {
    const distance = elements.messages.scrollHeight - elements.messages.scrollTop - elements.messages.clientHeight;
    return distance < 72;
}

function updateUnreadNotice() {
    if (elements.newMessagesNotice) {
        elements.newMessagesNotice.hidden = state.unreadCount === 0;
        elements.newMessagesNotice.textContent = state.unreadCount > 1
            ? `Nowe wiadomości (${state.unreadCount})`
            : 'Nowa wiadomość';
    }
    if (window.parent !== window) {
        window.parent.postMessage({ type: 'CHAT_UNREAD', count: state.unreadCount }, window.location.origin);
    }
}

function clearUnreadMessages() {
    state.unreadCount = 0;
    updateUnreadNotice();
}

function requestChatroomClose() {
    if (window.parent !== window) {
        window.parent.postMessage({ type: 'CHATROOM_CLOSE_REQUEST' }, window.location.origin);
    }
}

async function markPublicMessagesRead() {
    if (!state.session || state.session.needsTerms || state.lastMessageId <= 0) {
        return;
    }
    try {
        const payload = await api('mark-read.php', { method: 'POST', data: { lastMessageId: state.lastMessageId } });
        state.unreadCount = Math.max(0, Number(payload.unreadCount) || 0);
        updateUnreadNotice();
    } catch {
    }
}

function setConnectionStatus(text, online = false) {
    elements.status.textContent = text;
    elements.status.classList.toggle('is-online', online);
}

function showAlert(message, isError = false) {
    elements.alert.textContent = message;
    elements.alert.hidden = false;
    elements.alert.classList.toggle('is-error', isError);
    window.clearTimeout(showAlert.timeoutId);
    showAlert.timeoutId = window.setTimeout(() => { elements.alert.hidden = true; }, 5000);
}

/** Wysyła żądanie JSON albo wieloczęściowy formularz bez ręcznego ustawiania nagłówka Content-Type. */
async function api(path, { method = 'GET', data = null } = {}) {
    const headers = { Accept: 'application/json' };
    const options = { method, headers, credentials: 'same-origin' };

    if (data !== null) {
        headers['X-CSRF-Token'] = state.csrfToken;
        if (data instanceof FormData) {
            options.body = data;
        } else {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }

    const response = await fetch(`api/${path}`, options);
    let payload;
    try {
        payload = await response.json();
    } catch {
        throw new Error('Serwer zwrócił nieprawidłową odpowiedź.');
    }
    if (!response.ok || payload.ok === false) {
        const error = new Error(payload.error ?? 'Wystąpił nieznany błąd.');
        error.status = response.status;
        throw error;
    }
    return payload;
}

function formatTime(value) {
    const match = /^(?:\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})/.exec(String(value ?? ''));
    return match ? `${match[1]}:${match[2]}` : '';
}

/**
 * Ustawia najnowszy wpis bezpośrednio nad nieruchomym kompozytorem.
 * Kolejne klatki są celowe: obrazy i układ mobilny mogą zwiększyć wysokość
 * rozmowy dopiero po pierwszym przeliczeniu DOM przez przeglądarkę.
 */
function scrollToBottom() {
    const setBottom = () => { elements.messages.scrollTop = elements.messages.scrollHeight; };
    setBottom();
    window.requestAnimationFrame(() => {
        setBottom();
        window.requestAnimationFrame(setBottom);
    });
}

function syncComposerSpace() {
    const dockHeight = elements.composerDock?.offsetHeight ?? 0;
    document.documentElement.style.setProperty('--composer-space', `${dockHeight}px`);
}

function observeComposerSpace() {
    syncComposerSpace();
    if (!('ResizeObserver' in window) || !elements.composerDock) return;
    const observer = new ResizeObserver(() => {
        syncComposerSpace();
        scrollToBottom();
    });
    observer.observe(elements.composerDock);
}

function permanentBadge() {
    const badge = document.createElement('img');
    badge.className = 'permanent-badge';
    badge.src = 'assets/permanent-badge.png';
    badge.alt = 'Stały, zarejestrowany nick';
    badge.title = 'Stały, zarejestrowany nick';
    return badge;
}

function roleBadge(role) {
    if (!ROLE_LABELS[role]) return null;
    const badge = document.createElement('span');
    badge.className = `role-badge ${role}`;
    badge.textContent = ROLE_LABELS[role];
    return badge;
}

function createActionButton(label, action, dataset = {}, className = '') {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `mini-button ${className}`.trim();
    button.dataset.action = action;
    Object.entries(dataset).forEach(([key, value]) => { button.dataset[key] = String(value); });
    button.textContent = label;
    return button;
}

function createImageContent(message) {
    if (message.imageExpired) {
        const expired = document.createElement('p');
        expired.className = 'image-expired';
        expired.textContent = 'Zdjęcie wygasło (regulamin chatroomu).';
        return expired;
    }
    if (!message.imageUrl) return null;

    const link = document.createElement('a');
    link.href = message.imageUrl;
    link.target = '_blank';
    link.rel = 'noopener';
    link.setAttribute('aria-label', 'Otwórz zdjęcie w pełnym rozmiarze');
    const image = document.createElement('img');
    image.className = 'message-image';
    image.src = message.imageUrl;
    image.alt = 'Zdjęcie dodane do rozmowy';
    image.loading = 'eager';
    image.addEventListener('load', scrollToBottom, { once: true });
    image.addEventListener('error', scrollToBottom, { once: true });
    link.append(image);
    return link;
}

function createMessageElement(message) {
    const ownMessage = message.authorId === state.session?.id;
    const item = document.createElement('article');
    item.className = `message ${ownMessage ? 'is-own' : 'is-other'}${message.deleted ? ' is-deleted' : ''}`;
    item.dataset.messageId = String(message.id);
    item.dataset.authorId = String(message.authorId);

    const stack = document.createElement('div');
    stack.className = 'message-stack';
    const meta = document.createElement('div');
    meta.className = 'message-meta';
    const name = document.createElement('span');
    name.className = 'message-name';
    name.textContent = ownMessage ? 'Ty' : message.nickname;
    meta.append(name);
    if (message.isPermanent) meta.append(permanentBadge());
    const badge = roleBadge(message.role);
    if (badge) meta.append(badge);
    const time = document.createElement('time');
    time.className = 'message-time';
    time.dateTime = message.createdAt;
    time.textContent = formatTime(message.createdAt);
    meta.append(time);

    if (!message.deleted && state.session?.canModerate) {
        const actions = document.createElement('div');
        actions.className = 'message-actions-inline';
        actions.append(createActionButton('Usuń', 'delete-message', { messageId: message.id, targetId: message.authorId }, 'danger'));
        meta.append(actions);
    }

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';
    if (message.deleted) {
        const body = document.createElement('p');
        body.className = 'message-body';
        body.textContent = 'Ta wiadomość została usunięta przez moderację.';
        bubble.append(body);
    } else {
        const imageContent = createImageContent(message);
        if (imageContent) bubble.append(imageContent);
        if (message.body) {
            const body = document.createElement('p');
            body.className = 'message-body';
            body.textContent = message.body;
            bubble.append(body);
        }
    }

    stack.append(meta, bubble);
    item.append(stack);
    return item;
}

function renderMessages(messages, replace = false, shouldScroll = true) {
    if (replace) {
        elements.messages.replaceChildren();
        state.lastMessageId = 0;
    }

    for (const message of messages) {
        const existing = elements.messages.querySelector(`[data-message-id="${CSS.escape(String(message.id))}"]`);
        if (existing) existing.replaceWith(createMessageElement(message));
        else elements.messages.append(createMessageElement(message));
        state.lastMessageId = Math.max(state.lastMessageId, message.id);
    }

    if (elements.messages.childElementCount === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty-state';
        empty.textContent = 'Jeszcze nikt nie napisał. Rozpocznij rozmowę.';
        elements.messages.append(empty);
    } else {
        elements.messages.querySelector('.empty-state')?.remove();
    }

    state.hasLoadedMessages = true;
    if (shouldScroll) scrollToBottom();
}

/** Usuwa z otwartego widoku wpisy fizycznie skasowane przez 24-godzinną retencję. */
function removePurgedMessages(messageIds) {
    for (const messageId of messageIds) {
        const selector = `[data-message-id="${CSS.escape(String(messageId))}"]`;
        elements.messages.querySelector(selector)?.remove();
    }
    if (messageIds.length > 0) renderMessages([], false, false);
}

function formatLastActivity(lastSeen, serverTime) {
    const date = new Date(`${String(lastSeen ?? '').replace(' ', 'T')}Z`);
    const reference = new Date(serverTime ?? '');
    if (Number.isNaN(date.getTime())) return 'ostatnia aktywność: nieznana';
    const now = Number.isNaN(reference.getTime()) ? Date.now() : reference.getTime();
    const minutes = Math.max(0, Math.floor((now - date.getTime()) / 60000));
    if (minutes < 2) return 'aktywny teraz';
    if (minutes < 60) return `aktywny ${minutes} min temu`;
    const hours = Math.floor(minutes / 60);
    return `aktywny ${hours} godz. temu`;
}

function formatBlockedUntil(value) {
    const date = new Date(`${String(value ?? '').replace(' ', 'T')}Z`);
    if (Number.isNaN(date.getTime())) return 'do później';
    return `do ${date.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' })}`;
}

function renderBlocks(blocks) {
    state.blocks = blocks;
    if (!elements.blocks || !elements.blocksList) return;
    const isAdmin = state.session?.isAdmin === true;
    elements.blocks.hidden = !isAdmin || blocks.length === 0;
    if (!isAdmin || blocks.length === 0) {
        elements.blocksList.replaceChildren();
        return;
    }
    const fragment = document.createDocumentFragment();
    for (const block of blocks) {
        const row = document.createElement('div');
        row.className = 'blocked-nickname-row';
        const name = document.createElement('span');
        name.textContent = block.nickname;
        const until = document.createElement('small');
        until.textContent = formatBlockedUntil(block.blocked_until);
        row.append(name, until, createActionButton('Cofnij', 'unblock-nickname', { blockId: block.id }));
        fragment.append(row);
    }
    elements.blocksList.replaceChildren(fragment);
}

function renderUsers(users) {
    state.users = users;
    const fragment = document.createDocumentFragment();

    if (users.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty-state';
        empty.textContent = 'Brak aktywnych osób.';
        fragment.append(empty);
    }

    for (const user of users) {
        const row = document.createElement('div');
        row.className = `user-row${state.selectedPrivateTarget?.id === user.id ? ' is-selected' : ''}`;
        row.dataset.userId = String(user.id);
        const dot = document.createElement('span');
        dot.className = `user-dot ${user.role}${user.isOnline ? '' : ' is-away'}`;
        dot.setAttribute('aria-hidden', 'true');
        const summary = document.createElement('div');
        summary.className = 'user-summary';
        const nameLine = document.createElement('span');
        nameLine.className = 'user-name-line';
        const name = document.createElement('span');
        name.className = 'user-name';
        name.textContent = user.nickname;
        nameLine.append(name);
        if (user.isPermanent) nameLine.append(permanentBadge());
        const activity = document.createElement('span');
        activity.className = 'user-activity';
        activity.textContent = formatLastActivity(user.lastSeen, state.serverTime);
        summary.append(nameLine, activity);
        row.append(dot, summary);
        const badge = roleBadge(user.role);
        if (badge) row.append(badge);

        const isSelf = user.id === state.session?.id;
        if (!isSelf && user.isOnline && state.session?.canModerate) {
            const controls = document.createElement('div');
            controls.className = 'user-controls';
            if (state.session.isAdmin && user.role !== 'admin') {
                if (user.role === 'moderator') controls.append(createActionButton('Cofnij', 'revoke-moderator', { targetId: user.id }));
                else {
                    controls.append(createActionButton('Mod. 15m', 'assign-moderator', { targetId: user.id, minutes: 15 }));
                    controls.append(createActionButton('Mod. 1h', 'assign-moderator', { targetId: user.id, minutes: 60 }));
                    controls.append(createActionButton('Mod. 4h', 'assign-moderator', { targetId: user.id, minutes: 240 }));
                }
                controls.append(createActionButton('Zastrzeż nick', 'claim-nickname', { targetId: user.id }));
            }
            if (user.role !== 'admin') {
                controls.append(createActionButton('Wycisz 15m', 'mute-user', { targetId: user.id, minutes: 15 }));
                controls.append(createActionButton('Wycisz 1h', 'mute-user', { targetId: user.id, minutes: 60 }));
                controls.append(createActionButton('Wycisz 2h', 'mute-user', { targetId: user.id, minutes: 120 }));
            }
            if (state.session.isAdmin && user.role !== 'admin') {
                controls.append(createActionButton('Usuń z czatu', 'remove-user', { targetId: user.id }, 'danger'));
                controls.append(createActionButton('Zablokuj 24h', 'block-user', { targetId: user.id }, 'danger'));
                controls.append(createActionButton('Prywatnie', 'private-target', { targetId: user.id }));
            }
            row.append(controls);
        }
        fragment.append(row);
    }
    elements.users.replaceChildren(fragment);
}

function updateSession(session) {
    state.session = session;
    if (session?.csrfToken) state.csrfToken = session.csrfToken;
    if (session && !session.needsTerms) {
        state.unreadCount = Math.max(0, Number(session.unreadCount) || 0);
        updateUnreadNotice();
    }
    const authenticated = session !== null;
    elements.messageInput.disabled = !authenticated;
    elements.sendButton.disabled = !authenticated;
    elements.selectImage.disabled = !authenticated;
    elements.openPrivate.hidden = !authenticated;
}

/** Zwalnia adres blob i czyści wybrany przez użytkownika, nietrwały podgląd zdjęcia. */
function clearSelectedImage() {
    if (state.selectedImage?.url) URL.revokeObjectURL(state.selectedImage.url);
    state.selectedImage = null;
    elements.imageInput.value = '';
    elements.imagePreviewImage.removeAttribute('src');
    elements.imagePreview.hidden = true;
}

function selectImage(file) {
    if (!file) return;
    if (!ALLOWED_IMAGE_TYPES.has(file.type)) {
        showAlert('Możesz dodać wyłącznie plik JPG, PNG lub WebP.', true);
        return;
    }
    if (file.size > MAX_IMAGE_BYTES) {
        showAlert('Zdjęcie może mieć maksymalnie 8 MB.', true);
        return;
    }
    clearSelectedImage();
    const url = URL.createObjectURL(file);
    state.selectedImage = { file, url };
    elements.imagePreviewImage.src = url;
    elements.imagePreview.hidden = false;
}

function showEntryView(view) {
    const showChoice = view === 'choice';
    elements.entryChoice.hidden = !showChoice;
    elements.accountLoginForm.hidden = view !== 'login';
    elements.accountRegisterForm.hidden = view !== 'register';
    if (showChoice) window.setTimeout(() => elements.nicknameInput.focus(), 0);
    if (view === 'login') window.setTimeout(() => elements.accountLoginNickname.focus(), 0);
    if (view === 'register') window.setTimeout(() => elements.accountRegisterNickname.focus(), 0);
}

function openNicknameModal() {
    elements.nicknameModal.hidden = false;
    elements.nicknameError.hidden = true;
    elements.accountLoginError.hidden = true;
    elements.accountRegisterError.hidden = true;
    showEntryView('choice');
}

function closeNicknameModal() { elements.nicknameModal.hidden = true; }

function openTermsModal() {
    elements.termsModal.hidden = false;
    elements.termsConsent.checked = false;
    elements.termsError.hidden = true;
    window.setTimeout(() => elements.termsConsent.focus(), 0);
}

function closeTermsModal() { elements.termsModal.hidden = true; }

async function finishEntry(payload) {
    updateSession(payload.session);
    renderUsers(payload.users ?? []);
    closeNicknameModal();
    if (payload.session?.needsTerms) {
        openTermsModal();
        return;
    }
    await refresh({ initial: true });
    await markPublicMessagesRead();
    startPolling();
    elements.messageInput.focus();
}

function setPrivateTarget(target) {
    state.selectedPrivateTarget = target;
    elements.privateTargetNote.textContent = state.session?.isAdmin
        ? (target ? `Odpowiadasz prywatnie użytkownikowi: ${target.nickname}.` : 'Wybierz użytkownika z listy, aby odpowiedzieć.')
        : 'Tu możesz poprosić o zastrzeżenie pseudonimu albo przekazać sprawę administratorowi.';
}

function createPrivateMessage(message) {
    const node = document.createElement('div');
    node.className = `private-message${message.outgoing ? ' outgoing' : ''}`;
    const label = document.createElement('small');
    label.textContent = `${message.outgoing ? 'Ty' : message.counterpartNickname} · ${formatTime(message.createdAt)}`;
    node.append(label, document.createTextNode(message.body));
    return node;
}

function renderPrivateMessages(messages) {
    elements.privateMessages.replaceChildren();
    if (messages.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty-state';
        empty.textContent = 'Brak wiadomości prywatnych.';
        elements.privateMessages.append(empty);
    } else {
        for (const message of messages) elements.privateMessages.append(createPrivateMessage(message));
        elements.privateMessages.scrollTop = elements.privateMessages.scrollHeight;
    }
}

async function openPrivateModal() {
    if (state.session?.isAdmin && !state.selectedPrivateTarget) {
        showAlert('Najpierw wybierz użytkownika z listy „Osoby online”.', true);
        return;
    }
    state.lastPrivateFocus = document.activeElement;
    setPrivateTarget(state.selectedPrivateTarget);
    elements.privateModal.hidden = false;
    elements.privateError.hidden = true;
    try {
        const payload = await api('private.php');
        renderPrivateMessages(payload.messages ?? []);
        elements.privateInput.focus();
    } catch (error) {
        renderPrivateMessages([]);
        elements.privateError.textContent = error.message;
        elements.privateError.hidden = false;
    }
}

function closePrivateModal() {
    elements.privateModal.hidden = true;
    elements.privateForm.reset();
    state.lastPrivateFocus?.focus();
    state.lastPrivateFocus = null;
}

async function refresh({ initial = false } = {}) {
    if (state.isPolling || !state.session) return;
    state.isPolling = true;
    try {
        const query = new URLSearchParams();
        if (!initial && state.lastMessageId > 0) query.set('after', String(state.lastMessageId));
        if (!initial && state.changedSince) query.set('changedSince', state.changedSince);
        const suffix = query.size ? `?${query.toString()}` : '';
        const wasNearBottom = initial || isNearBottom();
        const payload = await api(`feed.php${suffix}`);
        state.serverTime = payload.serverTime ?? new Date().toISOString();
        updateSession(payload.session);
        removePurgedMessages(payload.purgedMessageIds ?? []);
        const newMessages = payload.messages ?? [];
        const incomingMessages = newMessages.filter((message) => message.authorId !== state.session?.id);
        renderMessages(newMessages, initial, wasNearBottom);
        renderUsers(payload.users ?? []);
        renderBlocks(payload.blocks ?? []);
        if (!initial && incomingMessages.length > 0) {
            if (wasNearBottom) clearUnreadMessages();
            else state.unreadCount += incomingMessages.length;
            updateUnreadNotice();
            playNotificationSound();
        }
        state.changedSince = state.serverTime;
        setConnectionStatus('Połączono', true);
    } catch (error) {
        if (error.status === 401) {
            state.session = null;
            clearSelectedImage();
            window.clearInterval(state.pollingTimer);
            openNicknameModal();
        } else if (error.status === 428) {
            openTermsModal();
        }
        setConnectionStatus('Ponawianie połączenia…', false);
    } finally {
        state.isPolling = false;
    }
}

function startPolling() {
    window.clearInterval(state.pollingTimer);
    state.pollingTimer = window.setInterval(() => refresh(), 2500);
}

async function performModeration(action, targetId, extra = {}) {
    try {
        const payload = await api('moderate.php', { method: 'POST', data: { action, targetId, ...extra } });
        showAlert(payload.message ?? 'Wykonano działanie moderacyjne.');
        await refresh();
    } catch (error) {
        showAlert(error.message, true);
    }
}

async function bootstrap() {
    setConnectionStatus('Łączenie…');
    try {
        const payload = await api('bootstrap.php');
        state.serverTime = payload.serverTime ?? new Date().toISOString();
        updateSession(payload.session);
        renderMessages(payload.messages ?? [], true);
        renderUsers(payload.users ?? []);
        renderBlocks(payload.blocks ?? []);
        state.changedSince = payload.serverTime;
        setConnectionStatus('Połączono', true);
        if (payload.needNickname) openNicknameModal();
        else if (payload.session?.needsTerms) openTermsModal();
        else {
            await markPublicMessagesRead();
            startPolling();
        }
    } catch (error) {
        setConnectionStatus('Czat jest chwilowo niedostępny', false);
        showAlert(error.message, true);
    }
}

elements.showLogin.addEventListener('click', () => showEntryView('login'));
elements.showRegister.addEventListener('click', () => showEntryView('register'));
document.querySelectorAll('[data-entry-back]').forEach((button) => button.addEventListener('click', () => showEntryView('choice')));

elements.nicknameForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    elements.nicknameError.hidden = true;
    try {
        const payload = await api('join.php', { method: 'POST', data: { nickname: elements.nicknameInput.value } });
        elements.nicknameForm.reset();
        await finishEntry(payload);
    } catch (error) {
        elements.nicknameError.textContent = error.message;
        elements.nicknameError.hidden = false;
    }
});

elements.accountLoginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    elements.accountLoginError.hidden = true;
    try {
        const payload = await api('login-account.php', { method: 'POST', data: {
            nickname: elements.accountLoginNickname.value,
            password: elements.accountLoginPassword.value
        } });
        elements.accountLoginForm.reset();
        await finishEntry(payload);
    } catch (error) {
        elements.accountLoginError.textContent = error.message;
        elements.accountLoginError.hidden = false;
    }
});

elements.accountRegisterForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    elements.accountRegisterError.hidden = true;
    if (elements.accountRegisterPassword.value !== elements.accountRegisterPasswordConfirm.value) {
        elements.accountRegisterError.textContent = 'Hasła nie są identyczne.';
        elements.accountRegisterError.hidden = false;
        return;
    }
    try {
        const payload = await api('register-account.php', { method: 'POST', data: {
            nickname: elements.accountRegisterNickname.value,
            password: elements.accountRegisterPassword.value,
            terms: elements.accountRegisterTerms.checked
        } });
        elements.accountRegisterForm.reset();
        await finishEntry(payload);
    } catch (error) {
        elements.accountRegisterError.textContent = error.message;
        elements.accountRegisterError.hidden = false;
    }
});

elements.acceptTerms.addEventListener('click', async () => {
    elements.termsError.hidden = true;
    if (!elements.termsConsent.checked) {
        elements.termsError.textContent = 'Zaznacz akceptację regulaminu, aby wejść do pokoju.';
        elements.termsError.hidden = false;
        return;
    }
    elements.acceptTerms.disabled = true;
    try {
        const payload = await api('accept-terms.php', { method: 'POST', data: { accepted: true } });
        updateSession(payload.session);
        closeTermsModal();
        await refresh({ initial: true });
        await markPublicMessagesRead();
        startPolling();
        elements.messageInput.focus();
    } catch (error) {
        elements.termsError.textContent = error.message;
        elements.termsError.hidden = false;
    } finally {
        elements.acceptTerms.disabled = false;
    }
});

elements.messageInput.addEventListener('input', () => {
    elements.messageLength.textContent = String(elements.messageInput.value.length);
    elements.messageInput.style.height = 'auto';
    elements.messageInput.style.height = `${Math.min(elements.messageInput.scrollHeight, 100)}px`;
});

elements.selectImage.addEventListener('click', () => elements.imageInput.click());
elements.imageInput.addEventListener('change', () => selectImage(elements.imageInput.files?.[0] ?? null));
elements.removeImage.addEventListener('click', clearSelectedImage);

elements.messageForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = elements.messageInput.value;
    if (!message.trim() && !state.selectedImage) return;
    elements.sendButton.disabled = true;
    elements.selectImage.disabled = true;
    try {
        const data = new FormData();
        data.append('message', message);
        if (state.selectedImage) data.append('image', state.selectedImage.file);
        const payload = await api('send.php', { method: 'POST', data });
        elements.messageInput.value = '';
        elements.messageInput.style.height = 'auto';
        elements.messageLength.textContent = '0';
        clearSelectedImage();
        renderMessages([payload.message]);
    } catch (error) {
        showAlert(error.message, true);
    } finally {
        elements.sendButton.disabled = false;
        elements.selectImage.disabled = false;
        elements.messageInput.focus();
    }
});

elements.messages.addEventListener('scroll', () => {
    if (isNearBottom()) {
        clearUnreadMessages();
        markPublicMessagesRead();
    }
}, { passive: true });

window.addEventListener('message', (event) => {
    if (event.origin !== window.location.origin || event.data?.type !== 'CHAT_MARK_READ') {
        return;
    }
    clearUnreadMessages();
    markPublicMessagesRead();
});

elements.messages.addEventListener('click', (event) => {
    const button = event.target.closest('[data-action="delete-message"]');
    if (!button) return;
    performModeration('delete_message', Number(button.dataset.targetId), { messageId: Number(button.dataset.messageId) });
});

elements.users.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const targetId = Number(button.dataset.targetId);
    const target = state.users.find((user) => user.id === targetId) ?? null;
    const action = button.dataset.action;
    if (action === 'private-target') {
        setPrivateTarget(target);
        renderUsers(state.users);
        await openPrivateModal();
        return;
    }
    if (action === 'claim-nickname') {
        try {
            const payload = await api('claim-nickname.php', { method: 'POST', data: { targetId } });
            showAlert(payload.message);
        } catch (error) {
            showAlert(error.message, true);
        }
        return;
    }
    const moderationMap = { 'assign-moderator': 'assign_moderator', 'revoke-moderator': 'revoke_moderator', 'mute-user': 'mute_user', 'remove-user': 'remove_user', 'block-user': 'block_user' };
    if (moderationMap[action] && window.confirm('Czy na pewno chcesz wykonać tę akcję moderacyjną?')) {
        await performModeration(moderationMap[action], targetId, { minutes: Number(button.dataset.minutes ?? 0) });
    }
});

elements.blocksList?.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-action="unblock-nickname"]');
    if (!button || !window.confirm('Czy na pewno cofnąć blokadę nicku?')) return;
    await performModeration('unblock_user', 0, { blockId: Number(button.dataset.blockId) });
});

document.addEventListener('keydown', (event) => {
    if (!elements.privateModal.hidden) {
        if (event.key === 'Tab') trapFocus(event, elements.privateModal);
        else if (event.key === 'Escape') closePrivateModal();
        return;
    }
    if (!elements.termsModal.hidden) {
        if (event.key === 'Tab') trapFocus(event, elements.termsModal);
        else if (event.key === 'Escape') requestChatroomClose();
        return;
    }
    if (!elements.nicknameModal.hidden) {
        if (event.key === 'Tab') trapFocus(event, elements.nicknameModal);
        else if (event.key === 'Escape') requestChatroomClose();
        return;
    }
    if (event.key === 'Escape') requestChatroomClose();
});

elements.openPrivate.addEventListener('click', openPrivateModal);
elements.closePrivate.addEventListener('click', closePrivateModal);
elements.privateModal.addEventListener('click', (event) => { if (event.target === elements.privateModal) closePrivateModal(); });
elements.privateForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    elements.privateError.hidden = true;
    const message = elements.privateInput.value;
    if (!message.trim() || (state.session?.isAdmin && !state.selectedPrivateTarget)) return;
    try {
        const data = { message };
        if (state.session?.isAdmin) data.targetId = state.selectedPrivateTarget.id;
        await api('private.php', { method: 'POST', data });
        elements.privateInput.value = '';
        const payload = await api('private.php');
        renderPrivateMessages(payload.messages ?? []);
    } catch (error) {
        elements.privateError.textContent = error.message;
        elements.privateError.hidden = false;
    }
});

elements.usersToggle.addEventListener('click', () => {
    const collapsed = elements.peoplePanel.classList.toggle('is-collapsed');
    elements.usersToggle.setAttribute('aria-expanded', String(!collapsed));
    elements.usersToggle.textContent = collapsed ? 'Rozwiń' : 'Zwiń';
});

elements.messages.addEventListener('scroll', () => {
    if (isNearBottom()) clearUnreadMessages();
});
elements.newMessagesNotice?.addEventListener('click', () => {
    clearUnreadMessages();
    scrollToBottom();
    elements.messages.focus({ preventScroll: true });
});
document.addEventListener('pointerdown', unlockAudio, { passive: true });
document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') refresh(); });
window.addEventListener('beforeunload', clearSelectedImage);

initializeTheme();
initializeNotifications();
observeComposerSpace();
bootstrap();
