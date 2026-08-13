import Chart from 'chart.js/auto';

/**
 * Initialize enterprise admin platform charts when canvases are present.
 */
document.addEventListener('DOMContentLoaded', () => {
    const distributionCanvas = document.getElementById('adminDistributionChart');
    const activityCanvas = document.getElementById('adminActivityChart');

    if (distributionCanvas) {
        const labels = JSON.parse(distributionCanvas.dataset.labels || '[]');
        const values = JSON.parse(distributionCanvas.dataset.values || '[]');
        const colors = JSON.parse(distributionCanvas.dataset.colors || '[]');

        new Chart(distributionCanvas, {
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
                cutout: '55%',
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

    if (activityCanvas) {
        const labels = JSON.parse(activityCanvas.dataset.labels || '[]');
        const values = JSON.parse(activityCanvas.dataset.values || '[]');

        new Chart(activityCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Activity',
                        data: values,
                        borderColor: '#10853F',
                        backgroundColor: 'rgba(16, 133, 63, 0.08)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: false,
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
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#5F6B63', font: { size: 10 }, maxRotation: 0 },
                    },
                    y: {
                        min: 1,
                        max: 5,
                        grid: { color: 'rgba(230, 235, 231, 1)' },
                        ticks: {
                            stepSize: 1,
                            color: '#5F6B63',
                            font: { size: 11 },
                        },
                    },
                },
            },
        });
    }
});
