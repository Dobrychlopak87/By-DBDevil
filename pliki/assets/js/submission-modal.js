(() => {
    'use strict';

    const modal = document.getElementById('submission-modal');
    const content = modal?.querySelector('.submission-modal-content');
    const body = document.getElementById('submission-modal-body');
    const status = document.getElementById('submission-modal-status');
    const title = document.getElementById('submission-modal-title');
    const description = document.getElementById('submission-modal-description');
    if (!modal || !content || !body) return;

    const configs = {
        ads: {
            endpoint: `${window.location.origin}/submit-ad.php?fragment=1`,
            title: 'Dodaj ogłoszenie',
            description: 'Wypełnij formularz. Ogłoszenie trafi do moderacji przed publikacją.',
            className: 'submission-modal--ads',
            success: 'Ogłoszenie przekazano do moderacji.'
        },
        chronicle: {
            endpoint: `${window.location.origin}/submit-post.php?fragment=1`,
            title: 'Dodaj wpis do Kroniki',
            description: 'Wypełnij formularz. Wpis trafi do moderacji przed publikacją.',
            className: 'submission-modal--chronicle',
            success: 'Wpis przekazano do moderacji.'
        }
    };

    let opener = null;
    let activeConfig = null;
    let closeTimer = 0;
    const isFullSubmissionPage = /\/submit-(ad|post)\.php$/.test(window.location.pathname);

    const focusable = () => Array.from(content.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'))
        .filter((element) => element instanceof HTMLElement && !element.hidden && element.offsetParent !== null);

    const setStatus = (message = '', isError = false) => {
        if (!status) return;
        status.hidden = !message;
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    };

    const resetBody = () => {
        window.clearTimeout(closeTimer);
        body.replaceChildren();
        setStatus('');
    };

    const close = () => {
        if (modal.hidden) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
        document.body.classList.remove('submission-modal-open');
        resetBody();
        if (opener instanceof HTMLElement) opener.focus();
    };

    const open = async (module, source) => {
        const config = configs[module] || configs.ads;
        activeConfig = config;
        opener = source instanceof HTMLElement ? source : document.activeElement;
        window.clearTimeout(closeTimer);
        content.classList.remove('submission-modal--ads', 'submission-modal--chronicle');
        content.classList.add(config.className);
        title.textContent = config.title;
        description.textContent = config.description;
        body.innerHTML = '<div class="submission-modal-loading" role="status">Ładowanie formularza…</div>';
        setStatus('');
        modal.hidden = false;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('submission-modal-open');
        try {
            const response = await fetch(config.endpoint, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'text/html' } });
            if (!response.ok) throw new Error('Nie udało się załadować formularza.');
            body.innerHTML = await response.text();
            const form = body.querySelector('form.public-ad-form');
            if (!form) throw new Error('Formularz jest chwilowo niedostępny.');
            const categoryId = source?.dataset?.categoryId;
            if (categoryId) {
                const categorySelect = form.querySelector('select[name="category_id"]');
                if (categorySelect && Array.from(categorySelect.options).some((option) => option.value === categoryId)) {
                    categorySelect.value = categoryId;
                }
            }
            window.initializeSubmissionForm?.(form);
            window.setTimeout(() => form.querySelector('input:not([type="hidden"]), textarea, select')?.focus(), 0);
        } catch (error) {
            body.innerHTML = '<div class="public-form-alert error" role="alert">Nie udało się załadować formularza. Możesz użyć pełnej strony zgłoszenia.</div>';
            setStatus(error instanceof Error ? error.message : 'Nie udało się załadować formularza.', true);
            const fallback = document.createElement('a');
            fallback.className = 'public-submit-button secondary submission-fallback-link';
            fallback.href = module === 'chronicle' ? `${window.location.origin}/submit-post.php` : `${window.location.origin}/submit-ad.php`;
            fallback.textContent = 'Otwórz pełną stronę formularza';
            body.appendChild(fallback);
            fallback.focus();
        }
    };

    const submit = async (form) => {
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        setStatus('Wysyłanie zgłoszenia…');
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.error || 'Nie udało się przyjąć zgłoszenia.');
            body.innerHTML = `<div class="public-form-alert success" role="status"><strong>${activeConfig.success}</strong><span>Administrator sprawdzi treść przed publikacją.</span></div><div class="public-form-actions success-actions"><button type="button" class="public-submit-button secondary" data-close-submission> Zamknij </button></div>`;
            setStatus('');
            body.querySelector('[data-close-submission]')?.addEventListener('click', close);
            closeTimer = window.setTimeout(close, 2200);
        } catch (error) {
            setStatus(error instanceof Error ? error.message : 'Nie udało się przyjąć zgłoszenia.', true);
            form.querySelector('input:not([type="hidden"]), textarea, select')?.focus();
        } finally {
            if (button) button.disabled = false;
        }
    };

    document.querySelectorAll('[data-open-submission]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            if (isFullSubmissionPage) return;
            event.preventDefault();
            open(trigger.dataset.openSubmission, trigger);
        });
    });
    modal.querySelectorAll('[data-close-submission]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    body.addEventListener('submit', (event) => {
        const form = event.target.closest('form.public-ad-form');
        if (!form) return;
        event.preventDefault();
        submit(form);
    });
    window.addEventListener('keydown', (event) => {
        if (modal.hidden) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }
        if (event.key !== 'Tab') return;
        const items = focusable();
        if (!items.length) return;
        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();
