import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Map rainfall millimeters to a blue intensity color.
 */
function rainfallColor(mm) {
    if (mm >= 50) {
        return '#1D4ED8';
    }

    if (mm >= 30) {
        return '#3B82F6';
    }

    if (mm >= 15) {
        return '#60A5FA';
    }

    if (mm >= 5) {
        return '#93C5FD';
    }

    return '#DBEAFE';
}

/**
 * Initialize Nigeria rainfall intensity map.
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('weatherRainfallMap');

    if (! el) {
        return;
    }

    const zones = JSON.parse(el.dataset.zones || '[]');
    const map = L.map(el, {
        zoomControl: false,
        attributionControl: false,
        dragging: true,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
    }).setView([9.1, 8.7], 5.4);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 12,
    }).addTo(map);

    zones.forEach((zone) => {
        const radius = 18000 + Number(zone.mm) * 900;

        L.circle([zone.lat, zone.lng], {
            radius,
            color: rainfallColor(zone.mm),
            fillColor: rainfallColor(zone.mm),
            fillOpacity: 0.45,
            weight: 1,
        })
            .bindTooltip(`${zone.label}: ${zone.mm} mm`, {
                direction: 'top',
                opacity: 0.95,
            })
            .addTo(map);
    });
});
