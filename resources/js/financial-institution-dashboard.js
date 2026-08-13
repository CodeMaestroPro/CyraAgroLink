import Chart from 'chart.js/auto';

/**
 * Initialize financial institution portfolio charts when canvases are present.
 */
document.addEventListener('DOMContentLoaded', () => {
    const portfolioCanvas = document.getElementById('fiPortfolioChart');
    const repaymentCanvas = document.getElementById('fiRepaymentChart');

    if (portfolioCanvas) {
        const labels = JSON.parse(portfolioCanvas.dataset.labels || '[]');
        const values = JSON.parse(portfolioCanvas.dataset.values || '[]');
        const colors = JSON.parse(portfolioCanvas.dataset.colors || '[]');

        new Chart(portfolioCanvas, {
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

    if (repaymentCanvas) {
        const labels = JSON.parse(repaymentCanvas.dataset.labels || '[]');
        const values = JSON.parse(repaymentCanvas.dataset.values || '[]');

        new Chart(repaymentCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Repayment',
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
                        pointRadius: 0,
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
                            label: (context) => `${context.parsed.y ?? 0}%`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#5F6B63', font: { size: 11 } },
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: { color: 'rgba(230, 235, 231, 1)' },
                        ticks: {
                            color: '#5F6B63',
                            font: { size: 11 },
                            callback: (value) => `${value}%`,
                        },
                    },
                },
            },
        });
    }
});
