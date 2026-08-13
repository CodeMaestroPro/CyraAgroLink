import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Fix Vite asset paths for default Leaflet marker icons.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const cyraPin = L.divIcon({
    className: '',
    html: `
        <div style="
            width: 28px;
            height: 28px;
            border-radius: 9999px 9999px 9999px 4px;
            background: #10853F;
            border: 3px solid #ffffff;
            box-shadow: 0 6px 16px rgba(16,133,63,.35);
            transform: rotate(-45deg);
        "></div>
    `,
    iconSize: [28, 28],
    iconAnchor: [14, 28],
});

/**
 * Alpine component for interactive farm location map.
 *
 * @param {{ lat: number, lng: number }} initial
 */
window.farmLocationMap = (initial) => ({
    lat: Number(initial.lat),
    lng: Number(initial.lng),
    coordinatesDisplay: '',
    map: null,
    marker: null,

    init() {
        this.updateCoordinatesDisplay();

        this.$nextTick(() => {
            const el = document.getElementById('farm-map');

            if (! el || this.map) {
                return;
            }

            this.map = L.map(el, {
                zoomControl: true,
                attributionControl: true,
            }).setView([this.lat, this.lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.map);

            this.marker = L.marker([this.lat, this.lng], {
                draggable: true,
                icon: cyraPin,
            }).addTo(this.map);

            this.marker.on('dragend', () => {
                const position = this.marker.getLatLng();
                this.lat = Number(position.lat.toFixed(7));
                this.lng = Number(position.lng.toFixed(7));
                this.updateCoordinatesDisplay();
            });

            this.map.on('click', (event) => {
                this.lat = Number(event.latlng.lat.toFixed(7));
                this.lng = Number(event.latlng.lng.toFixed(7));
                this.marker.setLatLng([this.lat, this.lng]);
                this.updateCoordinatesDisplay();
            });

            setTimeout(() => this.map.invalidateSize(), 150);
        });
    },

    updateCoordinatesDisplay() {
        const latHemisphere = this.lat >= 0 ? 'N' : 'S';
        const lngHemisphere = this.lng >= 0 ? 'E' : 'W';

        this.coordinatesDisplay = `${Math.abs(this.lat).toFixed(4)}° ${latHemisphere}, ${Math.abs(this.lng).toFixed(4)}° ${lngHemisphere}`;
    },

    resetMarker() {
        this.lat = Number(initial.lat);
        this.lng = Number(initial.lng);

        if (this.marker && this.map) {
            this.marker.setLatLng([this.lat, this.lng]);
            this.map.setView([this.lat, this.lng], 13);
        }

        this.updateCoordinatesDisplay();
    },
});
