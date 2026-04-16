/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/blocks/dynamic-images/block.json":
/*!**********************************************!*\
  !*** ./src/blocks/dynamic-images/block.json ***!
  \**********************************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"custom-block-package/dynamic-images","title":"Blok z trzema obrazami","category":"custom-blocks-from-scratch","icon":"format-image","attributes":{"anchor":{"type":"string"},"imgDesktopID":{"type":"number","default":0},"imgDesktopURL":{"type":"string","default":""},"imgTabletID":{"type":"number","default":0},"imgTabletURL":{"type":"string","default":""},"imgMobileID":{"type":"number","default":0},"imgMobileURL":{"type":"string","default":""},"heading":{"type":"string","default":""},"overlayDesktopID":{"type":"number","default":0},"overlayDesktopURL":{"type":"string","default":""},"overlayTabletID":{"type":"number","default":0},"overlayTabletURL":{"type":"string","default":""},"overlayMobileID":{"type":"number","default":0},"overlayMobileURL":{"type":"string","default":""},"overlayAlt":{"type":"string","default":""}},"textdomain":"custom-block-package","description":"Blok z wyborem obrazów dla desktopu, tabletu i telefonu z ukrytym nagłówkiem H1.","supports":{"anchor":true,"align":true,"alignWide":true,"color":{"background":true,"text":false,"gradients":true},"spacing":{"padding":true,"margin":true,"blockGap":true},"dimensions":{"minHeight":true},"position":{"sticky":true}},"editorScript":"file:./index.js","viewStyle":"file:./style-index.css","editorStyle":"file:./index.css","render":"file:./render.php"}');

/***/ }),

/***/ "./src/blocks/dynamic-images/edit.js":
/*!*******************************************!*\
  !*** ./src/blocks/dynamic-images/edit.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./index.scss */ "./src/blocks/dynamic-images/index.scss");







/**
 * Helper component for a single image upload slot.
 */
