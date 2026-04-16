/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/blocks/custom-svg/block.json":
/*!******************************************!*\
  !*** ./src/blocks/custom-svg/block.json ***!
  \******************************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"custom-block-package/custom-svg","title":"Custom SVG Block","category":"media","icon":"format-image","description":"Blok do wstawiania plików SVG inline","supports":{"anchor":true,"align":["left","center","right","wide","full"],"spacing":{"margin":true,"padding":true}},"textdomain":"custom-block-package","editorScript":"file:./index.js","editorStyle":"file:./index.css","attributes":{"svgId":{"type":"number","default":0},"svgUrl":{"type":"string","default":""},"svgContent":{"type":"string","default":""},"svgWidth":{"type":"string","default":""},"svgHeight":{"type":"string","default":""},"svgFill":{"type":"string","default":""},"svgStroke":{"type":"string","default":""},"preserveAspectRatio":{"type":"boolean","default":true},"applyColorToAllElements":{"type":"boolean","default":false},"wrapWithLink":{"type":"boolean","default":false},"linkUrl":{"type":"string","default":""},"linkTarget":{"type":"string","default":"_self"},"linkRel":{"type":"string","default":""}},"render":"file:./render.php"}');

/***/ }),

/***/ "./src/blocks/custom-svg/edit.js":
/*!***************************************!*\
  !*** ./src/blocks/custom-svg/edit.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
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







/**
 * Removes XML declaration from SVG content
 * 
 * @param {string} svgContent Original SVG content
 * @return {string} SVG content without XML declaration
 */
const removeXmlDeclaration = svgContent => {
  if (!svgContent) return '';

  // Remove XML declaration like <?xml version="1.0" encoding="UTF-8" standalone="no"?>
  return svgContent.replace(/<\?xml[^>]*\?>/i, '');
};

/**
 * Modifies SVG by adding specified attributes
 * 
 * This function takes an SVG string and applies various transformations:
 * - Adds CSS classes directly to the SVG tag
 * - Applies width and height attributes
 * - Applies fill and stroke colors
 * - Adds unique ID for styling all elements if needed
 * - Preserves existing attributes and classes in the SVG
 * 
 * @param {string} svgContent SVG content as string
 * @param {string} width Width value with unit (px, %, etc.)
 * @param {string} height Height value with unit (px, %, etc.)
 * @param {string} fill Fill color (hex, rgba, etc.)
 * @param {string} stroke Stroke color (hex, rgba, etc.)
 * @param {boolean} applyColorToAll Whether to apply colors to all elements
 * @param {string} cssClasses CSS classes to add to the SVG element
 * @return {string} Modified SVG content
 */
