(() => {
    'use strict';

    const initializeSubmissionForm = (root) => {
        if (!root || root.dataset.submissionInitialized === 'true') return;
        root.dataset.submissionInitialized = 'true';

        const find = (selector) => root.querySelector(selector);
        const findAll = (selector) => Array.from(root.querySelectorAll(selector));

        const updateCounter = (field) => {
            const counter = find(`#${field.dataset.publicCounter}`);
            if (!counter) return;
            const maxLength = Number(field.getAttribute('maxlength')) || null;
            counter.textContent = maxLength ? `${field.value.length} / ${maxLength}` : `${field.value.length} znaków`;
        };

        const createImagePreview = (input, container, selectedFile) => {
            const file = selectedFile || (input.files && input.files[0]);
            if (!file || !file.type || file.type.indexOf('image/') !== 0 || !container) return;
            const reader = new FileReader();
            reader.onload = (event) => {
                container.replaceChildren();
                const image = document.createElement('img');
                image.src = event.target.result;
                image.alt = 'Podgląd wybranego zdjęcia';
                container.appendChild(image);
            };
            reader.readAsDataURL(file);
        };

        findAll('[data-public-counter]').forEach((field) => {
            updateCounter(field);
            field.addEventListener('input', () => updateCounter(field));
        });

        findAll('[data-public-preview]').forEach((input) => {
            input.addEventListener('change', () => createImagePreview(input, find(`#${input.dataset.publicPreview}`)));
        });

        findAll('[data-public-file-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', () => find(`#${trigger.dataset.publicFileTrigger}`)?.click());
        });

        const galleryContainer = find('#public-gallery-container');
        const addGalleryButton = find('#add-gallery-image');
        const isChronicleGallery = Boolean(find('[data-gallery-multi-select="true"]') && galleryContainer && addGalleryButton);
        const galleryLimit = isChronicleGallery ? 6 : 4;

        const galleryRowCount = () => galleryContainer?.querySelectorAll('.public-gallery-row').length || 0;
        const updateGalleryButtonState = () => {
            if (!addGalleryButton || !galleryContainer) return;
            const reachedLimit = galleryRowCount() >= galleryLimit;
            addGalleryButton.disabled = reachedLimit;
            addGalleryButton.textContent = reachedLimit ? `Limit ${galleryLimit} zdjęć dodatkowych` : 'Dodaj zdjęcie';
        };

        const setSingleFile = (input, file) => {
            try {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                return true;
            } catch {
                return false;
            }
        };

        const createGalleryRow = (file) => {
            if (!galleryContainer || galleryRowCount() >= galleryLimit) return false;
            const row = document.createElement('div');
            row.className = 'public-gallery-row';
            row.innerHTML = [
                '<div class="public-gallery-preview" aria-hidden="true"></div>',
                '<div class="public-gallery-fields">',
                '  <input type="file" name="gallery[]" accept="image/*" aria-label="Wybierz dodatkowe zdjęcie">',
                '  <input type="text" name="gallery_descriptions[]" maxlength="255" placeholder="Krótki opis zdjęcia (opcjonalnie)" aria-label="Krótki opis zdjęcia">',
                '</div>',
                '<button type="button" class="public-gallery-remove" aria-label="Usuń to zdjęcie">×</button>'
            ].join('');

            const fileInput = row.querySelector('input[type="file"]');
            const preview = row.querySelector('.public-gallery-preview');
            if (file && !setSingleFile(fileInput, file)) return false;
            if (file) createImagePreview(fileInput, preview, file);
            fileInput.addEventListener('change', () => createImagePreview(fileInput, preview));
            row.querySelector('.public-gallery-remove').addEventListener('click', () => {
                row.remove();
                updateGalleryButtonState();
            });
            galleryContainer.appendChild(row);
            updateGalleryButtonState();
            return true;
        };

        const addGalleryRow = () => {
            if (!galleryContainer || galleryRowCount() >= galleryLimit) return;
            if (isChronicleGallery) {
                const picker = document.createElement('input');
                picker.type = 'file';
                picker.multiple = true;
                picker.accept = 'image/*';
                picker.className = 'sr-only';
                picker.setAttribute('aria-label', 'Wybierz dodatkowe zdjęcia');
                document.body.appendChild(picker);
                picker.addEventListener('change', () => {
                    Array.from(picker.files || []).slice(0, galleryLimit - galleryRowCount()).forEach(createGalleryRow);
                    picker.remove();
                    updateGalleryButtonState();
                }, { once: true });
                picker.click();
                return;
            }
            createGalleryRow(null);
        };

        addGalleryButton?.addEventListener('click', addGalleryRow);
        updateGalleryButtonState();
    };

    window.initializeSubmissionForm = initializeSubmissionForm;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.public-ad-form').forEach((form) => initializeSubmissionForm(form));
    });
})();
