(function () {
    'use strict';

    var shareModal = document.getElementById('share-modal');
    var shareLinkInput = document.getElementById('share-link');
    var shareFacebook = document.getElementById('share-facebook');
    var shareEmail = document.getElementById('share-email');
    var shareClose = document.getElementById('close-share-modal');
    var copyButton = document.getElementById('copy-share-link');
    var feedback = document.getElementById('share-feedback');
    var currentShare = null;
    var lastFocusedElement = null;
    var announcementTimer = 0;

    function announce(message) {
        if (!feedback) return;
        window.clearTimeout(announcementTimer);
        feedback.textContent = message;
        feedback.hidden = false;
        window.requestAnimationFrame(function () {
            feedback.classList.add('visible');
        });
        announcementTimer = window.setTimeout(function () {
            feedback.classList.remove('visible');
            window.setTimeout(function () {
                if (!feedback.classList.contains('visible')) {
                    feedback.hidden = true;
                    feedback.textContent = '';
                }
            }, 180);
        }, 3000);
    }

    function normaliseShareData(trigger) {
        return {
            title: trigger.getAttribute('data-share-title') || document.title,
            text: trigger.getAttribute('data-share-text') || '',
            url: trigger.getAttribute('data-share-url') || window.location.href
        };
    }

    function getFocusableElements() {
        if (!shareModal) return [];
        return Array.prototype.slice.call(shareModal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter(function (element) {
            return element.getClientRects().length > 0 && !element.closest('[aria-hidden="true"]');
        });
    }

    function trapFocus(event) {
        if (event.key !== 'Tab') return;
        var focusableElements = getFocusableElements();
        if (focusableElements.length === 0) return;
        var firstElement = focusableElements[0];
        var lastElement = focusableElements[focusableElements.length - 1];
        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    }

    function openFallback(data, trigger) {
        currentShare = data;
        lastFocusedElement = trigger;
        if (shareLinkInput) {
            shareLinkInput.value = data.url;
        }
        if (shareFacebook) {
            shareFacebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(data.url);
        }
        if (shareEmail) {
            shareEmail.href = 'mailto:?subject=' + encodeURIComponent(data.title) + '&body=' + encodeURIComponent((data.text ? data.text + '\n\n' : '') + data.url);
        }
        if (shareModal) {
            shareModal.classList.add('active');
            shareModal.setAttribute('aria-hidden', 'false');
            window.setTimeout(function () { if (shareClose) shareClose.focus(); }, 0);
        }
    }

    function closeFallback() {
        if (!shareModal || !shareModal.classList.contains('active')) return;
        shareModal.classList.remove('active');
        shareModal.setAttribute('aria-hidden', 'true');
        if (lastFocusedElement) lastFocusedElement.focus();
        lastFocusedElement = null;
    }

    async function share(trigger) {
        var data = normaliseShareData(trigger);
        if (navigator.share) {
            try {
                await navigator.share(data);
                announce('Udostępnianie zakończone.');
                return;
            } catch (error) {
                if (error && error.name === 'AbortError') return;
            }
        }
        openFallback(data, trigger);
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-share-url]');
        if (trigger) {
            event.preventDefault();
            share(trigger);
            return;
        }

        if (event.target === shareModal) {
            closeFallback();
        }
    });

    if (shareClose) {
        shareClose.addEventListener('click', closeFallback);
    }

    if (copyButton) {
        copyButton.addEventListener('click', function () {
            if (!currentShare) return;
            var copied = false;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(currentShare.url).then(function () {
                    announce('Link został skopiowany.');
                    closeFallback();
                }).catch(function () {
                    shareLinkInput.select();
                    copied = document.execCommand('copy');
                    announce(copied ? 'Link został skopiowany.' : 'Skopiuj link ręcznie z pola powyżej.');
                });
            } else if (shareLinkInput) {
                shareLinkInput.select();
                copied = document.execCommand('copy');
                announce(copied ? 'Link został skopiowany.' : 'Skopiuj link ręcznie z pola powyżej.');
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (!shareModal || !shareModal.classList.contains('active')) return;
        if (event.key === 'Tab') trapFocus(event);
        else if (event.key === 'Escape') closeFallback();
    });
})();
