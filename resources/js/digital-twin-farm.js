import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Initialize the AI digital twin farm satellite map with plot overlays.
 */
document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('digitalTwinFarmMap');

    if (! mapEl) {
        return;
    }

    const center = JSON.parse(mapEl.dataset.center || '{"lat":12.011,"lng":8.582,"zoom":15}');
    const plots = JSON.parse(mapEl.dataset.plots || '[]');

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

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
        opacity: 0.55,
    }).addTo(map);

    plots.forEach((plot) => {
        const latLngs = (plot.coords || []).map((pair) => [pair[0], pair[1]]);

        L.polygon(latLngs, {
            color: plot.color,
            weight: 1.5,
            fillColor: plot.color,
            fillOpacity: plot.opacity ?? 0.4,
        })
            .bindTooltip(plot.name, {
                sticky: true,
                direction: 'top',
                opacity: 0.95,
            })
            .addTo(map);
    });

    setTimeout(() => map.invalidateSize(), 120);
});
