import { Loader } from '@googlemaps/js-api-loader';
import { MarkerClusterer } from '@googlemaps/markerclusterer';

const colors = [
    '#dc2626', '#2563eb', '#16a34a', '#fb923c', '#9333ea', '#059669', '#d97706'
];

function getColor(index) {
    return colors[index % colors.length];
}

function init() {
    if (!window.lotMapData) return;
    const loader = new Loader({
        apiKey: window.googleMapsKey ?? '',
        version: 'weekly'
    });

    loader.load().then(() => {
        const center = { lat: 37.0902, lng: -95.7129 };
        const map = new google.maps.Map(document.getElementById('lot-map'), {
            center, zoom: 4
        });
        const geocoder = new google.maps.Geocoder();
        const markers = [];
        const colorMap = {};

        const geocode = (lot) => new Promise((resolve) => {
            geocoder.geocode({ address: lot.address }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const { lat, lng } = results[0].geometry.location;
                    resolve({ lat: lat(), lng: lng() });
                } else {
                    resolve(null);
                }
            });
        });

        Promise.all(window.lotMapData.map(async (lot) => {
            const pos = await geocode(lot);
            if (!pos) return;
            const index = Object.keys(colorMap).indexOf(lot.subdivision);
            if (index === -1) {
                colorMap[lot.subdivision] = getColor(Object.keys(colorMap).length);
            }
            const marker = new google.maps.Marker({
                position: pos,
                title: lot.address,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: colorMap[lot.subdivision],
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 1
                }
            });
            markers.push(marker);
        })).then(() => {
            new MarkerClusterer({ markers, map });
            // apply legend colors
            document.querySelectorAll('[data-subdivision-color]').forEach(el => {
                const name = el.getAttribute('data-subdivision-color');
                el.style.backgroundColor = colorMap[name] ?? '#999';
            });
        });

        const legend = document.getElementById('lot-legend');
        document.getElementById('toggle-legend').addEventListener('change', (e) => {
            legend.classList.toggle('hidden', !e.target.checked);
        });
    });
}

document.addEventListener('DOMContentLoaded', init);
export default init;
