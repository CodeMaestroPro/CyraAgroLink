import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Initialize Smart City Food Distribution delivery map.
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('smartCityDeliveryMap');

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
        dragging: true,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 18,
    }).addTo(map);

    L.polyline(latLngs, {
        color: '#4B5563',
        weight: 4,
        opacity: 0.9,
        lineJoin: 'round',
        lineCap: 'round',
        smoothFactor: 1.4,
    }).addTo(map);

    const markerIcon = (kind, label) => L.divIcon({
        className: '',
        html: `
            <div style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;transform:translate(-50%, -120%);">
                <span style="
                    width:28px;height:28px;border-radius:9999px;background:#10853F;color:#fff;
                    display:inline-flex;align-items:center;justify-content:center;
                    box-shadow:0 4px 12px rgba(16,133,63,.35);border:2px solid #fff;
                ">
                    ${kind === 'end'
                        ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-5.2 7-11a7 7 0 10-14 0c0 5.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>'
                        : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 16h13V8H5.5L3 10.5V16z"/><path d="M16 11h3l2 2v3h-5v-5z"/><circle cx="7.5" cy="17.5" r="1.2"/><circle cx="17.5" cy="17.5" r="1.2"/></svg>'
                    }
                </span>
                <span style="
                    background:#fff;color:#1F2937;font:600 11px/1 Plus Jakarta Sans,sans-serif;
                    padding:5px 8px;border-radius:9999px;border:1px solid #E6EBE7;
                    box-shadow:0 6px 16px rgba(16,133,63,.12);
                ">${label}</span>
            </div>
        `,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
    });

    points.forEach((point) => {
        if (point.kind === 'waypoint') {
            return;
        }

        L.marker([Number(point.lat), Number(point.lng)], {
            icon: markerIcon(point.kind, point.label),
            interactive: false,
        }).addTo(map);
    });

    map.fitBounds(L.latLngBounds(latLngs).pad(0.25));
});
