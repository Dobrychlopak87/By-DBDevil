/* Poker Polski — motyw jasny/ciemny (wspólny wybór z serwisem: klucz „66600-theme”). */
(function () {
  var KEY = '66600-theme';
  var root = document.documentElement;
  function saved() {
    try { var v = localStorage.getItem(KEY); return v === 'light' || v === 'dark' ? v : null; } catch (e) { return null; }
  }
  function system() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  function apply(theme) {
    root.setAttribute('data-theme', theme);
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'dark' ? '#2A3846' : '#FFFFFF');
    var buttons = document.querySelectorAll('.theme-toggle');
    for (var i = 0; i < buttons.length; i++) {
      var label = theme === 'dark' ? 'Włącz jasny motyw' : 'Włącz ciemny motyw';
      buttons[i].setAttribute('title', label);
      buttons[i].setAttribute('aria-label', label);
      buttons[i].setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }
  }
  apply(saved() || system());
  if (window.matchMedia) {
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    var onChange = function () { if (!saved()) apply(system()); };
    if (mq.addEventListener) mq.addEventListener('change', onChange); else if (mq.addListener) mq.addListener(onChange);
  }
  window.addEventListener('storage', function (e) { if (e.key === KEY) apply(saved() || system()); });
  window.PokerTheme = {
    toggle: function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem(KEY, next); } catch (e) { /* brak pamięci lokalnej — tylko ta karta */ }
      apply(next);
      return next;
    },
    refresh: function () { apply(root.getAttribute('data-theme') || system()); }
  };
  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('.theme-toggle') : null;
    if (btn) { e.preventDefault(); window.PokerTheme.toggle(); }
  });
  document.addEventListener('DOMContentLoaded', function () { window.PokerTheme.refresh(); });
})();
