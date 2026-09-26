document.addEventListener('DOMContentLoaded', () => {
    const profile = document.querySelector('[data-ad-profile]');
    if (!profile) {
        return;
    }

    const mainImage = profile.querySelector('[data-ad-profile-main-image]');
    const thumbnails = Array.from(profile.querySelectorAll('[data-ad-profile-thumbnail]'));
    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            if (!mainImage) {
                return;
            }
            mainImage.src = thumbnail.dataset.adProfileImage ?? '';
            mainImage.alt = thumbnail.dataset.adProfileAlt ?? mainImage.alt;
            thumbnails.forEach((item) => {
                const isActive = item === thumbnail;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-pressed', String(isActive));
            });
            if (window.matchMedia('(max-width: 600px)').matches) {
                const hero = profile.querySelector('.ad-profile__hero');
                const headerHeight = document.querySelector('header')?.getBoundingClientRect().height ?? 0;
                const targetTop = Math.max(0, window.scrollY + (hero?.getBoundingClientRect().top ?? 0) - headerHeight - 12);
                window.scrollTo({
                    top: targetTop,
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
                });
            }
        });
    });

    const description = profile.querySelector('[data-ad-profile-description]');
    const descriptionToggle = profile.querySelector('[data-ad-profile-description-toggle]');
    if (!description || !descriptionToggle) {
        return;
    }

    descriptionToggle.addEventListener('click', () => {
        const isExpanded = description.classList.toggle('is-expanded');
        descriptionToggle.setAttribute('aria-expanded', String(isExpanded));
        descriptionToggle.textContent = isExpanded ? 'Zwiń' : 'Szczegóły';
    });
});
