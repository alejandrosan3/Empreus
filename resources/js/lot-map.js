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

    // Avoid double-initializing the same DOM node
    if (el.dataset.inited === '1') {
        console.log('⏭️ lot-map already initialized, skipping');
        return;
    }

    const loader = new Loader({ apiKey, version: 'weekly' });

    // Load Maps + Geocoding libraries
    const [{ Map }, { Geocoder }, { AdvancedMarkerElement, PinElement }] = await Promise.all([
      loader.importLibrary('maps'),
      loader.importLibrary('geocoding'),
      loader.importLibrary('marker'),
    ]);

      // Initialize map (needs a Map ID for Advanced Markers)
      const map = new Map(el, {
          center: { lat: 37.0902, lng: -95.7129 },
          zoom: 4,
          mapId: "DEMO_MAP_ID", // REAL: 249fa5ae398d0c11f927ef99
      });

    const geocoder = new Geocoder();

    // Build unique color palette for subdivisions
    const subdivisions = [...new Set(lotMapData.map(l => l.subdivision))].sort();
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

          return new AdvancedMarkerElement({
              position: pos,
              title: lot.address,
              content: new PinElement({
                  scale: 0.8,
                  background: colorMap[lot.subdivision],
                  borderColor: "#fff",
                  glyphColor: "#fff",
              }).element,
          });

      })
    ).then(arr => arr.filter(Boolean));

    // Cluster markers
    new MarkerClusterer({ markers, map });

      // Auto‑zoom/center map to fit all markers
     if (markers.length > 0) {
         const bounds = new google.maps.LatLngBounds();
         markers.forEach(m => bounds.extend(m.position));
         map.fitBounds(bounds);
         // optional: don't zoom in too much
         google.maps.event.addListenerOnce(map, 'idle', () => {
            if (map.getZoom() > 16) map.setZoom(16);
         });
     }

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

      // Mark as initialized for this node
      el.dataset.inited = '1';
      console.log("✅ lot-map initialized with", markers.length, "markers");
  } catch (err) {
    console.error("❌ lot-map error:", err);
  }
}

// Initializers
window.addEventListener('DOMContentLoaded', init);
window.addEventListener('livewire:load', init);
window.addEventListener('livewire:initialized', init);
document.addEventListener('lot-map-added', init);

// Re‐init after every Livewire AJAX swap
if (window.Livewire) {
  window.Livewire.hook('message.processed', () => {
    console.log('🔄 livewire message.processed → re‑init lot‑map');
    init();
  });
}

export default init;
