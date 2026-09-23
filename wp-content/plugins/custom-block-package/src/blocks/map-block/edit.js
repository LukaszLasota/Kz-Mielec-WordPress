import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, SelectControl, ToggleControl, ExternalLink } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import L from 'leaflet';
import markerIconUrl from './images/marker-icon.png';
import markerShadowUrl from './images/marker-shadow.png';
import { TILE_PROVIDERS, getTileProvider } from './tile-providers';
import './index.scss';

/*
 * Make Leaflet's dragging work inside the editor's canvas iframe.
 *
 * This script runs in the editor window, but the map lives in the canvas
 * iframe. Leaflet's Draggable binds its mousemove/mouseup listeners to the
 * global `document` - the editor window's - so it saw moves only while the
 * pointer was over the sidebar (and a click never ended, because the mouseup
 * fired in the iframe). Gutenberg also re-dispatches some iframe events to the
 * window with shifted coordinates, and the two sources disagreeing is what made
 * the map jump while panning.
 *
 * So once a drag starts, its listeners are moved to the document the dragged
 * element actually belongs to. Only the editor bundle is patched; the front end
 * has one document and keeps stock Leaflet.
 */
const MOVE_EVENTS = 'mousemove touchmove';
const END_EVENTS = 'mouseup touchend touchcancel';
if (!L.Draggable.prototype._cbpOwnDocument) {
    const onDown = L.Draggable.prototype._onDown;
    const finishDrag = L.Draggable.prototype.finishDrag;

    L.Draggable.prototype._onDown = function (e) {
        onDown.call(this, e);
        const doc = this._element.ownerDocument;
        if (L.Draggable._dragging !== this || !doc || doc === document) {
            return;
        }
        L.DomEvent.off(document, MOVE_EVENTS, this._onMove, this);
        L.DomEvent.off(document, END_EVENTS, this._onUp, this);
        L.DomEvent.on(doc, MOVE_EVENTS, this._onMove, this);
        L.DomEvent.on(doc, END_EVENTS, this._onUp, this);
    };

    L.Draggable.prototype.finishDrag = function (noInertia) {
        const doc = this._element.ownerDocument;
        if (doc && doc !== document) {
            L.DomEvent.off(doc, MOVE_EVENTS, this._onMove, this);
            L.DomEvent.off(doc, END_EVENTS, this._onUp, this);
        }
        finishDrag.call(this, noInertia);
    };

    L.Draggable.prototype._cbpOwnDocument = true;
}

// Same values as MapLocation::PLACEHOLDER_* in PHP, and block.json's defaults.
const PLACEHOLDER_LAT = 50.299071;
const PLACEHOLDER_LNG = 21.4483254;

// Printed by MapEditorData: the coordinates from the contact settings and a link
// to that screen. Absent or null when the option is not set.
const CONTACT = window.cbpMapContact || {};
const HAS_CONTACT = typeof CONTACT.lat === 'number' && typeof CONTACT.lng === 'number';

// Mirrors MapLocation::uses_contact(): an explicit choice wins; a block saved
// before the choice existed follows the settings while it holds the placeholder.
const usesContact = ({ locationSource, latitude, longitude }) => {
    if (locationSource === 'contact' || locationSource === 'custom') {
        return locationSource === 'contact';
    }
    return (
        Math.abs(latitude - PLACEHOLDER_LAT) < 0.000001 &&
        Math.abs(longitude - PLACEHOLDER_LNG) < 0.000001
    );
};

