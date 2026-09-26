(() => {
    const openButtons = [...document.querySelectorAll('#contact-btn, [data-open-contact]')];
    const modal = document.getElementById('contact-modal');
    const closeButton = document.getElementById('close-contact-modal');
    const form = document.getElementById('contact-form');
    const status = document.getElementById('contact-form-status');
    const submitButton = document.getElementById('contact-submit');

    if (openButtons.length === 0 || !modal || !closeButton || !form || !status || !submitButton) {
        return;
    }

    const modalTitle = document.getElementById('contact-modal-title');
    const modalDescription = document.getElementById('contact-modal-description');
    const subjectInput = document.getElementById('contact-subject');
    const defaultTitle = modalTitle?.textContent ?? 'Kontakt';
    const defaultDescription = modalDescription?.textContent ?? 'Napisz do redakcji serwisu.';
    let lastFocusedElement = null;

    const trapFocus = (event) => {
        if (event.key !== 'Tab') {
            return;
        }
        const focusableElements = Array.from(modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter((element) => element.getClientRects().length > 0 && !element.closest('[aria-hidden="true"]'));
        if (focusableElements.length === 0) {
            return;
        }
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    };

    const setModalContent = (trigger = null) => {
        if (modalTitle) {
            modalTitle.textContent = trigger?.dataset.contactTitle ?? defaultTitle;
        }
        if (modalDescription) {
            modalDescription.textContent = trigger?.dataset.contactDescription ?? defaultDescription;
        }
        if (subjectInput) {
            subjectInput.value = trigger?.dataset.contactSubject ?? '';
        }
    };

    const closeModal = () => {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        status.hidden = true;
        setModalContent();
        lastFocusedElement?.focus();
    };

    const openModal = (trigger) => {
        lastFocusedElement = document.activeElement;
        setModalContent(trigger);
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => document.getElementById('contact-name')?.focus(), 0);
    };

    openButtons.forEach((button) => button.addEventListener('click', (event) => openModal(event.currentTarget)));
    closeButton.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!modal.classList.contains('active')) {
            return;
        }
        if (event.key === 'Tab') {
            trapFocus(event);
        } else if (event.key === 'Escape') {
            closeModal();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        status.hidden = true;
        submitButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });
            const result = await response.json();

            status.textContent = result.message ?? 'Nie udało się wysłać wiadomości.';
            status.classList.toggle('is-error', !response.ok || !result.ok);
            status.hidden = false;

            if (response.ok && result.ok) {
                form.reset();
            }
        } catch {
            status.textContent = 'Nie udało się połączyć z serwerem. Spróbuj ponownie później.';
            status.classList.add('is-error');
            status.hidden = false;
        } finally {
            submitButton.disabled = false;
        }
    });
})();
