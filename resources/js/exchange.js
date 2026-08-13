import Chart from 'chart.js/auto';

/**
 * Initialize commodity exchange price chart.
 */
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('exchangePriceChart');

    if (! canvas) {
        return;
    }

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Price',
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
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#10853F',
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
                        label: (context) => {
                            const value = context.parsed.y ?? 0;

                            return `₦${Number(value).toLocaleString()}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#6B7280', maxTicksLimit: 6 },
                },
                y: {
                    grid: { color: 'rgba(230, 235, 231, 1)' },
                    ticks: {
                        color: '#6B7280',
                        callback: (value) => {
                            const numeric = Number(value);

                            if (numeric >= 1000000) {
                                return `${(numeric / 1000000).toFixed(1)}M`;
                            }

                            if (numeric >= 1000) {
                                return `${Math.round(numeric / 1000)}K`;
                            }

                            return `${numeric}`;
                        },
                    },
                },
            },
        },
    });
});
