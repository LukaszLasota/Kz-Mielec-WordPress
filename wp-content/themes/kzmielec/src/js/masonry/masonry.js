import Masonry from 'masonry-layout';
import imagesLoaded from 'imagesloaded';
import { cssVarPx as getCSSVar } from '../utils/css-vars';

(() => {
    const masonryContainer = document.querySelector(".news");

    if (!masonryContainer?.querySelector('.news__card')) return;

    let msnry = null;

    // Column thresholds come from --bp-laptop / --bp-small, the same tokens the
    // stylesheets use, so the grid switches at exactly the CSS breakpoints.
    function getColumns() {
        const width = window.innerWidth;
        if (width >= getCSSVar('--bp-laptop', 1024)) return getCSSVar('--masonry-columns-1024', 3);
        if (width >= getCSSVar('--bp-small', 600)) return getCSSVar('--masonry-columns-600', 2);
        return 1;
    }

    function getColumnWidth(columns) {
        const gap = getCSSVar('--masonry-gap', 25);
        const style = getComputedStyle(masonryContainer);
        const innerWidth = masonryContainer.clientWidth
            - parseFloat(style.paddingLeft)
            - parseFloat(style.paddingRight);
        return Math.floor((innerWidth - gap * (columns - 1)) / columns);
    }

    function setCardWidths(width) {
        const cards = masonryContainer.querySelectorAll('.news__card');
        cards.forEach((card) => {
            card.style.width = `${width}px`;
        });
    }

    function resetCardWidths() {
        const cards = masonryContainer.querySelectorAll('.news__card');
        cards.forEach((card) => {
            card.style.width = '';
        });
    }

    function initMasonry() {
        const columns = getColumns();

        if (columns > 1 && !msnry) {
            const colWidth = getColumnWidth(columns);
            const gap = getCSSVar('--masonry-gap', 25);

            setCardWidths(colWidth);

            msnry = new Masonry(masonryContainer, {
                itemSelector: '.news__card',
                columnWidth: colWidth,
                gutter: gap
            });

            imagesLoaded(masonryContainer, () => {
                msnry.layout();
            });
        } else if (columns <= 1 && msnry) {
            msnry.destroy();
            msnry = null;
            resetCardWidths();
        }
    }

    initMasonry();

    let resizeTimer;
    function handleResize() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (msnry) {
                msnry.destroy();
                msnry = null;
            }
            initMasonry();
        }, 250);
    }

    window.addEventListener('resize', handleResize);
    window.addEventListener('orientationchange', handleResize);
})();