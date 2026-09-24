(() => {
    const hamburgerButton = document.getElementById('hamburger-btn');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    const closeHamburgerButton = document.getElementById('close-hamburger');
    const chatroomOverlay = document.getElementById('chatroom-overlay');
    const chatroomFrame = document.getElementById('chatroom-frame');
    const chatroomCloseButton = document.querySelector('[data-close-chatroom]');
    const chatUnreadBadge = document.getElementById('header-chat-unread');
    const chatroomOrigin = window.location.origin;
    let lastChatroomFocus = null;
    let unreadRefreshInProgress = false;

    const getFocusableElements = (container) => Array.from(container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])')).filter((element) => element.getClientRects().length > 0 && !element.closest('[hidden], [aria-hidden="true"]'));

    const trapFocus = (event, container) => {
        if (event.key !== 'Tab') {
            return;
        }
        const focusableElements = getFocusableElements(container);
        if (focusableElements.length === 0) {
            return;
        }
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        if (!container.contains(document.activeElement)) {
            event.preventDefault();
            firstElement.focus();
            return;
        }
        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    };

    const setChatUnreadCount = (value) => {
        if (!chatUnreadBadge) {
            return;
        }
        const count = Math.max(0, Number(value) || 0);
        chatUnreadBadge.hidden = count === 0;
        chatUnreadBadge.textContent = count > 99 ? '99+' : String(count);
    };

    const refreshChatUnreadCount = async () => {
        if (!chatUnreadBadge || unreadRefreshInProgress || document.hidden) {
            return;
        }
        unreadRefreshInProgress = true;
        try {
            const response = await fetch(window.location.origin + '/chatroom/api/unread.php', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            setChatUnreadCount(payload?.unreadCount);
        } catch {
        } finally {
            unreadRefreshInProgress = false;
        }
    };

    const closeMenu = (restoreFocus = true) => {
        if (!hamburgerMenu || !hamburgerButton) {
            return;
        }
        hamburgerMenu.classList.remove('active');
        hamburgerMenu.setAttribute('aria-hidden', 'true');
        hamburgerButton.classList.remove('active');
        hamburgerButton.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-open');
        if (restoreFocus) {
            hamburgerButton.focus();
        }
        hamburgerMenu.hidden = true;
    };

    const openMenu = () => {
        if (!hamburgerMenu || !hamburgerButton) {
            return;
        }
        hamburgerMenu.hidden = false;
        hamburgerMenu.classList.add('active');
        hamburgerMenu.setAttribute('aria-hidden', 'false');
        hamburgerButton.classList.add('active');
        hamburgerButton.setAttribute('aria-expanded', 'true');
        document.body.classList.add('menu-open');
        hamburgerMenu.querySelector('.hamburger-menu-close')?.focus();
    };

    const closeChatroom = () => {
        if (!chatroomOverlay) {
            return;
        }
        chatroomOverlay.hidden = true;
        document.body.classList.remove('chatroom-open');
        lastChatroomFocus?.focus();
    };

    const openChatroom = (trigger) => {
        if (!chatroomOverlay || !chatroomFrame) {
            return;
        }
        lastChatroomFocus = trigger;
        closeMenu(false);
        if (!chatroomFrame.src) {
            chatroomFrame.src = chatroomFrame.dataset.src ?? '';
        }
        chatroomOverlay.hidden = false;
        document.body.classList.add('chatroom-open');
        setChatUnreadCount(0);
        chatroomFrame.contentWindow?.postMessage({ type: 'CHAT_MARK_READ' }, chatroomOrigin);
        chatroomCloseButton?.focus();
    };

    hamburgerButton?.addEventListener('click', () => {
        if (hamburgerMenu?.classList.contains('active')) {
            closeMenu();
            return;
        }
        openMenu();
    });

    closeHamburgerButton?.addEventListener('click', closeMenu);

    const menuTriggers = Array.from(document.querySelectorAll('.public-menu-trigger'));
    menuTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const submenu = document.getElementById(trigger.getAttribute('aria-controls') ?? '');
            if (!submenu) {
                return;
            }
            const isOpen = trigger.getAttribute('aria-expanded') === 'true';
            menuTriggers.forEach((otherTrigger) => {
                if (otherTrigger === trigger) {
                    return;
                }
                const otherSubmenu = document.getElementById(otherTrigger.getAttribute('aria-controls') ?? '');
                otherTrigger.setAttribute('aria-expanded', 'false');
                if (otherSubmenu) {
                    otherSubmenu.hidden = true;
                }
            });
            trigger.setAttribute('aria-expanded', String(!isOpen));
            submenu.hidden = isOpen;
        });
    });

    const nestedMenuTriggers = Array.from(document.querySelectorAll('.public-menu-nested-trigger'));
    nestedMenuTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const submenu = document.getElementById(trigger.getAttribute('aria-controls') ?? '');
            if (!submenu) {
                return;
            }
            const isOpen = trigger.getAttribute('aria-expanded') === 'true';
            nestedMenuTriggers.forEach((otherTrigger) => {
                if (otherTrigger === trigger) {
                    return;
                }
                const otherSubmenu = document.getElementById(otherTrigger.getAttribute('aria-controls') ?? '');
                otherTrigger.setAttribute('aria-expanded', 'false');
                if (otherSubmenu) {
                    otherSubmenu.hidden = true;
                }
            });
            trigger.setAttribute('aria-expanded', String(!isOpen));
            submenu.hidden = isOpen;
        });
    });

    document.querySelectorAll('.public-menu-submenu a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.querySelectorAll('[data-open-chatroom]').forEach((trigger) => {
        trigger.addEventListener('click', () => openChatroom(trigger));
    });

    chatroomCloseButton?.addEventListener('click', closeChatroom);

    chatroomFrame?.addEventListener('load', () => {
        chatroomFrame.contentWindow?.postMessage({ type: 'CHAT_MARK_READ' }, chatroomOrigin);
    });

    window.addEventListener('message', (event) => {
        if (event.origin !== chatroomOrigin || event.source !== chatroomFrame?.contentWindow) {
            return;
        }
        if (event.data?.type === 'CHAT_UNREAD') {
            setChatUnreadCount(event.data.count);
        } else if (event.data?.type === 'CHATROOM_CLOSE_REQUEST') {
            closeChatroom();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshChatUnreadCount();
        }
    });

    refreshChatUnreadCount();
    window.setInterval(refreshChatUnreadCount, 30000);

    document.addEventListener('click', (event) => {
        if (!hamburgerMenu?.classList.contains('active') || !hamburgerButton) {
            return;
        }
        if (!hamburgerMenu.contains(event.target) && !hamburgerButton.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!chatroomOverlay?.hidden) {
            if (event.key === 'Tab') {
                trapFocus(event, chatroomOverlay);
            } else if (event.key === 'Escape') {
                closeChatroom();
            }
            return;
        }
        if (!hamburgerMenu?.classList.contains('active') || document.querySelector('.modal.active, .prelaunch-notice:not([hidden])')) {
            return;
        }
        if (event.key === 'Tab') {
            trapFocus(event, hamburgerMenu);
        } else if (event.key === 'Escape') {
            closeMenu();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const lazyImages = document.querySelectorAll('img[data-src]');
        if (!lazyImages.length || !('IntersectionObserver' in window)) {
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                const image = entry.target;
                image.src = image.dataset.src ?? '';
                image.removeAttribute('data-src');
                observer.unobserve(image);
            });
        }, { rootMargin: '50px 0px', threshold: 0.01 });
        lazyImages.forEach((image) => observer.observe(image));
    });
})();

