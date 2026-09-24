(() => {
    'use strict';

    const modal = document.getElementById('pulse-modal');
    const dialog = modal?.querySelector('.pulse-modal-content');
    const form = document.getElementById('pulse-form');
    const message = document.getElementById('pulse-message');
    const counter = document.getElementById('pulse-message-counter');
    const status = document.getElementById('pulse-form-status');
    const feedback = document.getElementById('pulse-feedback');
    const submit = document.getElementById('pulse-submit');
    const bottomNav = document.querySelector('.bottom-nav');
    const actionBar = bottomNav?.querySelector('.bottom-nav-container');
    const tickerShell = document.querySelector('[data-pulse-ticker-shell]');
    const tickerFrame = document.getElementById('pulse-ticker-frame');
    const mobileView = window.matchMedia('(max-width: 600px)');
    const idleDelay = 2200;
    const tickerRefreshDelay = 60000;
    const trustedOrigin = window.location.origin;

    let opener = null;
    let idleTimer = 0;
    let frameReady = false;
    let tickerFingerprint = '';
    let feedbackTimer = 0;

    const setStatus = (text, isError = false) => {
        if (!status) return;
        status.hidden = !text;
        status.textContent = text;
        status.classList.toggle('is-error', isError);
    };

    const showFeedback = (text, isError = false) => {
        if (!feedback || !text) return;
        window.clearTimeout(feedbackTimer);
        feedback.hidden = false;
        feedback.textContent = text;
        feedback.classList.toggle('is-error', isError);
        window.requestAnimationFrame(() => feedback.classList.add('visible'));
        feedbackTimer = window.setTimeout(() => {
            feedback.classList.remove('visible');
            window.setTimeout(() => {
                feedback.hidden = true;
                feedback.classList.remove('is-error');
            }, 200);
        }, 3800);
    };

    const hideFeedback = () => {
        window.clearTimeout(feedbackTimer);
        if (!feedback) return;
        feedback.classList.remove('visible', 'is-error');
        feedback.hidden = true;
    };

    const focusableElements = () => {
        if (!dialog) return [];
        return Array.from(dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'))
            .filter((element) => element instanceof HTMLElement && !element.hidden && element.offsetParent !== null);
    };

    const postToTicker = (type, payload = {}) => {
        if (!frameReady || !tickerFrame?.contentWindow) return;
        tickerFrame.contentWindow.postMessage({ source: 'pulse-parent', type, ...payload }, trustedOrigin);
    };

    const tickerTheme = () => (document.body.classList.contains('dark-mode') || (!document.body.classList.contains('light-mode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) ? 'dark' : 'light';

    const showActions = () => {
        if (!mobileView.matches || !bottomNav) return;
        window.clearTimeout(idleTimer);
        bottomNav.classList.remove('pulse-mobile-idle');
    };

    const showTicker = () => {
        if (!mobileView.matches || !bottomNav || (modal && !modal.hidden)) return;
        document.body.classList.remove('home-bottom-nav-hidden');
        bottomNav.classList.add('pulse-mobile-idle');
        postToTicker('restart');
    };

    const scheduleTicker = () => {
        if (!mobileView.matches || !bottomNav || (modal && !modal.hidden)) return;
        window.clearTimeout(idleTimer);
        idleTimer = window.setTimeout(showTicker, idleDelay);
    };

    const noteActionActivity = () => {
        showActions();
        scheduleTicker();
    };

    const setOpen = (open, source = null) => {
        if (!modal) return;
        if (open) {
            window.clearTimeout(idleTimer);
            opener = source instanceof HTMLElement ? source : document.activeElement;
            modal.hidden = false;
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('pulse-modal-open');
            window.setTimeout(() => message?.focus(), 0);
            return;
        }
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
        document.body.classList.remove('pulse-modal-open');
        if (opener instanceof HTMLElement) opener.focus();
        scheduleTicker();
    };

    document.querySelectorAll('[data-open-pulse]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            setStatus('');
            hideFeedback();
            setOpen(true, button);
        });
    });
    modal?.querySelectorAll('[data-close-pulse]').forEach((button) => button.addEventListener('click', () => setOpen(false)));
    modal?.addEventListener('click', (event) => { if (event.target === modal) setOpen(false); });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            event.preventDefault();
            setOpen(false);
            return;
        }
        if (event.key === 'Tab' && modal && !modal.hidden) {
            const items = focusableElements();
            if (items.length === 0) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
            return;
        }
        if (!modal || modal.hidden) noteActionActivity();
    });

    const updateCounter = () => {
        if (message && counter) counter.textContent = `${Array.from(message.value).length}/160`;
    };
    message?.addEventListener('input', updateCounter);
    updateCounter();

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        setStatus('Publikowanie…');
        if (submit) submit.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.error || 'Nie udało się opublikować komunikatu.');
            const successMessage = payload.message || 'Komunikat został opublikowany.';
            setStatus('');
            form.reset();
            updateCounter();
            showFeedback(successMessage);
            refreshTicker();
            window.setTimeout(() => setOpen(false), 220);
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'Nie udało się opublikować komunikatu.';
            setStatus(errorMessage, true);
            showFeedback(errorMessage, true);
        } finally {
            if (submit) submit.disabled = false;
        }
    });

    const refreshTicker = async () => {
        if (!tickerShell || !tickerFrame || document.hidden) return;
        try {
            const response = await fetch(tickerShell.dataset.pulseApi || (window.location.origin + '/pulse-api.php'), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok || !payload.ok || !Array.isArray(payload.items)) return;
            const fingerprint = JSON.stringify(payload.items);
            if (fingerprint === tickerFingerprint) return;
            tickerFingerprint = fingerprint;
            postToTicker('items', { items: payload.items });
        } catch {
            // Iframe zachowuje lokalnie wyrenderowany komunikat stały przy chwilowej niedostępności API.
        }
    };

    window.addEventListener('message', (event) => {
        if (event.origin !== trustedOrigin || event.source !== tickerFrame?.contentWindow || !event.data || event.data.source !== 'pulse-ticker') return;
        if (event.data.type === 'ready') {
            frameReady = true;
            postToTicker('theme', { theme: tickerTheme() });
            refreshTicker();
        } else if (event.data.type === 'open') {
            setStatus('');
            hideFeedback();
            setOpen(true, tickerFrame);
        } else if (event.data.type === 'list') {
            window.location.assign(window.location.origin + '/pulse.php');
        }
    });

    ['pointerdown', 'touchstart'].forEach((eventName) => {
        window.addEventListener(eventName, (event) => {
            if (event.target instanceof Element && event.target.closest('[data-pulse-ticker-shell]')) return;
            noteActionActivity();
        }, { passive: true });
    });
    window.addEventListener('scroll', noteActionActivity, { passive: true });
    document.addEventListener('home-bottom-nav-visible', () => { if (mobileView.matches) scheduleTicker(); });
    actionBar?.addEventListener('focusin', noteActionActivity);
    mobileView.addEventListener('change', () => { showActions(); scheduleTicker(); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshTicker(); });
    window.addEventListener('online', refreshTicker);
    new MutationObserver(() => postToTicker('theme', { theme: tickerTheme() })).observe(document.body, { attributes: true, attributeFilter: ['class'] });

    scheduleTicker();
    window.setTimeout(refreshTicker, 700);
    window.setInterval(refreshTicker, tickerRefreshDelay);
})();
