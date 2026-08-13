import Chart from 'chart.js/auto';

/**
 * Initialize Business Intelligence Command Center charts.
 */
document.addEventListener('DOMContentLoaded', () => {
    const revenueCanvas = document.getElementById('biRevenueTrendChart');
    const commoditiesCanvas = document.getElementById('biCommoditiesChart');

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
                        tension: 0.35,
                        fill: true,
                        pointRadius: 5,
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
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6B7280', font: { size: 11, weight: '600' } },
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
                        grid: { color: 'rgba(230, 235, 231, 1)' },
                        ticks: {
                            color: '#6B7280',
                            font: { size: 11 },
                            stepSize: 50,
                        },
                    },
                },
            },
        });
    }

    if (commoditiesCanvas) {
        const labels = JSON.parse(commoditiesCanvas.dataset.labels || '[]');
        const values = JSON.parse(commoditiesCanvas.dataset.values || '[]');
        const colors = JSON.parse(commoditiesCanvas.dataset.colors || '[]');

        new Chart(commoditiesCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 0,
                        hoverOffset: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                },
            },
        });
    }
});