(() => {
    const sectorsRoot = document.querySelector('[data-home-sectors]');
    const switcher = document.querySelector('.mobile-sector-switcher');
    if (!sectorsRoot || !switcher || !document.body.classList.contains('home-page')) {
        return;
    }

    const track = sectorsRoot.querySelector('.mobile-sector-track');
    const tabs = Array.from(switcher.querySelectorAll('[data-home-sector]'));
    const panels = Array.from(sectorsRoot.querySelectorAll('[data-home-sector-panel]'));
    const status = document.getElementById('mobile-sector-status');
    const plusButton = document.getElementById('plus-btn');
    const mobileView = window.matchMedia('(max-width: 600px)');
    const labels = { ads: 'Ogłoszenia', blog: 'Kronika miasta' };
    const plusActions = {
        ads: { href: 'submit-ad.php', label: 'Dodaj ogłoszenie' },
        blog: { href: 'submit-post.php', label: 'Dodaj wpis do Kroniki' },
    };
    const savedScrollPositions = new Map();
    let activeSector = 'ads';
    let pointerState = null;
    let suppressNextClick = false;
    let revealTimeout = 0;
    let ignoreScrollUntil = 0;
    let lastScrollY = window.scrollY;

    const getSectorFromUrl = () => new URL(window.location.href).searchParams.get('sektor') === 'blog' ? 'blog' : 'ads';
    const getSectorOffset = (sector) => sector === 'blog' ? -50 : 0;

    const updatePlusButton = (sector) => {
        const action = plusActions[sector] ?? plusActions.ads;
        if (!plusButton) {
            return;
        }
        plusButton.href = action.href;
        plusButton.dataset.openSubmission = sector === 'blog' ? 'chronicle' : 'ads';
        plusButton.setAttribute('aria-label', action.label);
        plusButton.title = action.label;
    };

    const updateTrack = (offset) => {
        track?.style.setProperty('--home-sector-translate', `${offset}%`);
    };

    const showNavigation = () => {
        document.body.classList.remove('home-bottom-nav-hidden');
        document.dispatchEvent(new CustomEvent('home-bottom-nav-visible'));
    };

    const scheduleNavigationReveal = () => {
        window.clearTimeout(revealTimeout);
        revealTimeout = window.setTimeout(showNavigation, 420);
    };

    const selectSector = (sector, updateHistory = false) => {
        const nextSector = sector === 'blog' ? 'blog' : 'ads';
        savedScrollPositions.set(activeSector, window.scrollY);
        activeSector = nextSector;
        document.body.dataset.homeMode = activeSector;
        updatePlusButton(activeSector);
        document.dispatchEvent(new CustomEvent('home-sector-change', { detail: { sector: activeSector } }));

        tabs.forEach((tab) => {
            const isActive = tab.dataset.homeSector === activeSector;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
            const isActive = panel.dataset.homeSectorPanel === activeSector;
            panel.hidden = !isActive;
            panel.setAttribute('aria-hidden', String(!isActive));
            panel.toggleAttribute('inert', !isActive);
        });

        updateTrack(getSectorOffset(activeSector));
        if (status) {
            status.textContent = labels[activeSector];
        }
        showNavigation();

        if (updateHistory) {
            const url = new URL(window.location.href);
            url.searchParams.set('sektor', activeSector);
            window.history.pushState({ homeSector: activeSector }, '', url);
        }

        const nextScrollY = savedScrollPositions.get(activeSector) ?? 0;
        window.requestAnimationFrame(() => window.scrollTo(0, nextScrollY));
    };

    const finishDrag = (event) => {
        if (!pointerState || event.pointerId !== pointerState.id) {
            return;
        }
        const { direction, startX } = pointerState;
        const distanceX = event.clientX - startX;
        const threshold = Math.max(56, sectorsRoot.clientWidth * 0.18);
        const wasHorizontalSwipe = direction === 'horizontal' && Math.abs(distanceX) > 10;
        let nextSector = activeSector;

        if (wasHorizontalSwipe) {
            suppressNextClick = true;
        }
        if (direction === 'horizontal' && Math.abs(distanceX) >= threshold) {
            if (distanceX < 0 && activeSector === 'ads') {
                nextSector = 'blog';
            } else if (distanceX > 0 && activeSector === 'blog') {
                nextSector = 'ads';
            }
        }

        track?.classList.remove('is-dragging');
        pointerState = null;
        selectSector(nextSector, nextSector !== activeSector);
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => selectSector(tab.dataset.homeSector ?? 'ads', tab.dataset.homeSector !== activeSector));
    });

    switcher.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
            return;
        }
        event.preventDefault();
        const nextSector = event.key === 'ArrowLeft' || event.key === 'Home' ? 'ads' : 'blog';
        selectSector(nextSector, nextSector !== activeSector);
        tabs.find((tab) => tab.dataset.homeSector === nextSector)?.focus();
    });

    sectorsRoot.addEventListener('pointerdown', (event) => {
        if (!mobileView.matches || event.pointerType === 'mouse') {
            return;
        }
        pointerState = { id: event.pointerId, startX: event.clientX, startY: event.clientY, direction: null };
    }, { passive: true });

    sectorsRoot.addEventListener('pointermove', (event) => {
        if (!pointerState || event.pointerId !== pointerState.id || !mobileView.matches) {
            return;
        }
        const distanceX = event.clientX - pointerState.startX;
        const distanceY = event.clientY - pointerState.startY;
        if (!pointerState.direction && Math.max(Math.abs(distanceX), Math.abs(distanceY)) > 10) {
            pointerState.direction = Math.abs(distanceX) > Math.abs(distanceY) ? 'horizontal' : 'vertical';
            if (pointerState.direction === 'horizontal') {
                sectorsRoot.setPointerCapture?.(event.pointerId);
                track?.classList.add('is-dragging');
            }
        }
        if (pointerState.direction !== 'horizontal') {
            return;
        }
        event.preventDefault();
        const viewportWidth = Math.max(sectorsRoot.clientWidth, 1);
        const offset = Math.min(0, Math.max(-50, getSectorOffset(activeSector) + (distanceX / viewportWidth) * 50));
        updateTrack(offset);
    }, { passive: false });

    sectorsRoot.addEventListener('pointerup', finishDrag);
    sectorsRoot.addEventListener('pointercancel', finishDrag);
    sectorsRoot.addEventListener('click', (event) => {
        if (!suppressNextClick) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        suppressNextClick = false;
    }, { capture: true });

    window.addEventListener('scroll', () => {
        if (!mobileView.matches) {
            return;
        }
        const currentScrollY = window.scrollY;
        if (performance.now() < ignoreScrollUntil) {
            lastScrollY = currentScrollY;
            scheduleNavigationReveal();
            return;
        }
        const scrollingDown = currentScrollY > lastScrollY + 8;
        const scrollingUp = currentScrollY < lastScrollY - 4;
        if (currentScrollY < 24 || scrollingUp) {
            showNavigation();
        } else if (scrollingDown) {
            document.body.classList.add('home-bottom-nav-hidden');
            ignoreScrollUntil = performance.now() + 260;
        }
        lastScrollY = currentScrollY;
        scheduleNavigationReveal();
    }, { passive: true });

    window.addEventListener('popstate', () => selectSector(getSectorFromUrl()));
    mobileView.addEventListener('change', () => {
        showNavigation();
        panels.forEach((panel) => {
            const isActive = panel.dataset.homeSectorPanel === activeSector;
            panel.hidden = !isActive;
            panel.setAttribute('aria-hidden', String(!isActive));
            panel.toggleAttribute('inert', !isActive);
        });
        updateTrack(getSectorOffset(activeSector));
    });

    selectSector(getSectorFromUrl());
})();

