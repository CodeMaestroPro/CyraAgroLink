import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Initialize supply-chain route map when the container is present.
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('supplyChainRouteMap');

    if (! el) {
        return;
    }

    const points = JSON.parse(el.dataset.points || '[]');

    if (! Array.isArray(points) || points.length < 2) {
        return;
    }

    const latLngs = points.map((point) => [Number(point.lat), Number(point.lng)]);
    const map = L.map(el, {
        zoomControl: false,
        attributionControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        tap: false,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 18,
    }).addTo(map);

    const route = L.polyline(latLngs, {
        color: '#10853F',
        weight: 4,
        opacity: 0.95,
        lineJoin: 'round',
        lineCap: 'round',
        smoothFactor: 1.4,
    }).addTo(map);

    const labelIcon = (name) => L.divIcon({
        className: '',
        html: `
            <div style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                white-space:nowrap;
                transform:translate(-50%, -120%);
            ">
                <span style="
                    width:12px;
                    height:12px;
                    border-radius:9999px;
                    background:#10853F;
                    border:2px solid #fff;
                    box-shadow:0 2px 8px rgba(16,133,63,.35);
                "></span>
                <span style="
                    font:600 12px/1 Plus Jakarta Sans, system-ui, sans-serif;
                    color:#1F2937;
                    background:rgba(255,255,255,.92);
                    border:1px solid #E6EBE7;
                    border-radius:9999px;
                    padding:4px 8px;
                    box-shadow:0 4px 12px rgba(16,133,63,.08);
                ">${name}</span>
            </div>
        `,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
    });

    const origin = points[0];
    const destination = points[points.length - 1];

    L.marker([origin.lat, origin.lng], { icon: labelIcon(origin.name) }).addTo(map);
    L.marker([destination.lat, destination.lng], { icon: labelIcon(destination.name) }).addTo(map);

    map.fitBounds(route.getBounds(), {
        padding: [36, 36],
    });
});
