// Charts and map for the Statistics page.
// Pies/bars use Chart.js; the map uses Leaflet with free OpenStreetMap tiles
// and only coordinates already saved on the lots (no paid map quota).
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const charts = {};
let map = null;
let subdivisionLayer = null;
let lotLayer = null;
let lastData = null;

const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
const count = new Intl.NumberFormat('en-US');

function root() {
    return document.querySelector('.stats-root');
}

function cssVar(name) {
    return getComputedStyle(root()).getPropertyValue(name).trim();
}

function slotColor(slot) {
    return cssVar(`--series-${slot ?? 'other'}`) || '#999';
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function readInitialData() {
    const el = document.getElementById('statistics-data');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent);
    } catch (e) {
        console.error('statistics: bad initial data', e);
        return null;
    }
}

function renderPie(id, pie) {
    const canvas = document.getElementById(`pie-${id}`);
    if (!canvas) return;

    const wrap = canvas.closest('[data-pie-wrap]');
    wrap?.classList.toggle('hidden', pie.slices.length === 0);

    const total = wrap?.querySelector('[data-pie-total]');
    if (total) total.textContent = pie.money ? money.format(pie.total) : count.format(pie.total);

    const data = {
        labels: pie.slices.map(s => s.label),
        datasets: [{
            data: pie.slices.map(s => s.value),
            backgroundColor: pie.slices.map(s => slotColor(s.slot)),
            borderColor: cssVar('--chart-surface'),
            borderWidth: 2,
            hoverOffset: 6,
        }],
    };

    if (charts[id]) {
        charts[id].data = data;
        charts[id].$slices = pie.slices;
        charts[id].$money = pie.money;
        charts[id].update();
        return;
    }

    const chart = new Chart(canvas, {
        type: 'doughnut',
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            animation: { duration: 250 },
            plugins: {
                legend: { display: false }, // the list under the pie is the legend
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const slice = ctx.chart.$slices[ctx.dataIndex];
                            const value = ctx.chart.$money ? money.format(slice.value) : count.format(slice.value);
                            return ` ${value} (${slice.percent}%)`;
                        },
                    },
                },
            },
        },
    });
    chart.$slices = pie.slices;
    chart.$money = pie.money;
    charts[id] = chart;
}

function renderTimeline(timeline) {
    const canvas = document.getElementById('chart-timeline');
    if (!canvas) return;

    const ink = cssVar('--ink-muted');
    const data = {
        labels: timeline.buckets.map(b => b.label),
        datasets: [
            { label: 'Money earned', data: timeline.buckets.map(b => b.earned), backgroundColor: slotColor(1), borderRadius: 4, maxBarThickness: 28 },
            { label: 'Paid to crews', data: timeline.buckets.map(b => b.paid), backgroundColor: slotColor(2), borderRadius: 4, maxBarThickness: 28 },
        ],
    };

    if (charts.timeline) {
        charts.timeline.data = data;
        charts.timeline.update();
        return;
    }

    charts.timeline = new Chart(canvas, {
        type: 'bar',
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 250 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${money.format(ctx.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: ink, maxRotation: 0, autoSkip: true } },
                y: {
                    beginAtZero: true,
                    grid: { color: cssVar('--grid') },
                    border: { display: false },
                    ticks: { color: ink, callback: v => money.format(v) },
                },
            },
        },
    });
}

function renderMap(mapData, builderColors) {
    const el = document.getElementById('stats-map');
    if (!el) return;

    if (!map) {
        map = L.map(el, { scrollWheelZoom: false }).setView([37.0902, -95.7129], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
        map.on('focus', () => map.scrollWheelZoom.enable());
        map.on('blur', () => map.scrollWheelZoom.disable());

        subdivisionLayer = L.layerGroup().addTo(map);
        lotLayer = L.layerGroup();

        document.getElementById('stats-map-lots')?.addEventListener('change', e => {
            e.target.checked ? lotLayer.addTo(map) : lotLayer.remove();
        });
    }

    subdivisionLayer.clearLayers();
    lotLayer.clearLayers();

    const colorFor = id => slotColor(builderColors[id] ?? 'other');
    const maxLots = Math.max(1, ...mapData.subdivisions.map(s => s.lots));
    const bounds = [];

    mapData.subdivisions.forEach(s => {
        const radius = 8 + 22 * Math.sqrt(s.lots / maxLots);
        L.circleMarker([s.lat, s.lng], {
            radius,
            color: cssVar('--chart-surface'),
            weight: 2,
            fillColor: colorFor(s.builder_id),
            fillOpacity: 0.8,
        })
            .bindTooltip(escapeHtml(s.name))
            .bindPopup(`
                <strong>${escapeHtml(s.name)}</strong><br>
                ${escapeHtml(s.builder)}<br>
                ${count.format(s.lots)} lots · ${count.format(s.tasks_done)} jobs finished<br>
                Earned ${money.format(s.earned)}
            `)
            .addTo(subdivisionLayer);
        bounds.push([s.lat, s.lng]);
    });

    mapData.lots.forEach(l => {
        L.circleMarker([l.lat, l.lng], {
            radius: 4,
            weight: 1,
            color: cssVar('--chart-surface'),
            fillColor: colorFor(l.builder_id),
            fillOpacity: 1,
        })
            .bindTooltip(`${escapeHtml(l.address)}<br>${escapeHtml(l.subdivision)}`)
            .addTo(lotLayer);
    });

    if (bounds.length) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
    }
    // The container may have been laid out after Leaflet measured it.
    setTimeout(() => map.invalidateSize(), 0);
}

function render(data) {
    if (!data || !root()) return;
    lastData = data;
    Object.entries(data.pies).forEach(([id, pie]) => renderPie(id, pie));
    renderTimeline(data.timeline);
    renderMap(data.map, data.builderColors);
}

function init() {
    if (!document.getElementById('statistics-data') || root()?.dataset.inited === '1') return;
    root().dataset.inited = '1';
    render(readInitialData());
}

// Livewire 3 sends `statistics-updated` after every filter change.
window.addEventListener('statistics-updated', e => render(e.detail?.data));

// Repaint charts with the right palette when the light/dark toggle flips.
new MutationObserver(() => {
    if (!root() || !lastData) return;
    Object.values(charts).forEach(c => c.destroy());
    Object.keys(charts).forEach(k => delete charts[k]);
    render(lastData);
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
document.addEventListener('livewire:navigated', init);
