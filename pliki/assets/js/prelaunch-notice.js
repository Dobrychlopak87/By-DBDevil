(() => {
    const storageKey = '66600-prelaunch-notice-dismissed-v1';
    const notice = document.getElementById('prelaunch-notice');
    const dismissButton = document.getElementById('prelaunch-notice-dismiss');
    let previouslyFocusedElement = null;

    if (!notice || !dismissButton) {
        return;
    }

    const isDismissed = () => {
        try {
            return localStorage.getItem(storageKey) === 'true';
        } catch {
            return false;
        }
    };

    const trapFocus = (event) => {
        if (event.key !== 'Tab') {
            return;
        }
        const focusableElements = Array.from(notice.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter((element) => element.getClientRects().length > 0);
        if (focusableElements.length === 0) {
            return;
        }
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        if (event.shiftKey || document.activeElement === lastElement) {
            event.preventDefault();
            (event.shiftKey ? lastElement : firstElement).focus();
        }
    };

    const hideNotice = () => {
        notice.hidden = true;
        document.body.classList.remove('prelaunch-notice-open');
        previouslyFocusedElement?.focus?.();
    };

    const dismissNotice = () => {
        try {
            localStorage.setItem(storageKey, 'true');
        } catch {
        }
        hideNotice();
    };

    const showNotice = () => {
        if (isDismissed()) {
            return;
        }
        previouslyFocusedElement = document.activeElement;
        notice.hidden = false;
        document.body.classList.add('prelaunch-notice-open');
        dismissButton.focus();
    };

    window.setTimeout(showNotice, 5000);
    dismissButton.addEventListener('click', dismissNotice);
    document.addEventListener('keydown', (event) => {
        if (notice.hidden) {
            return;
        }
        if (event.key === 'Tab') {
            trapFocus(event);
        } else if (event.key === 'Escape') {
            dismissNotice();
        }
    });
})();
