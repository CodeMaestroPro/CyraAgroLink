import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Compact number formatting for chart axes.
 */
function formatCompact(value) {
    const numeric = Number(value);

    if (numeric >= 1000000) {
        return `${(numeric / 1000000).toFixed(0)}M`;
    }

    if (numeric >= 1000) {
        return `${Math.round(numeric / 1000)}K`;
    }

    return String(numeric);
}

/**
 * Map regional score to green intensity.
 */
function regionColor(score) {
    if (score >= 90) {
        return '#0A5C2E';
    }

    if (score >= 80) {
        return '#10853F';
    }

    if (score >= 70) {
        return '#1A9B4C';
    }

    return '#2F8F4E';
}

document.addEventListener('DOMContentLoaded', () => {
    const revenueCanvas = document.getElementById('reportingRevenueTrendChart');
    const transactionsCanvas = document.getElementById('reportingTransactionsChart');
    const segmentsCanvas = document.getElementById('reportingSegmentsChart');
    const mapEl = document.getElementById('reportingRegionsMap');

    if (revenueCanvas) {
        const labels = JSON.parse(revenueCanvas.dataset.labels || '[]');
        const values = JSON.parse(revenueCanvas.dataset.values || '[]');

        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: values,
                        borderColor: '#10853F',
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const { ctx, chartArea } = chart;

                            if (! chartArea) {
                                return 'rgba(16, 133, 63, 0.12)';
                            }

                            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            gradient.addColorStop(0, 'rgba(16, 133, 63, 0.28)');
                            gradient.addColorStop(1, 'rgba(16, 133, 63, 0.02)');

                            return gradient;
                        },
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#10853F',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `₦${Number(context.parsed.y ?? 0).toLocaleString()}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#5F6B63', font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 300000000,
                        grid: { color: 'rgba(230, 235, 231, 1)' },
                        ticks: {
                            color: '#5F6B63',
                            font: { size: 11 },
                            callback: formatCompact,
                        },
                    },
                },
            },
        });
    }

    if (transactionsCanvas) {
        const labels = JSON.parse(transactionsCanvas.dataset.labels || '[]');
        const values = JSON.parse(transactionsCanvas.dataset.values || '[]');

        new Chart(transactionsCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Transactions',
                        data: values,
                        backgroundColor: '#10853F',
                        borderRadius: 8,
                        maxBarThickness: 42,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${Number(context.parsed.y ?? 0).toLocaleString()} txns`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#5F6B63', font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 10000,
                        grid: { color: 'rgba(230, 235, 231, 1)' },
                        ticks: {
                            color: '#5F6B63',
                            font: { size: 11 },
                            callback: formatCompact,
                        },
                    },
                },
            },
        });
    }

    if (segmentsCanvas) {
        const labels = JSON.parse(segmentsCanvas.dataset.labels || '[]');
        const values = JSON.parse(segmentsCanvas.dataset.values || '[]');
        const colors = JSON.parse(segmentsCanvas.dataset.colors || '[]');

        new Chart(segmentsCanvas, {
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
                cutout: '62%',
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
        const regions = JSON.parse(mapEl.dataset.regions || '[]');
        const map = L.map(mapEl, {
            zoomControl: false,
            attributionControl: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
        }).setView([5, 20], 3.2);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 8,
        }).addTo(map);

        regions.forEach((region) => {
            const radius = 90000 + Number(region.score) * 2500;

            L.circle([region.lat, region.lng], {
                radius,
                color: regionColor(region.score),
                fillColor: regionColor(region.score),
                fillOpacity: 0.55,
                weight: 1,
            })
                .bindTooltip(`${region.name}: ${region.score}/100`, {
                    direction: 'top',
                    opacity: 0.95,
                })
                .addTo(map);
        });
    }
});
