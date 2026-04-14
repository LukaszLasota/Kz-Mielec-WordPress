import { __ } from '@wordpress/i18n';
import { 
    useBlockProps, 
    InspectorControls,
    __experimentalLinkControl as LinkControl,
    URLPopover 
} from '@wordpress/block-editor';
import {
    PanelBody,
    Button,
    TextControl,
    TextareaControl,
    Spinner,
    ToggleControl,
    __experimentalUnitControl as UnitControl,
    ColorPicker,
    Notice,
} from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

/**
 * Removes XML declaration from SVG content
 * 
 * @param {string} svgContent Original SVG content
 * @return {string} SVG content without XML declaration
 */
const removeXmlDeclaration = (svgContent) => {
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
export default function Edit(props) {
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
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [pastedSvgCode, setPastedSvgCode] = useState('');
    const [modifiedSvgContent, setModifiedSvgContent] = useState('');
    const [copySuccess, setCopySuccess] = useState(false);
    const [isLinkPickerOpen, setIsLinkPickerOpen] = useState(false);
    const linkPickerRef = useRef(null);
    
    // Use BlockProps and extract CSS classes
    const blockProps = useBlockProps();
    const blockCssClasses = blockProps.className || '';

    // Update modifiedSvgContent when svgContent or parameters change
    useEffect(() => {
        if (svgContent) {
            const modified = modifySvg(
                svgContent, 
                svgWidth, 
                svgHeight, 
                svgFill, 
                svgStroke, 
                applyColorToAllElements,
                blockCssClasses // Pass CSS classes from editor
            );
            setModifiedSvgContent(modified);
        } else {
            setModifiedSvgContent('');
        }
    }, [svgContent, svgWidth, svgHeight, svgFill, svgStroke, applyColorToAllElements, blockCssClasses]);

    // Reset copy success state after 2 seconds
    useEffect(() => {
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
    const validateSvgCode = (code) => {
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
            navigator.clipboard.writeText(modifiedSvgContent)
                .then(() => {
                    setCopySuccess(true);
                })
                .catch((err) => {
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
    const fetchSvgFromMediaLibrary = (media) => {
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
            fetch(media.url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(svgText => {
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
                })
                .catch(err => {
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
    const fetchSvgFromUrl = (url) => {
        if (!url) return;
        
        setIsLoading(true);
        setError('');
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text();
            })
            .then(svgText => {
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
            })
            .catch(err => {
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
    const updateHeight = (width) => {
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
                            setAttributes({ svgHeight: newHeight + (width.match(/[a-z%]+$/i) || ['']) });
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
                        setAttributes({ svgHeight: newHeight + (width.match(/[a-z%]+$/i) || ['']) });
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
    const updateWidth = (height) => {
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
                            setAttributes({ svgWidth: newWidth + (height.match(/[a-z%]+$/i) || ['']) });
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
                        setAttributes({ svgWidth: newWidth + (height.match(/[a-z%]+$/i) || ['']) });
                    }
                }
            }
        }
    };

    // Render the component
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Wybór SVG', 'wlc-custom-block')} initialOpen={true}>
                    {/* Select file from media library */}
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => {
                                if (media && media.id) {
                                    console.log('Wybrano media:', media);
                                    fetchSvgFromMediaLibrary(media);
                                }
                            }}
                            allowedTypes={['image/svg+xml']}
                            value={svgId || 0}
                            render={({ open }) => (
                                <Button 
                                    onClick={open}
                                    variant="primary"
                                >
                                    {svgId 
                                        ? __('Zmień SVG', 'wlc-custom-block') 
                                        : __('Wybierz SVG z biblioteki mediów', 'wlc-custom-block')
                                    }
                                </Button>
                            )}
                        />
                    </MediaUploadCheck>
                    
                    {/* Divider */}
                    <hr />
                    
                    {/* Or enter URL */}
                    <TextControl
                        label={__('Lub podaj link do pliku SVG', 'wlc-custom-block')}
                        value={svgUrl || ''}
                        onChange={(url) => setAttributes({ svgUrl: url })}
                    />
                    
                    <Button 
                        variant="primary"
                        disabled={!svgUrl || isLoading}
                        onClick={() => {
                            if (svgUrl) {
                                fetchSvgFromUrl(svgUrl);
                            }
                        }}
                    >
                        {__('Pobierz SVG z linku', 'wlc-custom-block')}
                    </Button>
                    
                    {/* Divider */}
                    <hr />
                    
                    {/* Or paste SVG code */}
                    <TextareaControl
                        label={__('Lub wklej kod SVG bezpośrednio', 'wlc-custom-block')}
                        value={pastedSvgCode}
                        onChange={(code) => setPastedSvgCode(code)}
                        rows={8}
                        placeholder="<svg>...</svg>"
                    />
                    
                    <Button 
                        variant="primary"
                        disabled={!pastedSvgCode || isLoading}
                        onClick={applyPastedSvgCode}
                    >
                        {__('Zastosuj wklejony kod SVG', 'wlc-custom-block')}
                    </Button>
                </PanelBody>
                
                <PanelBody title={__('Parametry SVG', 'wlc-custom-block')} initialOpen={true}>
                    {/* Dimensions */}
                    <ToggleControl
                        label={__('Zachowaj proporcje', 'wlc-custom-block')}
                        checked={preserveAspectRatio}
                        onChange={(value) => setAttributes({ preserveAspectRatio: value })}
                    />
                    
                    <UnitControl
                        label={__('Szerokość', 'wlc-custom-block')}
                        value={svgWidth}
                        onChange={(value) => {
                            setAttributes({ svgWidth: value });
                            if (preserveAspectRatio) {
                                updateHeight(value);
                            }
                        }}
                        units={[
                            { value: 'px', label: 'px' },
                            { value: '%', label: '%' },
                            { value: 'em', label: 'em' },
                            { value: 'rem', label: 'rem' },
                        ]}
                    />
                    
                    <UnitControl
                        label={__('Wysokość', 'wlc-custom-block')}
                        value={svgHeight}
                        onChange={(value) => {
                            setAttributes({ svgHeight: value });
                            if (preserveAspectRatio) {
                                updateWidth(value);
                            }
                        }}
                        units={[
                            { value: 'px', label: 'px' },
                            { value: '%', label: '%' },
                            { value: 'em', label: 'em' },
                            { value: 'rem', label: 'rem' },
                        ]}
                    />
                    
                    {/* Divider */}
                    <hr />
                    
                    {/* Colors */}
                    <ToggleControl
                        label={__('Zastosuj kolory do wszystkich elementów', 'wlc-custom-block')}
                        checked={applyColorToAllElements}
                        onChange={(value) => setAttributes({ applyColorToAllElements: value })}
                        help={__('Jeśli włączone, kolory zostaną zastosowane do wszystkich elementów SVG, nadpisując ich kolory.', 'wlc-custom-block')}
                    />
                    
                    <div className="svg-block-color-control">
                        <label>{__('Kolor wypełnienia (fill)', 'wlc-custom-block')}</label>
                        <ColorPicker
                            color={svgFill}
                            onChange={(value) => setAttributes({ svgFill: value })}
                            enableAlpha
                        />
                        {svgFill && (
                            <Button 
                                variant="secondary" 
                                isSmall
                                onClick={() => setAttributes({ svgFill: '' })}
                            >
                                {__('Usuń kolor', 'wlc-custom-block')}
                            </Button>
                        )}
                    </div>
                    
                    <div className="svg-block-color-control">
                        <label>{__('Kolor obrysu (stroke)', 'wlc-custom-block')}</label>
                        <ColorPicker
                            color={svgStroke}
                            onChange={(value) => setAttributes({ svgStroke: value })}
                            enableAlpha
                        />
                        {svgStroke && (
                            <Button 
                                variant="secondary" 
                                isSmall
                                onClick={() => setAttributes({ svgStroke: '' })}
                            >
                                {__('Usuń kolor', 'wlc-custom-block')}
                            </Button>
                        )}
                    </div>
                </PanelBody>

                {/* Link Settings Panel */}
                <PanelBody title={__('Ustawienia linku', 'wlc-custom-block')} initialOpen={false}>
                    <ToggleControl
                        label={__('Opakuj SVG linkiem', 'wlc-custom-block')}
                        checked={wrapWithLink}
                        onChange={(value) => setAttributes({ wrapWithLink: value })}
                        help={__('Jeśli włączone, SVG będzie opakowane linkiem.', 'wlc-custom-block')}
                    />
                    
                    {wrapWithLink && (
                        <div className="svg-link-settings" style={{ position: 'relative' }}>
                            <TextControl
                                label={__('Adres URL', 'wlc-custom-block')}
                                value={linkUrl}
                                onChange={(value) => setAttributes({ linkUrl: value })}
                                placeholder={__('Wpisz URL lub wyszukaj stronę', 'wlc-custom-block')}
                            />
                            
                            <div ref={linkPickerRef}>
                                <Button
                                    variant="secondary"
                                    onClick={() => setIsLinkPickerOpen(!isLinkPickerOpen)}
                                    style={{ marginBottom: '10px' }}
                                >
                                    {__('Wybierz z witryny', 'wlc-custom-block')}
                                </Button>
                                
                                {isLinkPickerOpen && (
                                    <URLPopover
                                        position="bottom center"
                                        onClose={() => setIsLinkPickerOpen(false)}
                                        renderSettings={() => (
                                            <ToggleControl
                                                label={__('Otwórz w nowej karcie', 'wlc-custom-block')}
                                                checked={linkTarget === '_blank'}
                                                onChange={(value) => 
                                                    setAttributes({ linkTarget: value ? '_blank' : '_self' })
                                                }
                                            />
                                        )}
                                    >
                                        <div style={{ padding: '16px', width: '350px', maxWidth: '100%' }}>
                                            <LinkControl
                                                value={{
                                                    url: linkUrl,
                                                    opensInNewTab: linkTarget === '_blank'
                                                }}
                                                onChange={(linkObject) => {
                                                    setAttributes({
                                                        linkUrl: linkObject.url || '',
                                                        linkTarget: linkObject.opensInNewTab ? '_blank' : '_self',
                                                        linkRel: linkObject.rel || ''
                                                    });
                                                    setIsLinkPickerOpen(false);
                                                }}
                                                onRemove={() => {
                                                    setAttributes({
                                                        linkUrl: '',
                                                        linkTarget: '_self',
                                                        linkRel: ''
                                                    });
                                                    setIsLinkPickerOpen(false);
                                                }}
                                                forceIsEditingLink={true}
                                            />
                                        </div>
                                    </URLPopover>
                                )}
                            </div>
                            
                            <ToggleControl
                                label={__('Otwórz w nowej karcie', 'wlc-custom-block')}
                                checked={linkTarget === '_blank'}
                                onChange={(value) => 
                                    setAttributes({ linkTarget: value ? '_blank' : '_self' })
                                }
                            />
                            
                            <TextControl
                                label={__('Relacja linku (rel)', 'wlc-custom-block')}
                                value={linkRel}
                                onChange={(value) => setAttributes({ linkRel: value })}
                                help={__('Opcjonalnie: nofollow, sponsored, itp.', 'wlc-custom-block')}
                            />
                            
                            {linkUrl && (
                                <div className="svg-link-info" style={{ marginTop: '10px', padding: '10px', background: '#f0f0f0', borderRadius: '4px' }}>
                                    <p style={{ margin: '0 0 10px 0', wordBreak: 'break-all' }}>
                                        {__('Link:', 'wlc-custom-block')} {linkUrl}
                                    </p>
                                    <Button
                                        variant="secondary"
                                        isSmall
                                        onClick={() => setAttributes({
                                            linkUrl: '',
                                            linkTarget: '_self',
                                            linkRel: ''
                                        })}
                                    >
                                        {__('Usuń link', 'wlc-custom-block')}
                                    </Button>
                                </div>
                            )}
                        </div>
                    )}
                </PanelBody>

                {svgContent && (
                    <PanelBody title={__('Podgląd kodu SVG', 'wlc-custom-block')} initialOpen={false}>
                        {copySuccess && (
                            <Notice status="success" isDismissible={false} className="svg-copy-notice">
                                {__('Kod został skopiowany do schowka!', 'wlc-custom-block')}
                            </Notice>
                        )}
                        
                        <div className="svg-code-preview">
                            <TextareaControl
                                label={__('Finalny kod SVG', 'wlc-custom-block')}
                                value={modifiedSvgContent}
                                readOnly
                                rows={8}
                            />
                            
                            <Button 
                                variant="secondary"
                                onClick={copySvgToClipboard}
                                className="svg-copy-button"
                            >
                                {__('Kopiuj do schowka', 'wlc-custom-block')}
                            </Button>
                        </div>
                    </PanelBody>
                )}
            </InspectorControls>
            
            {/* Main block content */}
            <div {...blockProps}>
                {isLoading && (
                    <div className="svg-block-loading">
                        <Spinner />
                        <p>{__('Ładowanie SVG...', 'wlc-custom-block')}</p>
                    </div>
                )}
                
                {error && (
                    <div className="svg-block-error">
                        <p>{error}</p>
                    </div>
                )}
                
                {svgContent && !isLoading && (
                    // Simplified structure - without unnecessary div
                    <div 
                        dangerouslySetInnerHTML={{ 
                            __html: modifiedSvgContent || svgContent 
                        }}
                    />
                )}

                {!svgContent && !isLoading && !error && (
                    <div className="svg-block-placeholder">
                        <p>{__('Wybierz plik SVG z biblioteki mediów, podaj link do pliku SVG lub wklej kod SVG bezpośrednio', 'wlc-custom-block')}</p>
                    </div>
                )}
            </div>
        </>
    );
}