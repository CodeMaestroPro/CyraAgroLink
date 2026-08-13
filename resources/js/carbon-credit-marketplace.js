import Chart from 'chart.js/auto';

/**
 * Initialize carbon credits trend chart when the canvas is present.
 */
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('carbonCreditsTrendChart');

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
                    label: 'Credits',
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
                    pointRadius: 4,
                    pointHoverRadius: 6,
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
                        label: (context) => `${context.parsed.y ?? 0} tCO2e`,
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
                    grid: { display: false },
                    ticks: {
                        color: '#5F6B63',
                        font: { size: 11 },
                    },
                },
            },
        },
    });
});