const modifySvg = (svgContent, width, height, fill, stroke, applyColorToAll, cssClasses) => {
  if (!svgContent) {
    return '';
  }

  // Remove XML declaration first
  let modifiedSvg = removeXmlDeclaration(svgContent);

  // Find the SVG tag using regular expression
  const svgTagRegex = /<svg[^>]*>/;
  const match = modifiedSvg.match(svgTagRegex);
  if (!match) {
    return modifiedSvg;
  }
  const originalSvgTag = match[0];
  let newSvgTag = originalSvgTag;

  // Generate unique ID for SVG if needed for styling
  const uniqueId = 'svg-editor-' + Math.random().toString(36).substr(2, 9);

  // Ensure we have a consistent ID to use in CSS selector
  let targetId = uniqueId;
  if (applyColorToAll && (fill || stroke)) {
    if (/\bid\s*=\s*["']([^"']+)["']/i.test(newSvgTag)) {
      // Use existing ID if found
      const idMatch = newSvgTag.match(/\bid\s*=\s*["']([^"']+)["']/i);
      targetId = idMatch[1];
    } else {
      // Add new ID if none exists
      newSvgTag = newSvgTag.replace('<svg', `<svg id="${targetId}"`);
    }
  }

  // Add CSS classes from editor directly to SVG
  if (cssClasses && cssClasses.length > 0) {
    if (/\bclass\s*=\s*["'][^"']*["']/i.test(newSvgTag)) {
      // If SVG already has a class attribute, add our classes
      newSvgTag = newSvgTag.replace(/\bclass\s*=\s*["']([^"']*)["']/i, (match, existingClasses) => {
        return `class="${existingClasses} ${cssClasses}"`;
      });
    } else {
      // Otherwise add a new class attribute
      newSvgTag = newSvgTag.replace('<svg', `<svg class="${cssClasses}"`);
    }
  }

  // Add dimensions as attributes, if specified
  if (width) {
    if (/\bwidth\s*=\s*["'][^"']*["']/i.test(newSvgTag)) {
      newSvgTag = newSvgTag.replace(/\bwidth\s*=\s*["'][^"']*["']/i, `width="${width}"`);
    } else {
      newSvgTag = newSvgTag.replace('<svg', `<svg width="${width}"`);
    }
  }
  if (height) {
    if (/\bheight\s*=\s*["'][^"']*["']/i.test(newSvgTag)) {
      newSvgTag = newSvgTag.replace(/\bheight\s*=\s*["'][^"']*["']/i, `height="${height}"`);
    } else {
      newSvgTag = newSvgTag.replace('<svg', `<svg height="${height}"`);
    }
  }

  // Add colors as attributes, only if we're not applying to all elements
  if (!applyColorToAll) {
    if (fill) {
      if (/\bfill\s*=\s*["'][^"']*["']/i.test(newSvgTag)) {
        newSvgTag = newSvgTag.replace(/\bfill\s*=\s*["'][^"']*["']/i, `fill="${fill}"`);
      } else {
        newSvgTag = newSvgTag.replace('<svg', `<svg fill="${fill}"`);
      }
    }
    if (stroke) {
      if (/\bstroke\s*=\s*["'][^"']*["']/i.test(newSvgTag)) {
        newSvgTag = newSvgTag.replace(/\bstroke\s*=\s*["'][^"']*["']/i, `stroke="${stroke}"`);
      } else {
        newSvgTag = newSvgTag.replace('<svg', `<svg stroke="${stroke}"`);
      }
    }
  }

  // Replace the original SVG tag with the new one
  modifiedSvg = modifiedSvg.replace(originalSvgTag, newSvgTag);

  // If we need to apply colors to all elements, add CSS style
  if (applyColorToAll && (fill || stroke)) {
    const styleTag = `<style>
            #${targetId} * {
                ${fill ? `fill: ${fill} !important;` : ''}
                ${stroke ? `stroke: ${stroke} !important;` : ''}
            }
        </style>`;

    // Add style before closing SVG tag
    modifiedSvg = modifiedSvg.replace('</svg>', `${styleTag}</svg>`);
  }
  return modifiedSvg;
};

/**
 * Edit component with media library support
 * 
 * This component handles:
 * - Selecting SVG from media library
 * - Loading SVG from URL
 * - Pasting SVG code directly
 * - Editing SVG parameters (dimensions, colors)
 * - Live preview of modified SVG
 * - Preserving aspect ratio
 * - Adding editor CSS classes directly to SVG
 * - Wrapping SVG with link
 *
 * @param {Object} props Component properties
 * @return {WPElement} Element to render
 */