const Edit = ({ attributes, setAttributes }) => {
    const { zoom, containerHeight, popupText, tileStyle } = attributes;
    const fromContact = usesContact(attributes) && HAS_CONTACT;
    const latitude = fromContact ? CONTACT.lat : attributes.latitude;
    const longitude = fromContact ? CONTACT.lng : attributes.longitude;
    const mapContainer = useRef(null);
    const mapInstance = useRef(null);
    const marker = useRef(null);
    const tileLayer = useRef(null);
    const overlayLayer = useRef(null);
    const cleanupDragFix = useRef(null);

    const blockProps = useBlockProps();

    // Swap the tile layer (and its overlay, if the style has one) in place.
    const applyTiles = (map, style) => {
        if (tileLayer.current) {
            map.removeLayer(tileLayer.current);
        }
        if (overlayLayer.current) {
            map.removeLayer(overlayLayer.current);
            overlayLayer.current = null;
        }
        const provider = getTileProvider(style);
        tileLayer.current = L.tileLayer(provider.url, provider.options).addTo(map);
        tileLayer.current.getContainer().style.filter = provider.filter || '';
        if (provider.overlay) {
            overlayLayer.current = L.tileLayer(provider.overlay.url, provider.overlay.options).addTo(map);
        }
    };

    // Create the map once. Each attribute below has its own effect: one shared
    // effect rebuilt the tile layer and re-centred the map on every change -
    // typing the popup text included - which is what made the preview jump.
    useEffect(() => {
        if (!mapContainer.current || mapInstance.current) {
            return;
        }

        // Configure default icon using webpack-resolved paths
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconUrl: markerIconUrl,
            shadowUrl: markerShadowUrl,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        });

        // The wheel scrolls the editor, not the map; the +/- buttons zoom it and
        // write the Zoom setting, so what the preview shows is what gets saved.
        const map = L.map(mapContainer.current, { scrollWheelZoom: false }).setView([latitude, longitude], zoom);
        mapInstance.current = map;
        map.on('zoomend', () => setAttributes({ zoom: map.getZoom() }));
        applyTiles(map, tileStyle);

        marker.current = L.marker([latitude, longitude], { draggable: !fromContact }).addTo(map);
        marker.current.bindPopup(popupText);

        // The editor's content CSS has `.wp-block img:not([draggable])
        // { pointer-events: none }`, which outranks Leaflet's own rule and made the
        // pin unclickable: grabbing it panned the map instead. An explicit
        // draggable="false" takes the icon out of that selector and also stops
        // the browser's native image drag, which Leaflet only blocks in its own
        // window, not in the canvas iframe.
        marker.current.getElement().setAttribute('draggable', 'false');

        marker.current.on('dragend', (e) => {
            const { lat, lng } = e.target.getLatLng();
            setAttributes({ latitude: lat, longitude: lng });
        });

        // Backstop for the patch at the top of this file: a release outside the
        // canvas (over the sidebar) or leaving the map ends whichever of this
        // map's drags is armed, so none is ever left running. finishDrag() fires
        // `dragend`, so a dragged pin still saves its spot.
        const abortDrag = () => {
            const active = L.Draggable._dragging;
            const ours = [map.dragging?._draggable, marker.current?.dragging?._draggable];
            if (active && ours.includes(active)) {
                active.finishDrag();
            }
        };
        const container = map.getContainer();
        const docs = [container.ownerDocument, window.document];
        container.addEventListener('mouseleave', abortDrag);
        // A release outside the browser window reaches no document at all, so a
        // drag can still be left armed; the next press on the map ends it first
        // (capture phase, before Leaflet's own mousedown handler).
        container.addEventListener('mousedown', abortDrag, true);


        docs.forEach((doc) => {
            doc.addEventListener('mouseup', abortDrag);
            doc.addEventListener('pointerup', abortDrag);
        });

        // Leaflet measures its container once. The editor resizes it - the
        // height control, the sidebar opening, the canvas settling - and a map
        // that does not know shifts its tiles and marker off-centre.
        const view = container.ownerDocument.defaultView || window;
        const resize = view.ResizeObserver ? new view.ResizeObserver(() => map.invalidateSize()) : null;
        if (resize) {
            resize.observe(container);
        }

        cleanupDragFix.current = () => {
            container.removeEventListener('mouseleave', abortDrag);
            container.removeEventListener('mousedown', abortDrag, true);
            docs.forEach((doc) => {
                doc.removeEventListener('mouseup', abortDrag);
                doc.removeEventListener('pointerup', abortDrag);
            });
            if (resize) {
                resize.disconnect();
            }
        };
    }, []);

    // Move the marker and the view only when the location itself changes.
    useEffect(() => {
        if (!mapInstance.current) {
            return;
        }
        marker.current.setLatLng([latitude, longitude]);
        // Keep the pin centred, as the published map is. A gentle pan rather than
        // a snap, so moving the pin reads as the map following it.
        mapInstance.current.panTo([latitude, longitude], { animate: true, duration: 0.3 });
    }, [latitude, longitude]);

    useEffect(() => {
        if (mapInstance.current && mapInstance.current.getZoom() !== zoom) {
            mapInstance.current.setZoom(zoom, { animate: false });
        }
    }, [zoom]);

    useEffect(() => {
        if (marker.current) {
            marker.current.setPopupContent(popupText);
        }
    }, [popupText]);

    useEffect(() => {
        if (mapInstance.current) {
            applyTiles(mapInstance.current, tileStyle);
        }
    }, [tileStyle]);

    // A map on the contact-settings location is moved from that screen, not by
    // dragging the pin, so dragging is off.
    useEffect(() => {
        if (!marker.current) {
            return;
        }
        if (fromContact) {
            marker.current.dragging.disable();
        } else {
            marker.current.dragging.enable();
        }
    }, [fromContact]);

    // Cleanup map instance on unmount
    useEffect(() => {
        return () => {
            if (cleanupDragFix.current) {
                cleanupDragFix.current();
                cleanupDragFix.current = null;
            }
            if (mapInstance.current) {
                mapInstance.current.remove();
                mapInstance.current = null;
            }
        };
    }, []);

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Ustawienia mapy', 'custom-block-package')} initialOpen={true}>
                    {HAS_CONTACT && (
                        <ToggleControl
                            label={__('Lokalizacja z danych kontaktowych', 'custom-block-package')}
                            checked={fromContact}
                            onChange={(checked) =>
                                setAttributes(
                                    checked
                                        ? { locationSource: 'contact' }
                                        // Start the custom pair from where the map
                                        // is now, not from the placeholder.
                                        : { locationSource: 'custom', latitude: CONTACT.lat, longitude: CONTACT.lng }
                                )
                            }
                        />
                    )}
                    {fromContact ? (
                        <p>
                            {__('Współrzędne', 'custom-block-package')}: {latitude}, {longitude}
                            {CONTACT.settingsUrl && (
                                <>
                                    <br />
                                    <ExternalLink href={CONTACT.settingsUrl}>
                                        {__('Zmień w: Dane kontaktowe', 'custom-block-package')}
                                    </ExternalLink>
                                </>
                            )}
                        </p>
                    ) : (
                        <>
                            <TextControl
                                label={__('Szerokość geograficzna', 'custom-block-package')}
                                value={latitude}
                                onChange={(value) => setAttributes({ latitude: parseFloat(value) || 0 })}
                            />
                            <TextControl
                                label={__('Długość geograficzna', 'custom-block-package')}
                                value={longitude}
                                onChange={(value) => setAttributes({ longitude: parseFloat(value) || 0 })}
                            />
                        </>
                    )}
                    <RangeControl
                        label={__('Powiększenie', 'custom-block-package')}
                        value={zoom}
                        onChange={(value) => setAttributes({ zoom: value })}
                        min={1}
                        max={18}
                    />
                    <RangeControl
                        label={__('Wysokość kontenera (px)', 'custom-block-package')}
                        value={containerHeight}
                        onChange={(value) => setAttributes({ containerHeight: value })}
                        min={200}
                        max={800}
                    />
                    <TextControl
                        label={__('Treść popupu', 'custom-block-package')}
                        value={popupText}
                        onChange={(value) => setAttributes({ popupText: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Wygląd mapy', 'custom-block-package')} initialOpen={true}>
                    <SelectControl
                        label={__('Styl mapy', 'custom-block-package')}
                        value={tileStyle}
                        options={Object.keys(TILE_PROVIDERS).map((key) => ({
                            label: TILE_PROVIDERS[key].label,
                            value: key,
                        }))}
                        onChange={(value) => setAttributes({ tileStyle: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <div
                {...blockProps}
                style={{
                    height: `${containerHeight}px`,
                    width: '100%',
                    position: 'relative',
                }}
            >
                <div
                    ref={mapContainer}
                    className="map-container"
                    style={{
                        height: '100%',
                        width: '100%',
                        position: 'relative',
                    }}
                ></div>
            </div>
        </>
    );
};

export default Edit;
