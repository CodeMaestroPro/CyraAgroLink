import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Map hunger risk level to fill color.
 */
function riskColor(risk) {
    switch (risk) {
        case 'severe':
            return '#7F1D1D';
        case 'high':
            return '#DC2626';
        case 'medium':
            return '#E6A817';
        default:
            return '#10853F';
    }
}

/**
 * Initialize national food security hunger risk map.
 */
document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('foodSecurityHungerMap');

    if (! mapEl) {
        return;
    }

    const center = JSON.parse(mapEl.dataset.center || '{"lat":9.2,"lng":8.1,"zoom":5.5}');
    const zones = JSON.parse(mapEl.dataset.zones || '[]');

    const map = L.map(mapEl, {
        zoomControl: false,
        attributionControl: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
    }).setView([center.lat, center.lng], center.zoom);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 10,
    }).addTo(map);

    zones.forEach((zone) => {
        const color = riskColor(zone.risk);

        L.circle([zone.lat, zone.lng], {
            radius: 45000,
            color,
            fillColor: color,
            fillOpacity: 0.45,
            weight: 1,
        })
            .bindTooltip(`${zone.name}: ${zone.risk} risk`, {
                direction: 'top',
                opacity: 0.95,
            })
            .addTo(map);
    });

    setTimeout(() => map.invalidateSize(), 120);
});
