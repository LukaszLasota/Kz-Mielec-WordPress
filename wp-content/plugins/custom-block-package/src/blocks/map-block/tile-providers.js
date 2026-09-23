/**
 * Shared Leaflet tile providers for the map block.
 *
 * Used by both the editor preview (edit.js) and the front-end (frontend.js)
 * so the available map styles stay in one place. All providers are free and
 * require no API key.
 *
 * CARTO's styles (Voyager, Positron, Dark) were removed on 2026-09-23: loaded
 * from a website they now draw "API KEY REQUIRED" across every tile. The street
 * names on the satellite view moved from CARTO to Esri the same day. A block still saved with one of those keys
 * falls back to DEFAULT_TILE_STYLE. Check a new provider from a browser on the
 * site, not with curl - CARTO answered curl with real tiles.
 */

const OSM_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const ESRI_ATTR = 'Tiles &copy; <a href="https://www.esri.com/">Esri</a>, Maxar, Earthstar Geographics';
const TOPO_ATTR = `${OSM_ATTR}, <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA)`;

export const TILE_PROVIDERS = {
	standard: {
		label: 'Standardowa (OSM)',
		url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		options: { attribution: OSM_ATTR, maxZoom: 19 },
	},
	satellite: {
		label: 'Satelita (zdjęcia terenu)',
		url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
		options: { attribution: ESRI_ATTR, maxZoom: 19 },
	},
	satelliteLabels: {
		label: 'Satelita + nazwy ulic',
		url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
		options: { attribution: ESRI_ATTR, maxZoom: 19 },
		// Esri's transportation reference layer: roads and street names on a
		// transparent background. Replaces CARTO's label overlay, which now needs
		// an API key; keyless like the imagery under it.
		overlay: {
			url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}',
			options: { attribution: ESRI_ATTR, maxZoom: 19, pane: 'overlayPane' },
		},
	},
	terrain: {
		label: 'Teren / Topo',
		url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
		options: { attribution: TOPO_ATTR, subdomains: 'abc', maxZoom: 17 },
	},
	esriStreet: {
		label: 'Esri Street (wyraziste ulice i nazwy)',
		url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
		options: { attribution: ESRI_ATTR, maxZoom: 19 },
	},
	esriTopo: {
		label: 'Esri Topo (topografia)',
		url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
		options: { attribution: ESRI_ATTR, maxZoom: 19 },
	},
	esriGray: {
		label: 'Esri Gray (jasnoszara)',
		url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
		options: { attribution: ESRI_ATTR, maxZoom: 16 },
	},
	humanitarian: {
		label: 'Humanitarian OSM (ciepła)',
		url: 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
		options: { attribution: `${OSM_ATTR}, Humanitarian OSM Team`, subdomains: 'abc', maxZoom: 19 },
	},
};

export const DEFAULT_TILE_STYLE = 'esriStreet';

/**
 * Resolve a tile style key to its provider config, falling back to the default.
 *
 * @param {string} style Tile style key.
 * @return {{url: string, options: object}} Provider config.
 */
export function getTileProvider( style ) {
	return TILE_PROVIDERS[ style ] || TILE_PROVIDERS[ DEFAULT_TILE_STYLE ];
}
