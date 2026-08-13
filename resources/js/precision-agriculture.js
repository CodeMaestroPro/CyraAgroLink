import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Map NDVI value (0–1) to heatmap color.
 */
function ndviColor(ndvi) {
    if (ndvi >= 0.7) {
        return '#0A5C2E';
    }

    if (ndvi >= 0.55) {
        return '#10853F';
    }

    if (ndvi >= 0.45) {
        return '#E6A817';
    }

    return '#C2410C';
}

/**
 * Initialize precision agriculture NDVI field map.
 */
document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('precisionNdviMap');

    if (! mapEl) {
        return;
    }

    const center = JSON.parse(mapEl.dataset.center || '{"lat":12.0125,"lng":8.582,"zoom":15}');
    const zones = JSON.parse(mapEl.dataset.zones || '[]');

    const map = L.map(mapEl, {
        zoomControl: false,
        attributionControl: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
    }).setView([center.lat, center.lng], center.zoom);

    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
    }).addTo(map);

    zones.forEach((zone) => {
        const color = ndviColor(Number(zone.ndvi));
        const radius = 55 + Number(zone.ndvi) * 40;

        L.circle([zone.lat, zone.lng], {
            radius,
            color,
            fillColor: color,
            fillOpacity: 0.55,
            weight: 1,
        })
            .bindTooltip(`${zone.label}: NDVI ${Number(zone.ndvi).toFixed(2)}`, {
                direction: 'top',
                opacity: 0.95,
            })
            .addTo(map);
    });

    setTimeout(() => map.invalidateSize(), 120);
});
