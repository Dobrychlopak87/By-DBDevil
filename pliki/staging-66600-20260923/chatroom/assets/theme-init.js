(() => {
    const storageKey = '66600-theme';
    let saved = null;
    try {
        saved = localStorage.getItem(storageKey);
    } catch {
        // Gdy pamięć lokalna jest zablokowana, wybór opiera się na ustawieniu systemu.
    }
    const theme = saved === 'light' || saved === 'dark'
        ? saved
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.dataset.theme = theme;
})();
