"use strict";
(self["webpackChunkchurch"] = self["webpackChunkchurch"] || []).push([["src_js_masonry_masonry_js"],{

/***/ "./src/js/masonry/masonry.js":
/*!***********************************!*\
  !*** ./src/js/masonry/masonry.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var masonry_layout__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! masonry-layout */ "./node_modules/masonry-layout/masonry.js");
/* harmony import */ var masonry_layout__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(masonry_layout__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var imagesloaded__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! imagesloaded */ "./node_modules/imagesloaded/imagesloaded.js");
/* harmony import */ var imagesloaded__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(imagesloaded__WEBPACK_IMPORTED_MODULE_1__);


(function () {
  var masonryContainer = document.querySelector(".news");
  if (!masonryContainer || !masonryContainer.querySelector('.news__card')) return;
  var msnry = null;
  function getCSSVar(name, fallback) {
    var val = getComputedStyle(document.documentElement).getPropertyValue(name);
    return parseInt(val, 10) || fallback;
  }
  function getColumns() {
    var width = window.innerWidth;
    if (width >= 1024) return getCSSVar('--masonry-columns-1024', 3);
    if (width >= 600) return getCSSVar('--masonry-columns-600', 2);
    return 1;
  }
  function getColumnWidth(columns) {
    var gap = getCSSVar('--masonry-gap', 25);
    var style = getComputedStyle(masonryContainer);
    var innerWidth = masonryContainer.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
    return Math.floor((innerWidth - gap * (columns - 1)) / columns);
  }
  function setCardWidths(width) {
    var cards = masonryContainer.querySelectorAll('.news__card');
    cards.forEach(function (card) {
      card.style.width = width + 'px';
    });
  }
  function resetCardWidths() {
    var cards = masonryContainer.querySelectorAll('.news__card');
    cards.forEach(function (card) {
      card.style.width = '';
    });
  }
  function initMasonry() {
    var columns = getColumns();
    if (columns > 1 && !msnry) {
      var colWidth = getColumnWidth(columns);
      var gap = getCSSVar('--masonry-gap', 25);
      setCardWidths(colWidth);
      msnry = new (masonry_layout__WEBPACK_IMPORTED_MODULE_0___default())(masonryContainer, {
        itemSelector: '.news__card',
        columnWidth: colWidth,
        gutter: gap
      });
      imagesloaded__WEBPACK_IMPORTED_MODULE_1___default()(masonryContainer, function () {
        msnry.layout();
      });
    } else if (columns <= 1 && msnry) {
      msnry.destroy();
      msnry = null;
      resetCardWidths();
    }
  }
  initMasonry();
  var resizeTimer;
  function handleResize() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
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

/***/ })

}]);
//# sourceMappingURL=src_js_masonry_masonry_js.js.map?5e4e30a4da71cc95e5e2