function Edit(props) {
  // Safe props destructuring
  const attributes = props?.attributes || {};
  const setAttributes = props?.setAttributes || (() => {});

  // Safe attributes destructuring
  const svgId = attributes.svgId || 0;
  const svgUrl = attributes.svgUrl || '';
  const svgContent = attributes.svgContent || '';
  const svgWidth = attributes.svgWidth || '';
  const svgHeight = attributes.svgHeight || '';
  const svgFill = attributes.svgFill || '';
  const svgStroke = attributes.svgStroke || '';
  const preserveAspectRatio = attributes.preserveAspectRatio !== false; // default true
  const applyColorToAllElements = attributes.applyColorToAllElements || false;
  const wrapWithLink = attributes.wrapWithLink || false;
  const linkUrl = attributes.linkUrl || '';
  const linkTarget = attributes.linkTarget || '_self';
  const linkRel = attributes.linkRel || '';

  // Component states
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)('');
  const [pastedSvgCode, setPastedSvgCode] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)('');
  const [modifiedSvgContent, setModifiedSvgContent] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)('');
  const [copySuccess, setCopySuccess] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(false);
  const [isLinkPickerOpen, setIsLinkPickerOpen] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(false);
  const linkPickerRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useRef)(null);

  // Use BlockProps and extract CSS classes
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.useBlockProps)();
  const blockCssClasses = blockProps.className || '';

  // Update modifiedSvgContent when svgContent or parameters change
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    if (svgContent) {
      const modified = modifySvg(svgContent, svgWidth, svgHeight, svgFill, svgStroke, applyColorToAllElements, blockCssClasses // Pass CSS classes from editor
      );
      setModifiedSvgContent(modified);
    } else {
      setModifiedSvgContent('');
    }
  }, [svgContent, svgWidth, svgHeight, svgFill, svgStroke, applyColorToAllElements, blockCssClasses]);

  // Reset copy success state after 2 seconds
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    if (copySuccess) {
      const timer = setTimeout(() => {
        setCopySuccess(false);
      }, 2000);
      return () => clearTimeout(timer);
    }
  }, [copySuccess]);

  /**
   * Validates SVG code
   * Checks if code contains opening and closing SVG tags
   * 
   * @param {string} code SVG code to validate
   * @return {boolean} Whether code is valid SVG
   */
  const validateSvgCode = code => {
    // Check if code contains SVG tag
    if (!code || !code.includes('<svg')) {
      return false;
    }

    // Check if closing SVG tag exists
    if (!code.includes('</svg>')) {
      return false;
    }
    return true;
  };

  /**
   * Applies pasted SVG code
   * Validates and sets SVG content from pasted code
   * Then clears the input field
   */
  const applyPastedSvgCode = () => {
    if (!pastedSvgCode) {
      setError('Proszę wkleić kod SVG.');
      return;
    }
    if (!validateSvgCode(pastedSvgCode)) {
      setError('Wklejony tekst nie wygląda na poprawny kod SVG.');
      return;
    }
    setError('');

    // Remove XML declaration before saving
    const cleanedSvg = removeXmlDeclaration(pastedSvgCode);
    setAttributes({
      svgContent: cleanedSvg
    });

    // Clear the input field after applying
    setPastedSvgCode('');
  };

  /**
   * Copies SVG code to clipboard
   * Uses Clipboard API
   */
  const copySvgToClipboard = () => {
    if (modifiedSvgContent) {
      navigator.clipboard.writeText(modifiedSvgContent).then(() => {
        setCopySuccess(true);
      }).catch(err => {
        console.error('Nie udało się skopiować tekstu: ', err);
      });
    }
  };

  /**
   * Fetches SVG from media library
   * Gets SVG content from selected media item
   * 
   * @param {Object} media Selected media object
   */
  const fetchSvgFromMediaLibrary = media => {
    if (!media || !media.url) {
      return;
    }
    setIsLoading(true);
    setError('');
    try {
      // Check if it's SVG
      if (media.mime !== 'image/svg+xml') {
        throw new Error('Wybrany plik nie jest plikiem SVG');
      }

      // Fetch SVG content from URL
      fetch(media.url).then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.text();
      }).then(svgText => {
        // Check if it's valid SVG
        if (!svgText.includes('<svg')) {
          throw new Error('Pobrany plik nie zawiera poprawnego kodu SVG');
        }

        // Remove XML declaration before saving
        const cleanedSvg = removeXmlDeclaration(svgText);

        // Set attributes - without setting svgUrl in text field
        setAttributes({
          svgId: media.id,
          svgContent: cleanedSvg
          // We don't set svgUrl to avoid overwriting the text field
        });
        setIsLoading(false);
      }).catch(err => {
        console.error('Błąd podczas pobierania zawartości SVG:', err);
        setError('Nie udało się pobrać zawartości SVG: ' + err.message);
        setIsLoading(false);
      });
    } catch (err) {
      console.error('Błąd podczas przetwarzania pliku z biblioteki mediów:', err);
      setError('Błąd: ' + err.message);
      setIsLoading(false);
    }
  };

  /**
   * Fetches SVG from URL
   * Gets SVG content from provided URL
   * 
   * @param {string} url URL to fetch SVG from
   */
  const fetchSvgFromUrl = url => {
    if (!url) return;
    setIsLoading(true);
    setError('');
    fetch(url).then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.text();
    }).then(svgText => {
      // Check if it's valid SVG
      if (!svgText.includes('<svg')) {
        throw new Error('Podany URL nie zawiera poprawnego kodu SVG');
      }

      // Remove XML declaration before saving
      const cleanedSvg = removeXmlDeclaration(svgText);

      // Set attributes
      setAttributes({
        svgContent: cleanedSvg,
        svgUrl: url
      });
      setIsLoading(false);
    }).catch(err => {
      console.error('Błąd podczas pobierania SVG z URL:', err);
      setError('Nie udało się pobrać SVG z URL: ' + err.message);
      setIsLoading(false);
    });
  };

  /**
   * Updates height while maintaining aspect ratio
   * Calculates proportional height based on width
   * 
   * @param {string} width New width value with unit
   */
  const updateHeight = width => {
    if (preserveAspectRatio && svgContent) {
      // Try to extract viewBox or original dimensions from SVG
      const viewBoxMatch = svgContent.match(/viewBox=["']([^"']*)["']/);
      const widthMatch = svgContent.match(/width=["']([^"']*)["']/);
      const heightMatch = svgContent.match(/height=["']([^"']*)["']/);

      // If we have viewBox, try to maintain proportions based on it
      if (viewBoxMatch && viewBoxMatch[1]) {
        const viewBox = viewBoxMatch[1].split(/\s+/);
        if (viewBox.length === 4) {
          const vbWidth = parseFloat(viewBox[2]);
          const vbHeight = parseFloat(viewBox[3]);
          if (vbWidth && vbHeight) {
            // Calculate proportionally new height
            const numWidth = parseFloat(width);
            if (!isNaN(numWidth)) {
              const newHeight = (numWidth * vbHeight / vbWidth).toFixed(2);
              setAttributes({
                svgHeight: newHeight + (width.match(/[a-z%]+$/i) || [''])
              });
            }
          }
        }
      }
      // If we don't have viewBox but have original dimensions
      else if (widthMatch && widthMatch[1] && heightMatch && heightMatch[1]) {
        const origWidth = parseFloat(widthMatch[1]);
        const origHeight = parseFloat(heightMatch[1]);
        if (origWidth && origHeight) {
          // Calculate proportionally new height
          const numWidth = parseFloat(width);
          if (!isNaN(numWidth)) {
            const newHeight = (numWidth * origHeight / origWidth).toFixed(2);
            setAttributes({
              svgHeight: newHeight + (width.match(/[a-z%]+$/i) || [''])
            });
          }
        }
      }
    }
  };

  /**
   * Updates width while maintaining aspect ratio
   * Calculates proportional width based on height
   * 
   * @param {string} height New height value with unit
   */
  const updateWidth = height => {
    if (preserveAspectRatio && svgContent) {
      // Try to extract viewBox or original dimensions from SVG
      const viewBoxMatch = svgContent.match(/viewBox=["']([^"']*)["']/);
      const widthMatch = svgContent.match(/width=["']([^"']*)["']/);
      const heightMatch = svgContent.match(/height=["']([^"']*)["']/);

      // If we have viewBox, try to maintain proportions based on it
      if (viewBoxMatch && viewBoxMatch[1]) {
        const viewBox = viewBoxMatch[1].split(/\s+/);
        if (viewBox.length === 4) {
          const vbWidth = parseFloat(viewBox[2]);
          const vbHeight = parseFloat(viewBox[3]);
          if (vbWidth && vbHeight) {
            // Calculate proportionally new width
            const numHeight = parseFloat(height);
            if (!isNaN(numHeight)) {
              const newWidth = (numHeight * vbWidth / vbHeight).toFixed(2);
              setAttributes({
                svgWidth: newWidth + (height.match(/[a-z%]+$/i) || [''])
              });
            }
          }
        }
      }
      // If we don't have viewBox but have original dimensions
      else if (widthMatch && widthMatch[1] && heightMatch && heightMatch[1]) {
        const origWidth = parseFloat(widthMatch[1]);
        const origHeight = parseFloat(heightMatch[1]);
        if (origWidth && origHeight) {
          // Calculate proportionally new width
          const numHeight = parseFloat(height);
          if (!isNaN(numHeight)) {
            const newWidth = (numHeight * origWidth / origHeight).toFixed(2);
            setAttributes({
              svgWidth: newWidth + (height.match(/[a-z%]+$/i) || [''])
            });
          }
        }
      }
    }
  };

  // Render the component
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Wybór SVG', 'wlc-custom-block'),
    initialOpen: true
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUploadCheck, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.MediaUpload, {
    onSelect: media => {
      if (media && media.id) {
        console.log('Wybrano media:', media);
        fetchSvgFromMediaLibrary(media);
      }
    },
    allowedTypes: ['image/svg+xml'],
    value: svgId || 0,
    render: ({
      open
    }) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
      onClick: open,
      variant: "primary"
    }, svgId ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Zmień SVG', 'wlc-custom-block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Wybierz SVG z biblioteki mediów', 'wlc-custom-block'))
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Lub podaj link do pliku SVG', 'wlc-custom-block'),
    value: svgUrl || '',
    onChange: url => setAttributes({
      svgUrl: url
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    variant: "primary",
    disabled: !svgUrl || isLoading,
    onClick: () => {
      if (svgUrl) {
        fetchSvgFromUrl(svgUrl);
      }
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Pobierz SVG z linku', 'wlc-custom-block')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextareaControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Lub wklej kod SVG bezpośrednio', 'wlc-custom-block'),
    value: pastedSvgCode,
    onChange: code => setPastedSvgCode(code),
    rows: 8,
    placeholder: "<svg>...</svg>"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    variant: "primary",
    disabled: !pastedSvgCode || isLoading,
    onClick: applyPastedSvgCode
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Zastosuj wklejony kod SVG', 'wlc-custom-block'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Parametry SVG', 'wlc-custom-block'),
    initialOpen: true
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Zachowaj proporcje', 'wlc-custom-block'),
    checked: preserveAspectRatio,
    onChange: value => setAttributes({
      preserveAspectRatio: value
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalUnitControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Szerokość', 'wlc-custom-block'),
    value: svgWidth,
    onChange: value => {
      setAttributes({
        svgWidth: value
      });
      if (preserveAspectRatio) {
        updateHeight(value);
      }
    },
    units: [{
      value: 'px',
      label: 'px'
    }, {
      value: '%',
      label: '%'
    }, {
      value: 'em',
      label: 'em'
    }, {
      value: 'rem',
      label: 'rem'
    }]
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalUnitControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Wysokość', 'wlc-custom-block'),
    value: svgHeight,
    onChange: value => {
      setAttributes({
        svgHeight: value
      });
      if (preserveAspectRatio) {
        updateWidth(value);
      }
    },
    units: [{
      value: 'px',
      label: 'px'
    }, {
      value: '%',
      label: '%'
    }, {
      value: 'em',
      label: 'em'
    }, {
      value: 'rem',
      label: 'rem'
    }]
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Zastosuj kolory do wszystkich elementów', 'wlc-custom-block'),
    checked: applyColorToAllElements,
    onChange: value => setAttributes({
      applyColorToAllElements: value
    }),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Jeśli włączone, kolory zostaną zastosowane do wszystkich elementów SVG, nadpisując ich kolory.', 'wlc-custom-block')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-block-color-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Kolor wypełnienia (fill)', 'wlc-custom-block')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ColorPicker, {
    color: svgFill,
    onChange: value => setAttributes({
      svgFill: value
    }),
    enableAlpha: true
  }), svgFill && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    variant: "secondary",
    isSmall: true,
    onClick: () => setAttributes({
      svgFill: ''
    })
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Usuń kolor', 'wlc-custom-block'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-block-color-control"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Kolor obrysu (stroke)', 'wlc-custom-block')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ColorPicker, {
    color: svgStroke,
    onChange: value => setAttributes({
      svgStroke: value
    }),
    enableAlpha: true
  }), svgStroke && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    variant: "secondary",
    isSmall: true,
    onClick: () => setAttributes({
      svgStroke: ''
    })
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Usuń kolor', 'wlc-custom-block')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Ustawienia linku', 'wlc-custom-block'),
    initialOpen: false
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Opakuj SVG linkiem', 'wlc-custom-block'),
    checked: wrapWithLink,
    onChange: value => setAttributes({
      wrapWithLink: value
    }),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Jeśli włączone, SVG będzie opakowane linkiem.', 'wlc-custom-block')
  }), wrapWithLink && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-link-settings",
    style: {
      position: 'relative'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Adres URL', 'wlc-custom-block'),
    value: linkUrl,
    onChange: value => setAttributes({
      linkUrl: value
    }),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Wpisz URL lub wyszukaj stronę', 'wlc-custom-block')
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ref: linkPickerRef
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    variant: "secondary",
    onClick: () => setIsLinkPickerOpen(!isLinkPickerOpen),
    style: {
      marginBottom: '10px'
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Wybierz z witryny', 'wlc-custom-block')), isLinkPickerOpen && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.URLPopover, {
    position: "bottom center",
    onClose: () => setIsLinkPickerOpen(false),
    renderSettings: () => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Otwórz w nowej karcie', 'wlc-custom-block'),
      checked: linkTarget === '_blank',
      onChange: value => setAttributes({
        linkTarget: value ? '_blank' : '_self'
      })
    })
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      padding: '16px',
      width: '350px',
      maxWidth: '100%'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.__experimentalLinkControl, {
    value: {
      url: linkUrl,
      opensInNewTab: linkTarget === '_blank'
    },
    onChange: linkObject => {
      setAttributes({
        linkUrl: linkObject.url || '',
        linkTarget: linkObject.opensInNewTab ? '_blank' : '_self',
        linkRel: linkObject.rel || ''
      });
      setIsLinkPickerOpen(false);
    },
    onRemove: () => {
      setAttributes({
        linkUrl: '',
        linkTarget: '_self',
        linkRel: ''
      });
      setIsLinkPickerOpen(false);
    },
    forceIsEditingLink: true
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Otwórz w nowej karcie', 'wlc-custom-block'),
    checked: linkTarget === '_blank',
    onChange: value => setAttributes({
      linkTarget: value ? '_blank' : '_self'
    })
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Relacja linku (rel)', 'wlc-custom-block'),
    value: linkRel,
    onChange: value => setAttributes({
      linkRel: value
    }),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Opcjonalnie: nofollow, sponsored, itp.', 'wlc-custom-block')
  }), linkUrl && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-link-info",
    style: {
      marginTop: '10px',
      padding: '10px',
      background: '#f0f0f0',
      borderRadius: '4px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    style: {
      margin: '0 0 10px 0',
      wordBreak: 'break-all'
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Link:', 'wlc-custom-block'), " ", linkUrl), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    variant: "secondary",
    isSmall: true,
    onClick: () => setAttributes({
      linkUrl: '',
      linkTarget: '_self',
      linkRel: ''
    })
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Usuń link', 'wlc-custom-block'))))), svgContent && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Podgląd kodu SVG', 'wlc-custom-block'),
    initialOpen: false
  }, copySuccess && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
    status: "success",
    isDismissible: false,
    className: "svg-copy-notice"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Kod został skopiowany do schowka!', 'wlc-custom-block')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-code-preview"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextareaControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Finalny kod SVG', 'wlc-custom-block'),
    value: modifiedSvgContent,
    readOnly: true,
    rows: 8
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    variant: "secondary",
    onClick: copySvgToClipboard,
    className: "svg-copy-button"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Kopiuj do schowka', 'wlc-custom-block'))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...blockProps
  }, isLoading && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-block-loading"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Spinner, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Ładowanie SVG...', 'wlc-custom-block'))), error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-block-error"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, error)), svgContent && !isLoading &&
  // Simplified structure - without unnecessary div
  (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    dangerouslySetInnerHTML: {
      __html: modifiedSvgContent || svgContent
    }
  }), !svgContent && !isLoading && !error && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "svg-block-placeholder"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Wybierz plik SVG z biblioteki mediów, podaj link do pliku SVG lub wklej kod SVG bezpośrednio', 'wlc-custom-block')))));
}

/***/ }),

/***/ "./src/blocks/custom-svg/editor.css":
/*!******************************************!*\
  !*** ./src/blocks/custom-svg/editor.css ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/blocks/custom-svg/save.js":
/*!***************************************!*\
  !*** ./src/blocks/custom-svg/save.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ save)
/* harmony export */ });
function save() {
  return null;
}

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
/************************************************************************/
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
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!****************************************!*\
  !*** ./src/blocks/custom-svg/index.js ***!
  \****************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _editor_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./editor.css */ "./src/blocks/custom-svg/editor.css");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block.json */ "./src/blocks/custom-svg/block.json");
/* harmony import */ var _edit_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./edit.js */ "./src/blocks/custom-svg/edit.js");
/* harmony import */ var _save_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./save.js */ "./src/blocks/custom-svg/save.js");
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */




(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_2__.name, {
  ..._block_json__WEBPACK_IMPORTED_MODULE_2__,
  /**
   * @see ./edit.js
   */
  edit: _edit_js__WEBPACK_IMPORTED_MODULE_3__["default"],
  /**
   * @see ./save.js
   */
  save: _save_js__WEBPACK_IMPORTED_MODULE_4__["default"]
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map