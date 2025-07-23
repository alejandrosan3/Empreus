import { Loader } from '@googlemaps/js-api-loader';
import { MarkerClusterer } from '@googlemaps/markerclusterer';

// Generate `count` equally‑spaced HSL colors
function generateColors(count) {
  return Array.from({ length: count }, (_, i) => {
    const hue = Math.round((360 * i) / count);
    return `hsl(${hue}, 70%, 50%)`;
  });
}

// Wrap the geocoder callback in a Promise
function geocodeAddress(geocoder, address) {
  return new Promise((resolve) => {
    geocoder.geocode({ address }, (results, status) => {
      if (status === 'OK' && results[0]) {
        const loc = results[0].geometry.location;
        resolve({ lat: loc.lat(), lng: loc.lng() });
      } else {
        console.warn(`Geocode failed for "${address}": ${status}`);
        resolve(null);
      }
    });
  });
}

async function init() {
  try {
    console.log("🛠️ lot-map init()", {
      hasEl: !!document.getElementById("lot-map"),
      dataset: document.getElementById("lot-map")?.dataset,
    });

    const el = document.getElementById("lot-map");
    if (!el) {
      console.log("⏭️ no #lot-map container, skipping");
      return;
    }

    const raw = el.dataset.lots;
    const apiKey = el.dataset.googleKey;
    if (!raw || !apiKey) {
      console.log("⚠️ missing data-lots or data-google-key", { raw, apiKey });
      return;
    }

    const lotMapData = JSON.parse(raw);
    const loader = new Loader({ apiKey, version: 'weekly' });

    // Load Maps + Geocoding libraries
    const [{ Map }, { Geocoder }] = await Promise.all([
      loader.importLibrary('maps'),
      loader.importLibrary('geocoding'),
    ]);

    // Initialize map
    const map = new Map(el, {
      center: { lat: 37.0902, lng: -95.7129 },
      zoom: 4,
    });
    const geocoder = new Geocoder();

    // Build unique color palette for subdivisions
    const subdivisions = [...new Set(lotMapData.map(l => l.subdivision))];
    const colors = generateColors(subdivisions.length);
    const colorMap = subdivisions.reduce((m, sub, i) => {
      m[sub] = colors[i];
      return m;
    }, {});

    // Create markers
    const markers = await Promise.all(
      lotMapData.map(async (lot) => {
        const pos = await geocodeAddress(geocoder, lot.address);
        if (!pos) return null;

        return new window.google.maps.Marker({
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
      })
    ).then(arr => arr.filter(Boolean));

    // Cluster markers
    new MarkerClusterer({ markers, map });

    // Color the legend swatches
    document.querySelectorAll('[data-subdivision-color]').forEach(el => {
      const sub = el.getAttribute('data-subdivision-color');
      el.style.backgroundColor = colorMap[sub] || '#999';
    });

    // Legend toggle
    const legend = document.getElementById('lot-legend');
    const toggle = document.getElementById('toggle-legend');
    if (toggle && legend) {
      toggle.addEventListener('change', e => {
        legend.classList.toggle('hidden', !e.target.checked);
      });
    }

    console.log("✅ lot-map initialized with", markers.length, "markers");
  } catch (err) {
    console.error("❌ lot-map error:", err);
  }
}

// Initializers
window.addEventListener('DOMContentLoaded', init);
window.addEventListener('livewire:load', init);
window.addEventListener('livewire: initialized', init);
document.addEventListener('lot-map-added', init);

// Re‐init after every Livewire AJAX swap
if (window.Livewire) {
  window.Livewire.hook('message.processed', () => {
    console.log('🔄 livewire message.processed → re‑init lot‑map');
    init();
  });
}

export default init;