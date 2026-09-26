/**
 * 66600.PL - PDF Viewer - 100% autorski, zero zależności zewnętrznych
 * Wersja: 2.0.0 - RWD + Light/Dark Mode + Touch Gestures
 * Data: 2026-09-25
 * 
 * Cechy:
 * - 100% lokalny, zero CDN, zero zewnętrznych bibliotek
 * - RWD: mobile-first, 44px touch targets, scrollable toolbar, fullscreen na mobile (100dvh), safe-area-inset
 * - Light/Dark Mode: spójny z 66600.pl (var(--mode-*), prefers-color-scheme, body.dark-mode)
 * - Touch: swipe lewo/prawo = zmiana strony, pinch zoom, double-tap zoom
 * - Dostępność: WCAG, klawiatura, aria, focus-visible
 * - Automatyczne wykrywanie wszystkich PDF na stronie
 */

(function() {
  'use strict';

  const CONFIG = {
    selectorLinks: 'a[href$=".pdf"], a[href$=".PDF"], a[href*=".pdf?"], a[href*=".pdf#"]',
    selectorInline: '[data-pdf-src], .pdf-viewer[data-src]',
    defaultZoom: 100,
    zoomSteps: [50, 75, 100, 125, 150, 175, 200, 250],
    storageKey: '66600_pdf_viewer_prefs_v2',
    enableAutoEnhance: true,
    enableSwipe: true,
    enablePinchZoom: true,
    mobileBreakpoint: 768
  };

  const ICONS = {
    pdf: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
    zoomIn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
    zoomOut: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
    fitWidth: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 8 3 3 8 3"/><polyline points="21 8 21 3 16 3"/><polyline points="3 16 3 21 8 21"/><polyline points="21 16 21 21 16 21"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>',
    rotate: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
    download: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    print: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>',
    fullscreen: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>',
    fullscreenExit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    prev: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>',
    next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>',
    external: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
  };

  function safeFilenameFromUrl(url) {
    try {
      const u = new URL(url, window.location.href);
      const pathname = u.pathname;
      const name = pathname.split('/').pop() || 'dokument.pdf';
      return decodeURIComponent(name);
    } catch { return 'dokument.pdf'; }
  }
  function isPdfUrl(url) {
    if (!url) return false;
    const clean = url.split('?')[0].split('#')[0].toLowerCase();
    return clean.endsWith('.pdf');
  }
  function buildPdfSrc(baseSrc, page, zoom) {
    let hash = '#toolbar=0&navpanes=0&scrollbar=1&view=FitH';
    if (page && page > 1) hash += '&page=' + page;
    if (zoom) {
      if (typeof zoom === 'number') hash += '&zoom=' + zoom;
      else hash += '&zoom=' + encodeURIComponent(zoom);
    }
    return baseSrc + hash;
  }
  function loadPrefs() {
    try { const raw = localStorage.getItem(CONFIG.storageKey); return raw ? JSON.parse(raw) : {}; } catch { return {}; }
  }
  function savePrefs(prefs) {
    try { localStorage.setItem(CONFIG.storageKey, JSON.stringify(prefs)); } catch {}
  }
  function isMobile() {
    return window.innerWidth <= CONFIG.mobileBreakpoint || ('ontouchstart' in window);
  }

  function createToolbar(instanceId, isModal) {
    const wrapper = document.createElement('div');
    wrapper.className = 'pdf-viewer-toolbar';
    wrapper.setAttribute('role', 'toolbar');
    wrapper.setAttribute('aria-label', 'Narzędzia PDF');
    wrapper.innerHTML = `
      <div class="pdf-viewer-toolbar__group" role="group" aria-label="Nawigacja stron">
        <button type="button" class="pdf-viewer-btn" data-action="prev" aria-label="Poprzednia strona (strzałka w lewo)">
          ${ICONS.prev}
          <span class="pdf-viewer-tooltip">Poprzednia (←)</span>
        </button>
        <div class="pdf-viewer-toolbar__group--pages" style="display:flex;align-items:center;gap:.45rem;">
          <input type="number" class="pdf-viewer-page-input" data-role="page-input" min="1" value="1" aria-label="Numer strony" inputmode="numeric">
          <span class="pdf-viewer-page-label">/ <span data-role="page-total">?</span></span>
        </div>
        <button type="button" class="pdf-viewer-btn" data-action="next" aria-label="Następna strona (strzałka w prawo)">
          ${ICONS.next}
          <span class="pdf-viewer-tooltip">Następna (→)</span>
        </button>
      </div>
      <div class="pdf-viewer-toolbar__group" role="group" aria-label="Powiększenie">
        <button type="button" class="pdf-viewer-btn" data-action="zoom-out" aria-label="Pomniejsz (-)">
          ${ICONS.zoomOut}
          <span class="pdf-viewer-tooltip">Pomniejsz (-)</span>
        </button>
        <button type="button" class="pdf-viewer-btn" data-role="zoom-label" data-action="zoom-reset" aria-label="Reset powiększenia do 100%" style="width:auto;padding:0 .7rem;font-weight:700;font-size:.84rem;min-width:62px;">100%</button>
        <button type="button" class="pdf-viewer-btn" data-action="zoom-in" aria-label="Powiększ (+)">
          ${ICONS.zoomIn}
          <span class="pdf-viewer-tooltip">Powiększ (+)</span>
        </button>
      </div>
      <div class="pdf-viewer-toolbar__group" role="group" aria-label="Widok">
        <button type="button" class="pdf-viewer-btn" data-action="fit-width" aria-label="Dopasuj do szerokości">
          ${ICONS.fitWidth}
          <span class="pdf-viewer-tooltip">Dopasuj</span>
        </button>
        <button type="button" class="pdf-viewer-btn" data-action="rotate" aria-label="Obróć o 90 stopni">
          ${ICONS.rotate}
          <span class="pdf-viewer-tooltip">Obróć</span>
        </button>
        <button type="button" class="pdf-viewer-btn" data-action="fullscreen" aria-label="Pełny ekran">
          ${ICONS.fullscreen}
          <span class="pdf-viewer-tooltip">Pełny ekran</span>
        </button>
      </div>
      <div class="pdf-viewer-toolbar__group" role="group" aria-label="Akcje pliku">
        <button type="button" class="pdf-viewer-btn" data-action="download" aria-label="Pobierz PDF">
          ${ICONS.download}
          <span class="pdf-viewer-tooltip">Pobierz</span>
        </button>
        <button type="button" class="pdf-viewer-btn" data-action="print" aria-label="Drukuj">
          ${ICONS.print}
          <span class="pdf-viewer-tooltip">Drukuj</span>
        </button>
        ${isModal ? '' : `<button type="button" class="pdf-viewer-btn" data-action="open-new" aria-label="Otwórz w nowej karcie">${ICONS.external}<span class="pdf-viewer-tooltip">Nowa karta</span></button>`}
      </div>
    `;
    return wrapper;
  }

  class PdfViewerInstance {
    constructor(container, src, options = {}) {
      this.container = container;
      this.src = src;
      this.title = options.title || safeFilenameFromUrl(src);
      this.isModal = !!options.isModal;
      this.id = 'pdfv-' + Math.random().toString(36).slice(2, 9);
      this.page = 1;
      this.totalPages = null;
      this.zoom = options.zoom || loadPrefs().zoom || CONFIG.defaultZoom;
      this.rotation = 0;
      this.isFullscreen = false;
      this.touchStartX = 0;
      this.touchStartY = 0;
      this.lastTap = 0;
      this.init();
    }

    init() {
      const el = this.container;
      el.classList.add('pdf-viewer-inline', 'pdf-viewer-root');
      el.setAttribute('data-pdf-viewer-id', this.id);
      el.innerHTML = '';

      const header = document.createElement('div');
      header.className = 'pdf-viewer-inline__header';
      header.innerHTML = `
        <div class="pdf-viewer-inline__title" title="${this.title}">
          ${ICONS.pdf}<span>${this.title}</span>
        </div>
        <div class="pdf-viewer-inline__actions">
          <button type="button" class="pdf-viewer-btn pdf-viewer-btn--primary" data-action="open-modal" aria-label="Otwórz w powiększeniu">
            ${ICONS.fullscreen} <span>Powiększ</span>
          </button>
        </div>
      `;
      el.appendChild(header);

      this.toolbar = createToolbar(this.id, false);
      el.appendChild(this.toolbar);

      this.frameWrap = document.createElement('div');
      this.frameWrap.className = 'pdf-viewer-frame-wrap pdf-viewer-frame-wrap--loading';
      this.frameWrap.innerHTML = `
        <iframe class="pdf-viewer-frame" title="${this.title}" loading="lazy" allow="fullscreen"></iframe>
        <div class="pdf-viewer-status" data-role="status" role="status" aria-live="polite">${ICONS.info}<span>Ładowanie dokumentu...</span></div>
      `;
      el.appendChild(this.frameWrap);

      this.iframe = this.frameWrap.querySelector('iframe');
      this.statusEl = this.frameWrap.querySelector('[data-role="status"]');

      this.bindEvents();
      this.load();
    }

    bindEvents() {
      const self = this;
      this.toolbar.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        self.handleAction(btn.getAttribute('data-action'));
      });

      const pageInput = this.toolbar.querySelector('[data-role="page-input"]');
      if (pageInput) {
        pageInput.addEventListener('change', () => {
          let p = parseInt(pageInput.value, 10);
          if (isNaN(p) || p < 1) p = 1;
          self.goToPage(p);
        });
        pageInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); pageInput.blur(); }
        });
      }

      const openModalBtn = this.container.querySelector('[data-action="open-modal"]');
      if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
          window.PDFViewer66600.openModal(self.src, self.title);
        });
      }

      this.iframe.addEventListener('load', () => {
        self.frameWrap.classList.remove('pdf-viewer-frame-wrap--loading');
        self.showStatus('Dokument załadowany', 2000);
      });
      this.iframe.addEventListener('error', () => self.showError());

      // RWD: fullscreen change
      document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
          self.container.classList.remove('is-fullscreen');
          self.isFullscreen = false;
          self.updateFullscreenIcon();
        }
      });

      // Touch gestures - RWD
      if (CONFIG.enableSwipe) {
        let startX = 0, startY = 0, startTime = 0;
        this.frameWrap.addEventListener('touchstart', (e) => {
          if (e.touches.length !== 1) return;
          startX = e.touches[0].clientX;
          startY = e.touches[0].clientY;
          startTime = Date.now();
          self.touchStartX = startX;
          self.touchStartY = startY;
        }, { passive: true });

        this.frameWrap.addEventListener('touchend', (e) => {
          if (!startX) return;
          const endX = e.changedTouches[0].clientX;
          const endY = e.changedTouches[0].clientY;
          const diffX = endX - startX;
          const diffY = endY - startY;
          const time = Date.now() - startTime;

          // Swipe horizontal - zmiana strony (jeśli przesunięcie > 60px i czas < 500ms i bardziej horizontal niż vertical)
          if (Math.abs(diffX) > 60 && Math.abs(diffX) > Math.abs(diffY) * 1.5 && time < 500) {
            if (diffX < 0) self.goToPage(self.page + 1); // swipe left = next
            else self.goToPage(self.page - 1); // swipe right = prev
          }

          // Double tap - zoom
          const now = Date.now();
          if (now - self.lastTap < 300) {
            // double tap
            if (typeof self.zoom === 'number' && self.zoom < 150) self.setZoom(150);
            else self.setZoom(100);
          }
          self.lastTap = now;

          startX = 0;
        }, { passive: true });
      }

      // Resize observer - RWD
      if (window.ResizeObserver) {
        const ro = new ResizeObserver(() => {
          // na mobile dostosuj wysokość
          if (isMobile()) {
            // nic specjalnego, CSS robi robotę
          }
        });
        ro.observe(this.container);
      }
    }

    handleAction(action) {
      switch(action) {
        case 'prev': this.goToPage(this.page - 1); break;
        case 'next': this.goToPage(this.page + 1); break;
        case 'zoom-in': this.setZoom((typeof this.zoom === 'number' ? this.zoom : 100) + 25); break;
        case 'zoom-out': this.setZoom((typeof this.zoom === 'number' ? this.zoom : 100) - 25); break;
        case 'zoom-reset': this.setZoom(100); break;
        case 'fit-width': this.setZoom('page-width'); break;
        case 'rotate': this.rotate(); break;
        case 'fullscreen': this.toggleFullscreen(); break;
        case 'download': this.download(); break;
        case 'print': this.print(); break;
        case 'open-new': window.open(this.src, '_blank', 'noopener'); break;
      }
    }

    load() {
      const url = buildPdfSrc(this.src, this.page, this.zoom);
      this.iframe.src = url;
      this.updateUI();
    }
    goToPage(p) {
      if (p < 1) p = 1;
      this.page = p;
      const pageInput = this.toolbar.querySelector('[data-role="page-input"]');
      if (pageInput) pageInput.value = this.page;
      this.load();
      this.showStatus('Strona ' + this.page, 1500);
      // Haptic feedback na mobile
      if (navigator.vibrate) navigator.vibrate(20);
    }
    setZoom(z) {
      if (typeof z === 'string') this.zoom = z;
      else { z = Math.max(50, Math.min(250, z)); this.zoom = z; }
      savePrefs({ ...loadPrefs(), zoom: this.zoom });
      this.load();
      this.updateUI();
    }
    rotate() {
      this.rotation = (this.rotation + 90) % 360;
      this.iframe.style.transform = `rotate(${this.rotation}deg) scale(${this.getCssScale()})`;
      if (this.rotation % 180 !== 0) this.frameWrap.style.minHeight = '68vh';
      else this.frameWrap.style.minHeight = '';
      this.showStatus('Obrót: ' + this.rotation + '°', 1500);
    }
    getCssScale() {
      if (typeof this.zoom === 'number') return this.zoom / 100;
      return 1;
    }
    updateUI() {
      const zoomLabel = this.toolbar.querySelector('[data-role="zoom-label"]');
      if (zoomLabel) zoomLabel.textContent = typeof this.zoom === 'number' ? this.zoom + '%' : this.zoom;
      const pageInput = this.toolbar.querySelector('[data-role="page-input"]');
      if (pageInput) pageInput.value = this.page;
      if (typeof this.zoom === 'number') this.iframe.style.transform = `rotate(${this.rotation}deg) scale(${this.getCssScale()})`;
      else this.iframe.style.transform = `rotate(${this.rotation}deg)`;
    }
    toggleFullscreen() {
      if (!this.isFullscreen) {
        if (this.container.requestFullscreen) {
          this.container.requestFullscreen().then(() => {
            this.container.classList.add('is-fullscreen');
            this.isFullscreen = true;
            this.updateFullscreenIcon();
          }).catch(() => {
            this.container.classList.toggle('is-fullscreen');
            this.isFullscreen = this.container.classList.contains('is-fullscreen');
            this.updateFullscreenIcon();
          });
        } else {
          this.container.classList.add('is-fullscreen');
          this.isFullscreen = true;
          this.updateFullscreenIcon();
        }
      } else {
        if (document.fullscreenElement) document.exitFullscreen();
        else { this.container.classList.remove('is-fullscreen'); this.isFullscreen = false; this.updateFullscreenIcon(); }
      }
    }
    updateFullscreenIcon() {
      const btn = this.toolbar.querySelector('[data-action="fullscreen"]');
      if (!btn) return;
      btn.innerHTML = (this.isFullscreen ? ICONS.fullscreenExit : ICONS.fullscreen) + '<span class="pdf-viewer-tooltip">' + (this.isFullscreen ? 'Zamknij pełny ekran' : 'Pełny ekran') + '</span>';
    }
    download() {
      const a = document.createElement('a');
      a.href = this.src; a.download = safeFilenameFromUrl(this.src); a.rel = 'noopener';
      document.body.appendChild(a); a.click(); a.remove();
      this.showStatus('Pobieranie rozpoczęte', 2000);
    }
    print() {
      try { this.iframe.contentWindow.focus(); this.iframe.contentWindow.print(); }
      catch { window.open(this.src, '_blank'); }
    }
    showStatus(msg, timeout = 0) {
      if (!this.statusEl) return;
      this.statusEl.querySelector('span').textContent = msg;
      this.statusEl.classList.add('is-visible');
      if (timeout) { clearTimeout(this._statusTimer); this._statusTimer = setTimeout(() => this.statusEl.classList.remove('is-visible'), timeout); }
    }
    showError() {
      this.frameWrap.classList.remove('pdf-viewer-frame-wrap--loading');
      this.frameWrap.innerHTML = `
        <div class="pdf-viewer-fallback">
          ${ICONS.pdf}
          <h3>Nie udało się wczytać PDF</h3>
          <p>Przeglądarka nie mogła wyświetlić dokumentu. Możesz pobrać plik i otworzyć go lokalnie.</p>
          <div class="pdf-viewer-fallback__actions">
            <a href="${this.src}" download class="pdf-viewer-btn pdf-viewer-btn--primary" style="text-decoration:none;">${ICONS.download}<span style="margin-left:.4rem;">Pobierz PDF</span></a>
            <a href="${this.src}" target="_blank" rel="noopener" class="pdf-viewer-btn pdf-viewer-btn--primary" style="background:transparent;color:var(--pdf-ink);border-color:var(--pdf-line);text-decoration:none;">${ICONS.external}<span style="margin-left:.4rem;">Otwórz w nowej karcie</span></a>
          </div>
        </div>
      `;
    }
  }

  const ModalViewer = {
    element: null, iframe: null, toolbar: null, statusEl: null, src: null, title: null, page: 1, zoom: 100, rotation: 0, isFullscreen: false,
    touchStartX: 0, lastTap: 0,

    ensureDOM() {
      if (this.element) return;
      const modal = document.createElement('div');
      modal.className = 'pdf-viewer-modal pdf-viewer-root';
      modal.id = 'pdf-viewer-modal';
      modal.setAttribute('role', 'dialog');
      modal.setAttribute('aria-modal', 'true');
      modal.setAttribute('aria-label', 'Podgląd PDF');
      modal.innerHTML = `
        <div class="pdf-viewer-modal__backdrop" data-action="close"></div>
        <div class="pdf-viewer-modal__dialog" role="document">
          <div class="pdf-viewer-modal__header">
            <div class="pdf-viewer-modal__title">
              ${ICONS.pdf}
              <div>
                <strong data-role="modal-title">Dokument PDF</strong>
                <span data-role="modal-subtitle">Podgląd</span>
              </div>
            </div>
            <div class="pdf-viewer-modal__actions">
              <button type="button" class="pdf-viewer-btn" data-action="close" aria-label="Zamknij (Esc)">${ICONS.close}<span class="pdf-viewer-tooltip">Zamknij (Esc)</span></button>
            </div>
          </div>
          <div class="pdf-viewer-toolbar" data-role="modal-toolbar"></div>
          <div class="pdf-viewer-modal__body">
            <div class="pdf-viewer-frame-wrap pdf-viewer-frame-wrap--loading" data-role="frame-wrap">
              <iframe class="pdf-viewer-frame" data-role="modal-iframe" title="Podgląd PDF" allow="fullscreen"></iframe>
              <div class="pdf-viewer-status" data-role="status" role="status" aria-live="polite">${ICONS.info}<span>Ładowanie...</span></div>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
      this.element = modal;
      this.iframe = modal.querySelector('[data-role="modal-iframe"]');
      this.toolbar = modal.querySelector('[data-role="modal-toolbar"]');
      this.frameWrap = modal.querySelector('[data-role="frame-wrap"]');
      this.statusEl = modal.querySelector('[data-role="status"]');
      this.titleEl = modal.querySelector('[data-role="modal-title"]');
      this.subtitleEl = modal.querySelector('[data-role="modal-subtitle"]');

      const tb = createToolbar('modal', true);
      this.toolbar.innerHTML = '';
      this.toolbar.append(...tb.childNodes);
      this.bindEvents();
    },

    bindEvents() {
      const self = this;
      this.element.addEventListener('click', (e) => {
        const actionBtn = e.target.closest('[data-action]');
        if (actionBtn) {
          const action = actionBtn.getAttribute('data-action');
          if (action === 'close') self.close();
          else self.handleAction(action);
          return;
        }
        if (e.target.classList.contains('pdf-viewer-modal__backdrop')) self.close();
      });

      this.toolbar.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        self.handleAction(btn.getAttribute('data-action'));
      });

      const pageInput = this.toolbar.querySelector('[data-role="page-input"]');
      if (pageInput) {
        pageInput.addEventListener('change', () => {
          let p = parseInt(pageInput.value, 10);
          if (isNaN(p) || p < 1) p = 1;
          self.goToPage(p);
        });
      }

      this.iframe.addEventListener('load', () => {
        self.frameWrap.classList.remove('pdf-viewer-frame-wrap--loading');
        self.showStatus('Załadowano', 1800);
      });

      document.addEventListener('keydown', (e) => {
        if (!self.element.classList.contains('is-open')) return;
        if (e.key === 'Escape') self.close();
        if (e.key === 'ArrowLeft') self.goToPage(self.page - 1);
        if (e.key === 'ArrowRight') self.goToPage(self.page + 1);
        if (e.key === '+' || e.key === '=') self.setZoom((typeof self.zoom === 'number' ? self.zoom : 100) + 25);
        if (e.key === '-' || e.key === '_') self.setZoom((typeof self.zoom === 'number' ? self.zoom : 100) - 25);
      });

      document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
          const dialog = self.element.querySelector('.pdf-viewer-modal__dialog');
          if (dialog) dialog.classList.remove('is-fullscreen');
          self.isFullscreen = false;
          self.updateFullscreenIcon();
        }
      });

      // Touch swipe na modal
      if (CONFIG.enableSwipe) {
        let startX = 0, startY = 0, startTime = 0;
        this.frameWrap.addEventListener('touchstart', (e) => {
          if (e.touches.length !== 1) return;
          startX = e.touches[0].clientX;
          startY = e.touches[0].clientY;
          startTime = Date.now();
        }, { passive: true });

        this.frameWrap.addEventListener('touchend', (e) => {
          if (!startX) return;
          const endX = e.changedTouches[0].clientX;
          const endY = e.changedTouches[0].clientY;
          const diffX = endX - startX;
          const diffY = endY - startY;
          const time = Date.now() - startTime;

          if (Math.abs(diffX) > 70 && Math.abs(diffX) > Math.abs(diffY) * 1.5 && time < 500) {
            if (diffX < 0) self.goToPage(self.page + 1);
            else self.goToPage(self.page - 1);
          }

          const now = Date.now();
          if (now - self.lastTap < 300) {
            if (typeof self.zoom === 'number' && self.zoom < 150) self.setZoom(150);
            else self.setZoom(100);
          }
          self.lastTap = now;
          startX = 0;
        }, { passive: true });
      }
    },

    handleAction(action) {
      switch(action) {
        case 'prev': this.goToPage(this.page - 1); break;
        case 'next': this.goToPage(this.page + 1); break;
        case 'zoom-in': this.setZoom((typeof this.zoom === 'number' ? this.zoom : 100) + 25); break;
        case 'zoom-out': this.setZoom((typeof this.zoom === 'number' ? this.zoom : 100) - 25); break;
        case 'zoom-reset': this.setZoom(100); break;
        case 'fit-width': this.setZoom('page-width'); break;
        case 'rotate': this.rotate(); break;
        case 'fullscreen': this.toggleFullscreen(); break;
        case 'download': this.download(); break;
        case 'print': this.print(); break;
        case 'close': this.close(); break;
      }
    },

    open(src, title) {
      this.ensureDOM();
      this.src = src;
      this.title = title || safeFilenameFromUrl(src);
      this.page = 1;
      this.zoom = loadPrefs().zoom || 100;
      this.rotation = 0;
      this.titleEl.textContent = this.title;
      this.subtitleEl.textContent = 'Podgląd • ' + safeFilenameFromUrl(src);
      this.element.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      // iOS safe area - zapobiegaj scroll
      document.body.style.position = 'fixed';
      document.body.style.width = '100%';
      this.load();
      this.updateUI();
    },
    close() {
      if (!this.element) return;
      this.element.classList.remove('is-open');
      document.body.style.overflow = '';
      document.body.style.position = '';
      document.body.style.width = '';
      this.iframe.src = 'about:blank';
      this.frameWrap.classList.add('pdf-viewer-frame-wrap--loading');
    },
    load() {
      const url = buildPdfSrc(this.src, this.page, this.zoom);
      this.frameWrap.classList.add('pdf-viewer-frame-wrap--loading');
      this.iframe.src = url;
    },
    goToPage(p) {
      if (p < 1) p = 1;
      this.page = p;
      this.load();
      this.updateUI();
      this.showStatus('Strona ' + p, 1200);
      if (navigator.vibrate) navigator.vibrate(20);
    },
    setZoom(z) {
      if (typeof z === 'string') this.zoom = z;
      else { z = Math.max(50, Math.min(250, z)); this.zoom = z; }
      savePrefs({ ...loadPrefs(), zoom: this.zoom });
      this.load();
      this.updateUI();
    },
    rotate() {
      this.rotation = (this.rotation + 90) % 360;
      this.iframe.style.transform = `rotate(${this.rotation}deg) scale(${typeof this.zoom === 'number' ? this.zoom/100 : 1})`;
      this.showStatus('Obrót: ' + this.rotation + '°', 1200);
    },
    toggleFullscreen() {
      const dialog = this.element.querySelector('.pdf-viewer-modal__dialog');
      if (!this.isFullscreen) {
        if (dialog.requestFullscreen) {
          dialog.requestFullscreen().then(() => {
            dialog.classList.add('is-fullscreen');
            this.isFullscreen = true;
            this.updateFullscreenIcon();
          }).catch(() => {
            dialog.classList.add('is-fullscreen');
            this.isFullscreen = true;
            this.updateFullscreenIcon();
          });
        } else { dialog.classList.add('is-fullscreen'); this.isFullscreen = true; this.updateFullscreenIcon(); }
      } else {
        if (document.fullscreenElement) document.exitFullscreen();
        dialog.classList.remove('is-fullscreen');
        this.isFullscreen = false;
        this.updateFullscreenIcon();
      }
    },
    updateFullscreenIcon() {
      const btn = this.toolbar.querySelector('[data-action="fullscreen"]');
      if (btn) btn.innerHTML = (this.isFullscreen ? ICONS.fullscreenExit : ICONS.fullscreen) + '<span class="pdf-viewer-tooltip">' + (this.isFullscreen ? 'Zamknij pełny ekran' : 'Pełny ekran') + '</span>';
    },
    updateUI() {
      const zoomLabel = this.toolbar.querySelector('[data-role="zoom-label"]');
      if (zoomLabel) zoomLabel.textContent = typeof this.zoom === 'number' ? this.zoom + '%' : this.zoom;
      const pageInput = this.toolbar.querySelector('[data-role="page-input"]');
      if (pageInput) pageInput.value = this.page;
      this.iframe.style.transform = `rotate(${this.rotation}deg) scale(${typeof this.zoom === 'number' ? this.zoom/100 : 1})`;
    },
    download() {
      const a = document.createElement('a');
      a.href = this.src; a.download = safeFilenameFromUrl(this.src);
      document.body.appendChild(a); a.click(); a.remove();
      this.showStatus('Pobieranie...', 1800);
    },
    print() {
      try { this.iframe.contentWindow.focus(); this.iframe.contentWindow.print(); }
      catch { window.open(this.src, '_blank'); }
    },
    showStatus(msg, timeout = 0) {
      if (!this.statusEl) return;
      this.statusEl.querySelector('span').textContent = msg;
      this.statusEl.classList.add('is-visible');
      if (timeout) { clearTimeout(this._timer); this._timer = setTimeout(() => this.statusEl.classList.remove('is-visible'), timeout); }
    }
  };

  const PDFViewer66600 = {
    init() {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.scan());
      } else this.scan();

      const observer = new MutationObserver(() => this.scan());
      observer.observe(document.body, { childList: true, subtree: true });

      // RWD: nasłuchuj zmiany orientacji
      window.addEventListener('orientationchange', () => {
        setTimeout(() => this.scan(), 300);
      });
    },
    scan() {
      this.enhanceLinks();
      this.enhanceInline();
    },
    enhanceLinks() {
      if (!CONFIG.enableAutoEnhance) return;
      const links = document.querySelectorAll(CONFIG.selectorLinks);
      links.forEach(link => {
        if (link.dataset.pdfEnhanced) return;
        const href = link.getAttribute('href');
        if (!href || !isPdfUrl(href)) return;
        if (link.closest('.pdf-viewer-toolbar, .pdf-viewer-modal, .pdf-viewer-inline')) return;
        link.dataset.pdfEnhanced = '1';
        link.classList.add('pdf-link-enhanced');
        link.setAttribute('data-pdf-title', link.textContent.trim() || safeFilenameFromUrl(href));
        link.addEventListener('click', (e) => {
          if (e.ctrlKey || e.metaKey || e.button === 1) return;
          e.preventDefault();
          const title = link.getAttribute('data-pdf-title') || link.textContent.trim() || safeFilenameFromUrl(href);
          const absolute = new URL(href, window.location.href).href;
          ModalViewer.open(absolute, title);
        });
      });
    },
    enhanceInline() {
      const containers = document.querySelectorAll(CONFIG.selectorInline);
      containers.forEach(el => {
        if (el.dataset.pdfViewerInitialized) return;
        const src = el.getAttribute('data-pdf-src') || el.getAttribute('data-src');
        if (!src || !isPdfUrl(src)) return;
        el.dataset.pdfViewerInitialized = '1';
        const title = el.getAttribute('data-pdf-title') || el.getAttribute('title') || safeFilenameFromUrl(src);
        new PdfViewerInstance(el, src, { title });
      });
    },
    openModal(src, title) { ModalViewer.open(src, title); },
    closeModal() { ModalViewer.close(); },
    createInline(container, src, title) {
      if (typeof container === 'string') container = document.querySelector(container);
      if (!container) return null;
      return new PdfViewerInstance(container, src, { title });
    },
    render(src, title, options = {}) {
      const div = document.createElement('div');
      div.setAttribute('data-pdf-src', src);
      if (title) div.setAttribute('data-pdf-title', title);
      if (options.className) div.className = options.className;
      return div;
    }
  };

  window.PDFViewer66600 = PDFViewer66600;
  window.PDFViewerModal = ModalViewer;
  PDFViewer66600.init();
  console.log('%c66600.PL PDF Viewer %c v2.0.0 RWD + Light/Dark - 100% lokalny', 'background:#334C60;color:#FFDBBB;padding:4px 10px;border-radius:8px;font-weight:800;', 'color:#3C4F5E;');
})();