(() => {
    const categoriesNav = document.querySelector('[data-home-category-nav]');
    const toggle = document.getElementById('home-categories-toggle');
    const drawer = document.getElementById('home-categories-drawer');
    if (!categoriesNav || !toggle || !drawer || !document.body.classList.contains('home-page')) {
        return;
    }

    const label = toggle.querySelector('[data-home-categories-toggle-label]');
    const panels = Array.from(drawer.querySelectorAll('[data-home-category-panel]'));
    let activeSector = document.body.dataset.homeMode === 'blog' ? 'blog' : 'ads';

    const setExpanded = (expanded) => {
        toggle.setAttribute('aria-expanded', String(expanded));
        categoriesNav.classList.toggle('is-expanded', expanded);
        drawer.hidden = !expanded;
        if (label) {
            label.textContent = expanded ? 'Ukryj kategorie' : 'Rozwiń, aby zobaczyć kategorie';
        }
    };

    const selectCategoryPanel = (sector) => {
        activeSector = sector === 'blog' ? 'blog' : 'ads';
        panels.forEach((panel) => {
            panel.hidden = panel.dataset.homeCategoryPanel !== activeSector;
        });
        categoriesNav.setAttribute('aria-label', `Kategorie: ${activeSector === 'blog' ? 'Kronika miasta' : 'Ogłoszenia'}`);
        setExpanded(false);
    };

    toggle.addEventListener('click', () => {
        setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
    });

    document.addEventListener('home-sector-change', (event) => {
        selectCategoryPanel(event.detail?.sector);
    });

    selectCategoryPanel(activeSector);
})();

(() => {
    const skeleton = document.getElementById('page-skeleton');
    const minimumVisibleMs = 400;
    let skeletonStartedAt = performance.now();
    let readyTimeout = 0;

    const markPageReady = (immediately = false) => {
        const elapsed = performance.now() - skeletonStartedAt;
        const delay = immediately ? 0 : Math.max(0, minimumVisibleMs - elapsed);
        window.clearTimeout(readyTimeout);
        readyTimeout = window.setTimeout(() => {
            document.body.classList.remove('page-is-loading');
            document.body.classList.add('page-ready');
        }, delay);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => markPageReady(), { once: true });
    } else {
        markPageReady();
    }
    window.addEventListener('pageshow', () => markPageReady(true));
})();
