import L from 'leaflet';

function initMap(mapElement) {
    const latitude = parseFloat(mapElement.dataset.lat);
    const longitude = parseFloat(mapElement.dataset.lng);
    const zoom = parseInt(mapElement.dataset.zoom, 10);
    const popupText = mapElement.dataset.popup || 'Nasza lokalizacja';
    const iconUrl = mapElement.dataset.iconUrl;
    const shadowUrl = mapElement.dataset.shadowUrl;

    if (isNaN(latitude) || isNaN(longitude)) return;

    const map = L.map(mapElement).setView([latitude, longitude], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Configure marker icon from PHP-generated paths
    const markerOptions = {};
    if (iconUrl && shadowUrl) {
        markerOptions.icon = L.icon({
            iconUrl,
            shadowUrl,
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        });
    }

    const marker = L.marker([latitude, longitude], markerOptions).addTo(map);
    marker.bindPopup(popupText).openPopup();
}

// Lazy-initialize maps: defer tile loading until map is near viewport.
document.addEventListener('DOMContentLoaded', () => {
    const maps = document.querySelectorAll('.map-container');
    if (!maps.length) return;

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    initMap(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '600px' });

        maps.forEach((el) => observer.observe(el));
    } else {
        maps.forEach(initMap);
    }
});
