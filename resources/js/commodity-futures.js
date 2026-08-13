import Chart from 'chart.js/auto';

/**
 * Build candlestick-style floating bar datasets from OHLC rows.
 */
function candleDatasets(ohlc) {
    const wickColors = [];
    const bodyColors = [];
    const wicks = [];
    const bodies = [];

    ohlc.forEach((candle) => {
        const up = candle.c >= candle.o;
        const color = up ? '#10853F' : '#DC2626';

        wickColors.push(color);
        bodyColors.push(color);
        wicks.push([candle.l, candle.h]);
        bodies.push([Math.min(candle.o, candle.c), Math.max(candle.o, candle.c)]);
    });

    return [
        {
            label: 'Wick',
            data: wicks,
            backgroundColor: wickColors,
            borderWidth: 0,
            barPercentage: 0.12,
            categoryPercentage: 0.8,
            order: 2,
        },
        {
            label: 'Body',
            data: bodies,
            backgroundColor: bodyColors,
            borderWidth: 0,
            barPercentage: 0.55,
            categoryPercentage: 0.8,
            order: 1,
        },
    ];
}

/**
 * Initialize commodity futures candlestick chart with range switching.
 */
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('futuresCandleChart');

    if (! canvas) {
        return;
    }

    const candlesByRange = JSON.parse(canvas.dataset.candles || '{}');
    let activeRange = canvas.dataset.defaultRange || '1D';
    let chart;

    const render = (range) => {
        const pack = candlesByRange[range] || candlesByRange['1D'];
        const labels = pack?.labels || [];
        const ohlc = pack?.ohlc || [];

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: candleDatasets(ohlc),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const candle = ohlc[context.dataIndex];

                                if (! candle) {
                                    return '';
                                }

                                if (context.dataset.label === 'Wick') {
                                    return `H ₦${candle.h.toLocaleString()} · L ₦${candle.l.toLocaleString()}`;
                                }

                                return `O ₦${candle.o.toLocaleString()} · C ₦${candle.c.toLocaleString()}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        stacked: false,
                        grid: { display: false },
                        ticks: { color: '#6B7280', font: { size: 11 } },
                    },
                    y: {
                        grid: { color: 'rgba(230, 235, 231, 1)' },
                        ticks: {
                            color: '#6B7280',
                            font: { size: 11 },
                            callback: (value) => {
                                const numeric = Number(value);

                                if (numeric >= 1000) {
                                    return `${Math.round(numeric / 1000)}K`;
                                }

                                return String(numeric);
                            },
                        },
                    },
                },
            },
        });
    };

    render(activeRange);

    document.querySelectorAll('[data-futures-range]').forEach((button) => {
        button.addEventListener('click', () => {
            activeRange = button.getAttribute('data-futures-range') || '1D';
            render(activeRange);

            document.querySelectorAll('[data-futures-range]').forEach((el) => {
                el.classList.toggle('text-cyra-forest', el === button);
                el.classList.toggle('text-cyra-muted', el !== button);
                el.classList.toggle('hover:text-cyra-ink', el !== button);
            });
        });
    });
});
