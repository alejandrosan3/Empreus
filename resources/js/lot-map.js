import { Loader } from '@googlemaps/js-api-loader';
import { MarkerClusterer } from '@googlemaps/markerclusterer';

const colors = [
  '#dc2626', '#2563eb', '#16a34a', '#fb923c', '#9333ea', '#059669', '#d97706'
];

function getColor(index) {
  return colors[index % colors.length];
}

async function init() {
  console.log('🗺️ init fired', window.lotMapData, window.googleMapsKey);
  if (!window.lotMapData || !window.googleMapsKey) return;

  const loader = new Loader({
    apiKey: window.googleMapsKey,
    version: 'weekly',
  });

  // Dynamically load Maps and Geocoding libraries
  const [{ Map }, { Geocoder }] = await Promise.all([
    loader.importLibrary('maps'),
    loader.importLibrary('geocoding'),
  ]);

  // Initialize map
  const center = { lat: 37.0902, lng: -95.7129 };
  const map = new Map(
    document.getElementById('lot-map'),
    { center, zoom: 4 }
  );

  const geocoder = new Geocoder();
  const markers = [];
  const colorMap = {};

  // Helper: geocode an address
  const geocode = (lot) =>
    new Promise((resolve) => {
      geocoder.geocode({ address: lot.address }, (results, status) => {
        if (status === 'OK' && results[0]) {
          const { lat, lng } = results[0].geometry.location;
          resolve({ lat: lat(), lng: lng() });
        } else {
          console.warn(`Geocode failed for ${lot.address}: ${status}`);
          resolve(null);
        }
      });
    });

  // Create markers for each lot
  await Promise.all(
    window.lotMapData.map(async (lot) => {
      const pos = await geocode(lot);
      if (!pos) return;

      if (!(lot.subdivision in colorMap)) {
        colorMap[lot.subdivision] = getColor(Object.keys(colorMap).length);
      }

      const marker = new window.google.maps.Marker({
        position: pos,
        title: lot.address,
        icon: {
          path: window.google.maps.SymbolPath.CIRCLE,
          scale: 8,
          fillColor: colorMap[lot.subdivision],
          fillOpacity: 1,
          strokeColor: '#fff',
          strokeWeight: 1,
        },
      });
      markers.push(marker);
    })
  );

  // Cluster markers
  new MarkerClusterer({ markers, map });

  // Apply legend colors
  document.querySelectorAll('[data-subdivision-color]').forEach((el) => {
    const name = el.getAttribute('data-subdivision-color');
    el.style.backgroundColor = colorMap[name] || '#999';
  });

  // Toggle legend visibility
  const legend = document.getElementById('lot-legend');
  document.getElementById('toggle-legend').addEventListener('change', (e) => {
    legend.classList.toggle('hidden', !e.target.checked);
  });
}

// Bind init to both Livewire and DOMContentLoaded
window.addEventListener('livewire:load', init);
document.addEventListener('DOMContentLoaded', init);

export default init;