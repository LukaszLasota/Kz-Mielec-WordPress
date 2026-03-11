import Masonry from 'masonry-layout';
import imagesLoaded from 'imagesloaded';

(function () {
    const masonryContainer = document.querySelector(".news");

    if (!masonryContainer || !masonryContainer.querySelector('.news__card')) return;

    let msnry = null;

    function getCSSVar(name, fallback) {
        const val = getComputedStyle(document.documentElement).getPropertyValue(name);
        return parseInt(val, 10) || fallback;
    }

    function getColumns() {
        const width = window.innerWidth;
        if (width >= 1024) return getCSSVar('--masonry-columns-1024', 3);
        if (width >= 600) return getCSSVar('--masonry-columns-600', 2);
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
        cards.forEach(function(card) {
            card.style.width = width + 'px';
        });
    }

    function resetCardWidths() {
        const cards = masonryContainer.querySelectorAll('.news__card');
        cards.forEach(function(card) {
            card.style.width = '';
        });
    }

    function initMasonry() {
        var columns = getColumns();

        if (columns > 1 && !msnry) {
            var colWidth = getColumnWidth(columns);
            var gap = getCSSVar('--masonry-gap', 25);

            setCardWidths(colWidth);

            msnry = new Masonry(masonryContainer, {
                itemSelector: '.news__card',
                columnWidth: colWidth,
                gutter: gap
            });

            imagesLoaded(masonryContainer, function() {
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
        resizeTimer = setTimeout(function() {
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