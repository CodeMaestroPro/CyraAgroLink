import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Map activity intensity to green shade.
 */
function activityColor(intensity) {
    if (intensity >= 88) {
        return '#0A5C2E';
    }

    if (intensity >= 78) {
        return '#10853F';
    }

    if (intensity >= 68) {
        return '#1A9B4C';
    }

    return '#2F8F4E';
}

document.addEventListener('DOMContentLoaded', () => {
    const productionCanvas = document.getElementById('governmentProductionChart');
    const mapEl = document.getElementById('governmentNigeriaMap');

    if (productionCanvas) {
        const labels = JSON.parse(productionCanvas.dataset.labels || '[]');
        const values = JSON.parse(productionCanvas.dataset.values || '[]');
        const colors = JSON.parse(productionCanvas.dataset.colors || '[]');

        new Chart(productionCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 0,
                        hoverOffset: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${context.parsed}%`,
                        },
                    },
                },
            },
        });
    }

    if (mapEl) {
        const zones = JSON.parse(mapEl.dataset.zones || '[]');
        const map = L.map(mapEl, {
            zoomControl: false,
            attributionControl: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
        }).setView([9.2, 8.1], 5.5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 10,
        }).addTo(map);

        zones.forEach((zone) => {
            const radius = 28000 + Number(zone.intensity) * 700;

            L.circle([zone.lat, zone.lng], {
                radius,
                color: activityColor(zone.intensity),
                fillColor: activityColor(zone.intensity),
                fillOpacity: 0.55,
                weight: 1,
            })
                .bindTooltip(`${zone.name}: ${zone.intensity}/100`, {
                    direction: 'top',
                    opacity: 0.95,
                })
                .addTo(map);
        });
    }
});
