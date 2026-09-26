(() => {
    const prompt = document.getElementById('pwa-install-prompt');
    const installButton = document.getElementById('pwa-install-button');
    const dismissButton = document.getElementById('pwa-install-dismiss');
    let deferredInstallPrompt = null;

    if (!prompt || !installButton || !dismissButton) {
        return;
    }

    const hidePrompt = () => {
        prompt.hidden = true;
        deferredInstallPrompt = null;
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        if (sessionStorage.getItem('pwa-install-dismissed') === 'true') {
            return;
        }
        deferredInstallPrompt = event;
        prompt.hidden = false;
    });

    installButton.addEventListener('click', async () => {
        if (!deferredInstallPrompt) {
            return;
        }
        installButton.disabled = true;
        try {
            await deferredInstallPrompt.prompt();
            await deferredInstallPrompt.userChoice;
        } catch {
        } finally {
            installButton.disabled = false;
            hidePrompt();
        }
    });

    dismissButton.addEventListener('click', () => {
        sessionStorage.setItem('pwa-install-dismissed', 'true');
        hidePrompt();
    });

    window.addEventListener('appinstalled', hidePrompt);
})();