const ImageSlot = ({
  label,
  imgID,
  imgURL,
  onSelect,
  onRemove,
  previewAlt
}) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUploadCheck, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUpload, {
  onSelect: onSelect,
  allowedTypes: ["image"],
  value: imgID,
  render: ({
    open
  }) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    onClick: open,
    isSecondary: true
  }, !imgID ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Wybierz obraz", "custom-block-package") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Zmień obraz", "custom-block-package")), imgURL && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      marginTop: "10px"
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: imgURL,
    style: {
      maxWidth: "100%",
      display: "block"
    },
    alt: previewAlt
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    onClick: onRemove,
    isDestructive: true,
    style: {
      marginTop: "5px"
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Usuń obraz", "custom-block-package"))))
}));
const Edit = ({
  attributes,
  setAttributes
}) => {
  const {
    imgDesktopID,
    imgDesktopURL,
    imgTabletID,
    imgTabletURL,
    imgMobileID,
    imgMobileURL,
    overlayDesktopID,
    overlayDesktopURL,
    overlayTabletID,
    overlayTabletURL,
    overlayMobileID,
    overlayMobileURL,
    overlayAlt,
    heading
  } = attributes;
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.useBlockProps)();
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Obrazy tła", "custom-block-package"),
    initialOpen: true
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ImageSlot, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Obraz Desktop", "custom-block-package"),
    imgID: imgDesktopID,
    imgURL: imgDesktopURL,
    previewAlt: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Podgląd Desktop", "custom-block-package"),
    onSelect: media => setAttributes({
      imgDesktopID: media.id,
      imgDesktopURL: media.url
    }),
    onRemove: () => setAttributes({
      imgDesktopID: 0,
      imgDesktopURL: ""
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ImageSlot, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Obraz Tablet", "custom-block-package"),
    imgID: imgTabletID,
    imgURL: imgTabletURL,
    previewAlt: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Podgląd Tablet", "custom-block-package"),
    onSelect: media => setAttributes({
      imgTabletID: media.id,
      imgTabletURL: media.url
    }),
    onRemove: () => setAttributes({
      imgTabletID: 0,
      imgTabletURL: ""
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ImageSlot, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Obraz Mobile", "custom-block-package"),
    imgID: imgMobileID,
    imgURL: imgMobileURL,
    previewAlt: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Podgląd Mobile", "custom-block-package"),
    onSelect: media => setAttributes({
      imgMobileID: media.id,
      imgMobileURL: media.url
    }),
    onRemove: () => setAttributes({
      imgMobileID: 0,
      imgMobileURL: ""
    })
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Overlay (nakładka)", "custom-block-package"),
    initialOpen: false
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "components-base-control__help"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Obraz nakładany na tło. Jeśli nie ustawisz dla danego rozmiaru — nakładka nie pojawi się na tym breakpoincie.", "custom-block-package")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ImageSlot, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Overlay Desktop", "custom-block-package"),
    imgID: overlayDesktopID,
    imgURL: overlayDesktopURL,
    previewAlt: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Podgląd overlay Desktop", "custom-block-package"),
    onSelect: media => setAttributes({
      overlayDesktopID: media.id,
      overlayDesktopURL: media.url
    }),
    onRemove: () => setAttributes({
      overlayDesktopID: 0,
      overlayDesktopURL: ""
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ImageSlot, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Overlay Tablet", "custom-block-package"),
    imgID: overlayTabletID,
    imgURL: overlayTabletURL,
    previewAlt: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Podgląd overlay Tablet", "custom-block-package"),
    onSelect: media => setAttributes({
      overlayTabletID: media.id,
      overlayTabletURL: media.url
    }),
    onRemove: () => setAttributes({
      overlayTabletID: 0,
      overlayTabletURL: ""
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ImageSlot, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Overlay Mobile", "custom-block-package"),
    imgID: overlayMobileID,
    imgURL: overlayMobileURL,
    previewAlt: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Podgląd overlay Mobile", "custom-block-package"),
    onSelect: media => setAttributes({
      overlayMobileID: media.id,
      overlayMobileURL: media.url
    }),
    onRemove: () => setAttributes({
      overlayMobileID: 0,
      overlayMobileURL: ""
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Tekst alternatywny overlay", "custom-block-package"),
    value: overlayAlt || "",
    onChange: value => setAttributes({
      overlayAlt: value
    }),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Opis nakładki dla czytników ekranu. Zostaw puste jeśli nakładka jest czysto dekoracyjna.", "custom-block-package")
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Nagłówek H1", "custom-block-package"),
    initialOpen: false
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Tekst nagłówka (ukryty wizualnie)", "custom-block-package"),
    value: heading || "",
    onChange: value => setAttributes({
      heading: value
    }),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Nagłówek H1 widoczny tylko dla wyszukiwarek i czytników ekranu.", "custom-block-package")
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...blockProps
  }, heading && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
    className: "visually-hidden"
  }, heading), !imgDesktopURL && !imgTabletURL && !imgMobileURL && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Dodaj zdjęcia dla różnych rozdzielczości.", "custom-block-package")), (imgDesktopURL || imgTabletURL || imgMobileURL) && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dynamic-images__preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("picture", null, imgMobileURL && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("source", {
    srcSet: imgMobileURL,
    media: "(max-width: 480px)"
  }), imgTabletURL && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("source", {
    srcSet: imgTabletURL,
    media: "(max-width: 768px)"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: imgDesktopURL || imgTabletURL || imgMobileURL,
    style: {
      maxWidth: "100%",
      display: "block",
      height: "auto"
    },
    alt: ""
  })), (overlayDesktopURL || overlayTabletURL || overlayMobileURL) && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "dynamic-images__overlay-preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("picture", null, overlayMobileURL && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("source", {
    srcSet: overlayMobileURL,
    media: "(max-width: 480px)"
  }), overlayTabletURL && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("source", {
    srcSet: overlayTabletURL,
    media: "(max-width: 768px)"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: overlayDesktopURL || overlayTabletURL || overlayMobileURL,
    style: {
      maxWidth: "100%",
      display: "block",
      height: "auto"
    },
    alt: ""
  }))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Edit);

/***/ }),

/***/ "./src/blocks/dynamic-images/index.js":
/*!********************************************!*\
  !*** ./src/blocks/dynamic-images/index.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block.json */ "./src/blocks/dynamic-images/block.json");
/* harmony import */ var _edit_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./edit.js */ "./src/blocks/dynamic-images/edit.js");
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./index.scss */ "./src/blocks/dynamic-images/index.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./style.scss */ "./src/blocks/dynamic-images/style.scss");






(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_2__.name, {
  ..._block_json__WEBPACK_IMPORTED_MODULE_2__,
  edit: _edit_js__WEBPACK_IMPORTED_MODULE_3__["default"],
  save: () => null
});

/***/ }),

/***/ "./src/blocks/dynamic-images/index.scss":
/*!**********************************************!*\
  !*** ./src/blocks/dynamic-images/index.scss ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/blocks/dynamic-images/style.scss":
/*!**********************************************!*\
  !*** ./src/blocks/dynamic-images/style.scss ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"blocks/dynamic-images/index": 0,
/******/ 			"blocks/dynamic-images/style-index": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunkcustom_block_package"] = globalThis["webpackChunkcustom_block_package"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["blocks/dynamic-images/style-index"], () => (__webpack_require__("./src/blocks/dynamic-images/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map