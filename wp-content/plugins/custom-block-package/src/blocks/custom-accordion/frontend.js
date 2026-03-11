document.addEventListener('DOMContentLoaded', () => {
    const accordions = document.querySelectorAll('.accordion__item');

    accordions.forEach((accordion) => {
        const titleEl = accordion.querySelector('.accordion__title');
        const contentEl = accordion.querySelector('.accordion__content');

        const open = () => {
            accordion.classList.add('accordion__item--active');
            titleEl.setAttribute('aria-expanded', 'true');
            contentEl.hidden = false;
            // Force reflow so the browser registers max-height: 0 before transitioning.
            contentEl.offsetHeight; // eslint-disable-line no-unused-expressions
            contentEl.style.maxHeight = contentEl.scrollHeight + 'px';
            // After transition, allow content to grow freely (fixes footer overlap).
            contentEl.addEventListener('transitionend', () => {
                contentEl.style.maxHeight = 'none';
            }, { once: true });
        };

        const close = () => {
            accordion.classList.remove('accordion__item--active');
            titleEl.setAttribute('aria-expanded', 'false');
            // Set explicit height first so the transition has a start value.
            contentEl.style.maxHeight = contentEl.scrollHeight + 'px';
            // Force reflow so the browser registers the explicit height.
            contentEl.offsetHeight; // eslint-disable-line no-unused-expressions
            contentEl.style.maxHeight = '0';
            // Hide after transition ends to restore the hidden attribute.
            contentEl.addEventListener('transitionend', () => {
                contentEl.hidden = true;
                contentEl.style.maxHeight = null;
            }, { once: true });
        };

        const toggle = () => {
            accordion.classList.contains('accordion__item--active') ? close() : open();
        };

        titleEl.addEventListener('click', toggle);

        titleEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggle();
            }
        });
    });
});