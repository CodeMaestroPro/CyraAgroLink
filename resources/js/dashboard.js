import Chart from 'chart.js/auto';

/**
 * Initialize farmer dashboard earnings chart when the canvas is present.
 */
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('earningsChart');

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
                    label: 'Earnings',
                    data: values,
                    borderColor: '#10853F',
                    backgroundColor: 'rgba(16, 133, 63, 0.12)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
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
                    ticks: { color: '#5F6B63', font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(230, 235, 231, 1)' },
                    ticks: {
                        color: '#5F6B63',
                        font: { size: 11 },
                        callback: (value) => {
                            const numeric = Number(value);
                            if (numeric >= 1000000) {
                                return `${(numeric / 1000000).toFixed(0)}M`;
                            }
                            if (numeric >= 1000) {
                                return `${(numeric / 1000).toFixed(0)}K`;
                            }
                            return String(numeric);
                        },
                    },
                },
            },
        },
    });
});
