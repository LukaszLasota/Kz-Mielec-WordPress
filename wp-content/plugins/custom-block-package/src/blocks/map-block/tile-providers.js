/**
 * Shared Leaflet tile providers for the map block.
 *
 * Used by both the editor preview (edit.js) and the front-end (frontend.js)
 * so the available map styles stay in one place. All providers are free and
 * require no API key.
 */

const OSM_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const CARTO_ATTR = `${OSM_ATTR} &copy; <a href="https://carto.com/attributions">CARTO</a>`;
const ESRI_ATTR = 'Tiles &copy; <a href="https://www.esri.com/">Esri</a>, Maxar, Earthstar Geographics';
const TOPO_ATTR = `${OSM_ATTR}, <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA)`;

export const TILE_PROVIDERS = {
	standard: {
		label: 'Standardowa (OSM)',
		url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		options: { attribution: OSM_ATTR, maxZoom: 19 },
	},
	voyager: {
		label: 'Voyager (jasna, czytelna)',
		url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
		options: { attribution: CARTO_ATTR, subdomains: 'abcd', maxZoom: 20 },
	},
	positron: {
		label: 'Positron (jasnoszara)',
		url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
		options: { attribution: CARTO_ATTR, subdomains: 'abcd', maxZoom: 20 },
	},
	dark: {
		label: 'Dark (ciemna)',
		url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
		options: { attribution: CARTO_ATTR, subdomains: 'abcd', maxZoom: 20 },
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
		// Dense transparent labels overlay (OSM-derived street/place names from
		// CARTO) — far more detailed than Esri's sparse boundaries layer.
		overlay: {
			url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png',
			options: { attribution: CARTO_ATTR, subdomains: 'abcd', maxZoom: 20, pane: 'overlayPane' },
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

export const DEFAULT_TILE_STYLE = 'voyager';

/**
 * Resolve a tile style key to its provider config, falling back to the default.
 *
 * @param {string} style Tile style key.
 * @return {{url: string, options: object}} Provider config.
 */
export function getTileProvider( style ) {
	return TILE_PROVIDERS[ style ] || TILE_PROVIDERS[ DEFAULT_TILE_STYLE ];
}
