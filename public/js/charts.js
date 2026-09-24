(function () {
    var charts = [];

    function css(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    function palette() {
        return {
            surface: css('--zk-surface'),
            text: css('--zk-text'),
            text2: css('--zk-text-2'),
            muted: css('--zk-muted'),
            grid: css('--viz-grid'),
            axis: css('--viz-axis'),
            present: css('--viz-present'),
            late: css('--viz-late'),
            leave: css('--viz-leave'),
            absent: css('--viz-absent'),
            blue: css('--viz-blue'),
            border: css('--zk-border'),
            font: css('--zk-font') || 'Inter, sans-serif'
        };
    }

    function baseOptions(p, stacked) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: p.surface,
                    titleColor: p.text,
                    bodyColor: p.text2,
                    borderColor: p.border,
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 4,
                    usePointStyle: true,
                    titleFont: { family: p.font, weight: '600' },
                    bodyFont: { family: p.font }
                }
            },
            scales: {
                x: {
                    stacked: stacked,
                    grid: { display: false },
                    border: { color: p.grid },
                    ticks: { color: p.axis, font: { family: p.font, size: 11 }, maxRotation: 0, autoSkipPadding: 12 }
                },
                y: {
                    stacked: stacked,
                    beginAtZero: true,
                    grid: { color: p.grid, lineWidth: 1 },
                    border: { display: false },
                    ticks: { color: p.axis, font: { family: p.font, size: 11 }, precision: 0, padding: 8 }
                }
            }
        };
    }

    function topOfStack(ctx) {
        var chart = ctx.chart;
        var index = ctx.dataIndex;
        for (var i = chart.data.datasets.length - 1; i >= 0; i--) {
            if (!chart.isDatasetVisible(i)) continue;
            if ((chart.data.datasets[i].data[index] || 0) > 0) return i === ctx.datasetIndex;
        }
        return false;
    }

    var endLabel = {
        id: 'zkEndLabel',
        afterDatasetsDraw: function (chart, args, opts) {
            if (!opts || !opts.format) return;
            var meta = chart.getDatasetMeta(0);
            var last = meta.data.length - 1;
            var bar = meta.data[last];
            var value = chart.data.datasets[0].data[last];
            if (!bar || !value) return;
            var p = palette();
            var c = chart.ctx;
            c.save();
            c.fillStyle = p.text;
            c.font = '600 11px ' + p.font;
            c.textAlign = 'center';
            c.fillText(opts.format(value), bar.x, bar.y - 8);
            c.restore();
        }
    };

    function stacked(el, data) {
        var p = palette();
        var series = [
            ['Present', 'present', p.present],
            ['Late', 'late', p.late],
            ['On leave', 'leave', p.leave],
            ['Not in / absent', 'absent', p.absent]
        ];

        return new Chart(el, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: series.map(function (s) {
                    return {
                        label: s[0],
                        data: data[s[1]],
                        backgroundColor: s[2],
                        borderColor: p.surface,
                        borderWidth: { top: 2, right: 0, bottom: 0, left: 0 },
                        borderSkipped: 'start',
                        borderRadius: function (ctx) { return topOfStack(ctx) ? { topLeft: 4, topRight: 4 } : 0; },
                        maxBarThickness: 24,
                        categoryPercentage: 0.7,
                        barPercentage: 0.9
                    };
                })
            },
            options: (function () {
                var o = baseOptions(p, true);
                o.plugins.tooltip.callbacks = {
                    title: function (items) { return data.dates[items[0].dataIndex]; },
                    footer: function (items) {
                        var total = items.reduce(function (sum, i) { return sum + i.raw; }, 0);
                        return total ? 'Total ' + total : '';
                    }
                };
                o.plugins.tooltip.footerColor = p.muted;
                return o;
            })()
        });
    }

    function columns(el, data) {
        var p = palette();
        return new Chart(el, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.label || '',
                    data: data.values,
                    backgroundColor: p.blue,
                    borderRadius: { topLeft: 4, topRight: 4 },
                    borderSkipped: 'start',
                    maxBarThickness: 24,
                    categoryPercentage: 0.6
                }]
            },
            plugins: [endLabel],
            options: (function () {
                var o = baseOptions(p, false);
                o.layout = { padding: { top: 18 } };
                o.plugins.zkEndLabel = { format: data.format };
                o.plugins.tooltip.callbacks = { label: function (item) { return ' ' + data.format(item.raw); } };
                o.scales.y.ticks.callback = function (v) { return data.compact(v); };
                return o;
            })()
        });
    }

    function render() {
        charts.forEach(function (c) { c.chart.destroy(); c.chart = c.build(); });
    }

    window.zkCharts = {
        stacked: function (el, data) {
            var entry = { build: function () { return stacked(el, data); } };
            entry.chart = entry.build();
            charts.push(entry);
        },
        columns: function (el, data) {
            var entry = { build: function () { return columns(el, data); } };
            entry.chart = entry.build();
            charts.push(entry);
        }
    };

    document.addEventListener('zk:theme', render);
})();
