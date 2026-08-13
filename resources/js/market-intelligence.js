import Chart from 'chart.js/auto';

let maizePriceTrendChart = null;

/**
 * Format naira axis ticks compactly.
 */
function formatCompactNaira(value) {
    const numeric = Number(value);

    if (numeric >= 1000000) {
        return `${(numeric / 1000000).toFixed(1)}M`;
    }

    if (numeric >= 1000) {
        return `${Math.round(numeric / 1000)}K`;
    }

    return String(numeric);
}

/**
 * Create or update the price trend line chart for a selected range.
 *
 * @param {string} range
 * @param {Record<string, {labels: string[], values: number[]}>} series
 * @param {string} [commodityName]
 */
window.cyraUpdateMaizePriceTrend = (range, series, commodityName = 'Commodity') => {
    const canvas = document.getElementById('maizePriceTrendChart');

    if (! canvas || ! series?.[range]) {
        return;
    }

    const { labels, values } = series[range];
    const label = `${commodityName || canvas.dataset.commodity || 'Commodity'} price`;
    const min = Math.min(...values);
    const max = Math.max(...values);
    const pad = Math.max(1000, Math.round((max - min) * 0.15));

    if (maizePriceTrendChart) {
        maizePriceTrendChart.data.labels = labels;
        maizePriceTrendChart.data.datasets[0].data = values;
        maizePriceTrendChart.data.datasets[0].label = label;
        maizePriceTrendChart.options.scales.y.suggestedMin = Math.max(0, min - pad);
        maizePriceTrendChart.options.scales.y.suggestedMax = max + pad;
        maizePriceTrendChart.update();

        return;
    }

    maizePriceTrendChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label,
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
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
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
                    beginAtZero: false,
                    suggestedMin: Math.max(0, min - pad),
                    suggestedMax: max + pad,
                    grid: { color: 'rgba(230, 235, 231, 1)' },
                    ticks: {
                        color: '#5F6B63',
                        font: { size: 11 },
                        callback: formatCompactNaira,
                    },
                },
            },
        },
    });
};

/**
 * Initialize demand forecast bar chart.
 */
document.addEventListener('DOMContentLoaded', () => {
    const demandCanvas = document.getElementById('maizeDemandForecastChart');

    if (! demandCanvas) {
        return;
    }

    const labels = JSON.parse(demandCanvas.dataset.labels || '[]');
    const values = JSON.parse(demandCanvas.dataset.values || '[]');
    const commodity = demandCanvas.dataset.commodity || 'Commodity';
    const min = Math.min(...values.map(Number));
    const max = Math.max(...values.map(Number));
    const pad = Math.max(500, Math.round((max - min) * 0.15));

    new Chart(demandCanvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: `${commodity} demand`,
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
                        label: (context) => `${Number(context.parsed.y ?? 0).toLocaleString()} Tons`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#5F6B63', font: { size: 11 } },
                },
                y: {
                    beginAtZero: false,
                    suggestedMin: Math.max(0, min - pad),
                    suggestedMax: max + pad,
                    grid: { color: 'rgba(230, 235, 231, 1)' },
                    ticks: {
                        color: '#5F6B63',
                        font: { size: 11 },
                        callback: formatCompactNaira,
                    },
                },
            },
        },
    });
});
