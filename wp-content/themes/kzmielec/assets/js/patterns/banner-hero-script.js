/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!********************************************!*\
  !*** ./src/patterns/banner-hero/script.ts ***!
  \********************************************/

document.addEventListener('DOMContentLoaded', () => {
    const arrow = document.querySelector('.pattern-banner-hero .black-circle a');
    if (!arrow)
        return;
    arrow.addEventListener('click', (e) => {
        const target = document.querySelector(arrow.getAttribute('href') || '');
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

/******/ })()
;
//# sourceMappingURL=banner-hero-script.js.map?ab0bdffa99005a20f479