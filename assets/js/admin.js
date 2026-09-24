/* ============================================
 * 66600.PL — Panel administracyjny
 * Wspólne usprawnienia ergonomii formularzy, tabel i grafik.
 * ============================================ */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    function isImageFile(file) {
        return file && typeof file.type === 'string' && file.type.indexOf('image/') === 0;
    }

    function updateCounter(field) {
        var counterId = field.dataset.counter;
        var counter = counterId ? document.getElementById(counterId) : null;
        if (!counter) return;

        var maxLength = Number(field.getAttribute('maxlength')) || null;
        var currentLength = field.value.length;
        counter.textContent = maxLength ? currentLength + ' / ' + maxLength : currentLength + ' znaków';
        counter.classList.toggle('is-near-limit', maxLength && currentLength >= maxLength * 0.9);
        counter.classList.toggle('is-at-limit', maxLength && currentLength >= maxLength);
    }

    function createPreview(input, preview) {
        var file = input.files && input.files[0];
        if (!isImageFile(file) || !preview) return;

        var reader = new FileReader();
        reader.onload = function (event) {
            preview.innerHTML = '';
            var item = document.createElement('div');
            item.className = 'file-preview-item file-preview-new';
            var image = document.createElement('img');
            image.src = event.target.result;
            image.alt = 'Podgląd nowego zdjęcia';
            item.appendChild(image);
            preview.appendChild(item);
        };
        reader.readAsDataURL(file);
    }

    // Strefy uploadu: kliknięcie, obsługa klawiatury i przeciąganie plików.
    document.querySelectorAll('.file-control').forEach(function (zone) {
        var input = zone.querySelector('input[type="file"]');
        if (!input) return;

        zone.setAttribute('tabindex', '0');
        zone.addEventListener('click', function (event) {
            if (event.target !== input) input.click();
        });
        zone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input.click();
            }
        });
        input.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (event) {
                event.preventDefault();
                zone.classList.add('file-control-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (event) {
                event.preventDefault();
                zone.classList.remove('file-control-dragover');
            });
        });
        zone.addEventListener('drop', function (event) {
            if (!event.dataTransfer || !event.dataTransfer.files.length) return;
            input.files = event.dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    // Podgląd głównego zdjęcia.
    document.querySelectorAll('.file-control input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var previewId = input.dataset.preview || (input.id ? input.id + '-preview' : null);
            createPreview(input, previewId ? document.getElementById(previewId) : null);
        });
    });

    // Podgląd miniatur dla nowych zdjęć w galerii.
    document.querySelectorAll('#new-images-container, #gallery-container').forEach(function (scope) {
        scope.addEventListener('change', function (event) {
            var input = event.target;
            if (!input.classList || !input.classList.contains('gallery-file-input')) return;

            var file = input.files && input.files[0];
            var row = input.closest('.new-image-row');
            var thumb = row ? row.querySelector('.gallery-file-thumb') : null;
            if (!isImageFile(file) || !thumb) return;

            var reader = new FileReader();
            reader.onload = function (readerEvent) {
                thumb.innerHTML = '';
                var image = document.createElement('img');
                image.src = readerEvent.target.result;
                image.alt = 'Podgląd zdjęcia galerii';
                thumb.appendChild(image);
                thumb.classList.add('gallery-file-thumb-filled');
            };
            reader.readAsDataURL(file);
        });
    });

    // Liczniki znaków dla pól redakcyjnych i SEO.
    document.querySelectorAll('[data-counter]').forEach(function (field) {
        updateCounter(field);
        field.addEventListener('input', function () {
            updateCounter(field);
        });
    });

    // Akcje zbiorcze: przycisk działa dopiero po wskazaniu treści.
    document.querySelectorAll('.bulk-form').forEach(function (form) {
        var checkboxes = form.querySelectorAll('input[name="selected_ids[]"]');
        var selectAll = form.querySelector('#select-all');
        var submitButton = form.querySelector('[data-bulk-submit]');
        var summary = form.querySelector('[data-selection-summary]');

        function refreshSelectionState() {
            var selected = form.querySelectorAll('input[name="selected_ids[]"]:checked').length;
            if (submitButton) submitButton.disabled = selected === 0;
            if (summary) summary.textContent = selected ? 'Wybrano: ' + selected : 'Nie wybrano pozycji';
            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
                selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
            }
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', refreshSelectionState);
        });
        if (selectAll) selectAll.addEventListener('change', refreshSelectionState);
        form.addEventListener('submit', function (event) {
            if (!form.querySelector('input[name="selected_ids[]"]:checked')) {
                event.preventDefault();
                refreshSelectionState();
            }
        });
        refreshSelectionState();
    });

    // Ostrzeżenie przy opuszczaniu niezapisanego formularza oraz skrót Ctrl/Cmd+S.
    document.querySelectorAll('.admin-editor-form').forEach(function (form) {
        var changed = false;
        var submitted = false;

        form.addEventListener('input', function () { changed = true; });
        form.addEventListener('change', function () { changed = true; });
        form.addEventListener('submit', function () { submitted = true; });

        window.addEventListener('beforeunload', function (event) {
            if (!changed || submitted) return;
            event.preventDefault();
            event.returnValue = '';
        });

        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            }
        });
    });

    // Jednolita obsługa dodatkowych potwierdzeń przy działaniach nieodwracalnych.
    document.querySelectorAll('[data-confirm-message]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (!window.confirm(element.dataset.confirmMessage)) event.preventDefault();
        });
    });
});
