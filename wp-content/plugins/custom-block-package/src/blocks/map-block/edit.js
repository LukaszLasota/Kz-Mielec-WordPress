import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, SelectControl } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import L from 'leaflet';
import markerIconUrl from './images/marker-icon.png';
import markerShadowUrl from './images/marker-shadow.png';
import { TILE_PROVIDERS, getTileProvider } from './tile-providers';
import './index.scss';

const Edit = ({ attributes, setAttributes }) => {
    const { latitude, longitude, zoom, containerHeight, popupText, tileStyle } = attributes;
    const mapContainer = useRef(null);
    const mapInstance = useRef(null);
    const marker = useRef(null);
    const tileLayer = useRef(null);
    const overlayLayer = useRef(null);
    const cleanupDragFix = useRef(null);

    const blockProps = useBlockProps();

    useEffect(() => {
        if (!mapContainer.current) {
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

        if (!mapInstance.current) {
            mapInstance.current = L.map(mapContainer.current).setView([latitude, longitude], zoom);

            const provider = getTileProvider(tileStyle);
            tileLayer.current = L.tileLayer(provider.url, provider.options).addTo(mapInstance.current);
            tileLayer.current.getContainer().style.filter = provider.filter || '';
            if (provider.overlay) {
                overlayLayer.current = L.tileLayer(provider.overlay.url, provider.overlay.options).addTo(mapInstance.current);
            }

            marker.current = L.marker([latitude, longitude], { draggable: true }).addTo(mapInstance.current);
            marker.current.bindPopup(popupText);

            marker.current.on('dragend', (e) => {
                const { lat, lng } = e.target.getLatLng();
                setAttributes({ latitude: lat, longitude: lng });
            });

            // Fix drag getting "stuck" in the block editor: the editor canvas
            // (iframed / Gutenberg pointer handling) can swallow the mouseup that
            // ends a Leaflet drag, so the map keeps panning after the button is
            // released or the pointer has left the map. Abort any in-progress
            // drag on pointer release or when leaving the map — public API only,
            // so panning still works normally (pan while held, stop on release).
            const map = mapInstance.current;
            const abortDrag = () => {
                const draggable = map.dragging?._draggable;
                if (draggable?._moving) {
                    map.dragging.disable();
                    map.dragging.enable();
                }
            };
            const container = map.getContainer();
            const docs = [container.ownerDocument, window.document];
            container.addEventListener('mouseleave', abortDrag);
            docs.forEach((doc) => {
                doc.addEventListener('mouseup', abortDrag);
                doc.addEventListener('pointerup', abortDrag);
            });
            cleanupDragFix.current = () => {
                container.removeEventListener('mouseleave', abortDrag);
                docs.forEach((doc) => {
                    doc.removeEventListener('mouseup', abortDrag);
                    doc.removeEventListener('pointerup', abortDrag);
                });
            };
        } else {
            mapInstance.current.setView([latitude, longitude]);
            mapInstance.current.setZoom(zoom);
            marker.current.setLatLng([latitude, longitude]);
            marker.current.setPopupContent(popupText);

            // Swap tile + overlay layers when the style changes.
            const provider = getTileProvider(tileStyle);
            if (tileLayer.current) {
                mapInstance.current.removeLayer(tileLayer.current);
            }
            if (overlayLayer.current) {
                mapInstance.current.removeLayer(overlayLayer.current);
                overlayLayer.current = null;
            }
            tileLayer.current = L.tileLayer(provider.url, provider.options).addTo(mapInstance.current);
            tileLayer.current.getContainer().style.filter = provider.filter || '';
            if (provider.overlay) {
                overlayLayer.current = L.tileLayer(provider.overlay.url, provider.overlay.options).addTo(mapInstance.current);
            }
        }
    }, [latitude, longitude, zoom, popupText, tileStyle]);

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
