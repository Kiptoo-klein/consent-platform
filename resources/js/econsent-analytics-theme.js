const chartLibrary = window.Chart ?? null;


const analyticsPath = '/signing-stations/analytics';

function findDailyActivityPanel(canvas) {
    let element = canvas.parentElement;
    let bestMatch = null;

    while (element && element !== document.body) {
        const text = (element.textContent || '')
            .replace(/\s+/g, ' ')
            .trim();

        if (/daily activity/i.test(text)) {
            bestMatch = element;

            if (
                element.matches(
                    'section, article, .bg-white, .rounded-lg, .rounded-xl, .rounded-2xl'
                )
            ) {
                break;
            }
        }

        element = element.parentElement;
    }

    return bestMatch;
}

function markDailyActivityPanel(canvas) {
    const panel = findDailyActivityPanel(canvas);

    if (!panel) {
        return null;
    }

    panel.classList.add('ec-daily-activity-panel');

    const surface =
        canvas.parentElement?.closest(
            '.relative, .overflow-hidden, .chart-container'
        )
        || canvas.parentElement;

    if (surface && surface !== panel) {
        surface.classList.add('ec-daily-activity-chart-surface');
    } else {
        canvas.parentElement?.classList.add(
            'ec-daily-activity-chart-surface'
        );
    }

    return panel;
}

function getChartInstances(canvas) {
    const instances = [];

    if (!chartLibrary) {
        return instances;
    }

    if (typeof chartLibrary.getChart === 'function') {
        const chart = chartLibrary.getChart(canvas);

        if (chart) {
            instances.push(chart);
        }
    }

    if (chartLibrary.instances) {
        Object.values(chartLibrary.instances).forEach((chart) => {
            if (
                chart
                && chart.canvas === canvas
                && !instances.includes(chart)
            ) {
                instances.push(chart);
            }
        });
    }

    return instances;
}

function styleChartJs(chart) {
    if (!chart?.data?.datasets) {
        return;
    }

    const chartType = chart.config?.type || '';

    chart.data.datasets.forEach((dataset, index) => {
        const datasetType = dataset.type || chartType;

        if (datasetType === 'line') {
            dataset.borderColor = '#0f766e';
            dataset.backgroundColor = 'rgba(15, 118, 110, 0.12)';
            dataset.pointBackgroundColor = '#0f766e';
            dataset.pointBorderColor = '#ffffff';
            dataset.pointHoverBackgroundColor = '#115e59';
            dataset.pointHoverBorderColor = '#ffffff';
            dataset.pointRadius = 3;
            dataset.pointHoverRadius = 5;
            dataset.borderWidth = 2.5;
            dataset.tension = 0.35;
        }

        if (datasetType === 'bar') {
            dataset.backgroundColor =
                index === 0
                    ? 'rgba(15, 118, 110, 0.78)'
                    : 'rgba(212, 167, 44, 0.72)';

            dataset.borderColor =
                index === 0 ? '#0f766e' : '#b88913';

            dataset.borderWidth = 1;
            dataset.borderRadius = 5;
        }
    });

    chart.options ??= {};
    chart.options.plugins ??= {};
    chart.options.plugins.legend ??= {};
    chart.options.plugins.legend.labels ??= {};
    chart.options.plugins.legend.labels.color = '#52525b';
    chart.options.plugins.legend.labels.font = {
        ...(chart.options.plugins.legend.labels.font || {}),
        weight: '500',
    };

    if (chart.options.scales) {
        Object.values(chart.options.scales).forEach((scale) => {
            if (!scale || typeof scale !== 'object') {
                return;
            }

            scale.grid ??= {};
            scale.grid.color = 'rgba(82, 82, 91, 0.12)';
            scale.grid.drawBorder = false;

            scale.border ??= {};
            scale.border.color = '#cfd8d4';

            scale.ticks ??= {};
            scale.ticks.color = '#52525b';
            scale.ticks.font = {
                ...(scale.ticks.font || {}),
                weight: '500',
            };

            scale.title ??= {};
            scale.title.color = '#3f3f46';
        });
    }

    /*
     * Chart.js 2 compatibility.
     */
    ['xAxes', 'yAxes'].forEach((axisName) => {
        const axes = chart.options?.scales?.[axisName];

        if (!Array.isArray(axes)) {
            return;
        }

        axes.forEach((axis) => {
            axis.gridLines ??= {};
            axis.gridLines.color = 'rgba(82, 82, 91, 0.12)';
            axis.gridLines.zeroLineColor = '#cfd8d4';

            axis.ticks ??= {};
            axis.ticks.fontColor = '#52525b';
        });
    });

    try {
        chart.update('none');
    } catch {
        try {
            chart.update(0);
        } catch {
            chart.update();
        }
    }
}

function themeDailyActivityGraph() {
    if (window.location.pathname !== analyticsPath) {
        return;
    }

    document.querySelectorAll('canvas').forEach((canvas) => {
        const panel = markDailyActivityPanel(canvas);

        if (!panel) {
            return;
        }

        getChartInstances(canvas).forEach(styleChartJs);
    });

    /*
     * Support SVG-based charts even when there is no canvas.
     */
    document
        .querySelectorAll('main section, main article, main div')
        .forEach((element) => {
            const text = (element.textContent || '')
                .replace(/\s+/g, ' ')
                .trim();

            if (
                /daily activity/i.test(text)
                && element.querySelector('svg, canvas')
            ) {
                element.classList.add('ec-daily-activity-panel');

                const chartElement = element.querySelector(
                    '.apexcharts-canvas, svg, canvas'
                );

                chartElement?.parentElement?.classList.add(
                    'ec-daily-activity-chart-surface'
                );
            }
        });
}

function initializeAnalyticsTheme() {
    if (window.location.pathname !== analyticsPath) {
        return;
    }

    /*
     * Apply Chart.js defaults when the library is available.
     */
    if (chartLibrary?.defaults) {
        chartLibrary.defaults.color = '#52525b';
        chartLibrary.defaults.borderColor =
            'rgba(82, 82, 91, 0.12)';
    }

    themeDailyActivityGraph();

    let attempts = 0;

    const timer = window.setInterval(() => {
        attempts += 1;
        themeDailyActivityGraph();

        if (attempts >= 20) {
            window.clearInterval(timer);
        }
    }, 500);
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeAnalyticsTheme
    );
} else {
    initializeAnalyticsTheme();
}
