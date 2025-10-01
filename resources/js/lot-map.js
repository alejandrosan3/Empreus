import { Loader } from '@googlemaps/js-api-loader';
import { MarkerClusterer } from '@googlemaps/markerclusterer';

const postedLots = new Set();    // finished saves
const savingLots = new Set();    // in-flight saves
const JUNK_ADDRESSES = new Set([
    'could not determine address',
    'n/a', 'na', 'none', 'unknown', 'tbd', 'tba', 'null', '0', '-'
]);

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
          // this would happen when you have a wrong address that Google Maps could not figure out.
        console.warn(`Geocode failed for "${address}": ${status}`);
        resolve(null);
      }
    });
  });
}

function cleanAddr(a) {
    return (a ?? '').toString().trim();
}

function isGeocodable(a) {
    const s = cleanAddr(a);
    if (!s) return false;                  // empty/whitespace
    if (JUNK_ADDRESSES.has(s.toLowerCase())) return false; // placeholders
    return s.length >= 5;                  // basic length sanity
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

    // Avoids double-initializing the same DOM node
    if (el.dataset.inited === '1') {
        console.log('⏭️ lot-map already initialized, skipping');
        return;
    }

    const loader = new Loader({ apiKey, version: 'weekly' });

    // Load Maps and Geocoding libraries
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

    // Build a unique color palette for subdivisions
    const subdivisions = [...new Set(lotMapData.map(l => l.subdivision))].sort();
    const colors = generateColors(subdivisions.length);
    const colorMap = subdivisions.reduce((m, sub, i) => {
      m[sub] = colors[i];
      return m;
    }, {});

    const markersBySubdivision = {};
    // Create markers
    const markers = await Promise.all(
      lotMapData.map(async (lot) => {
          const pos = await resolvePosition(lot, geocoder);
          if (!pos) {
              console.log('⏭️ skipping (no coords / badly formatted address):', lot.id, lot.address);
              return null;
          }

          const marker = new AdvancedMarkerElement({
              position: pos,
              title: lot.address,
              content: new PinElement({
                  scale: 0.8,
                  background: colorMap[lot.subdivision],
                  borderColor: "#fff",
                  glyphColor: "#fff",
              }).element,
          });

          if (!markersBySubdivision[lot.subdivision]) {
              markersBySubdivision[lot.subdivision] = [];
          }
          markersBySubdivision[lot.subdivision].push(marker);

          return marker;

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

    // Legend item click -> zoom to subdivision
    document.querySelectorAll('[data-subdivision-legend]').forEach(el => {
      el.addEventListener('click', () => {
          const sub = el.getAttribute('data-subdivision-legend');
          const subset = markersBySubdivision[sub];
          if (subset && subset.length) {
              const bounds = new google.maps.LatLngBounds();
              subset.forEach(m => bounds.extend(m.position));
              map.fitBounds(bounds);
              if (map.getZoom() > 16) map.setZoom(16);
          }
      });
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

async function saveCoords(lotId, pos) {
    try {
        if (!lotId) return;
        if (postedLots.has(lotId) || savingLots.has(lotId)) return;

        savingLots.add(lotId);

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            console.warn('⚠️ Missing CSRF token <meta>');
            savingLots.delete(lotId);
            return;
        }

        const res = await fetch(`/lots/${lotId}/coords`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                latitude:  pos.lat.toFixed(7),
                longitude: pos.lng.toFixed(7),
            }).toString(),
            credentials: 'same-origin',
        });

        if (res.ok) {
            postedLots.add(lotId);
            console.log(`💾 Saved coords for lot ${lotId}`);
        } else {
            console.warn(`❌ Save coords failed (${res.status}) for lot ${lotId}`);
        }
    } catch (e) {
        console.error('❌ Save coords error:', e);
    } finally {
        savingLots.delete(lotId);
    }
}
async function resolvePosition(lot, geocoder) {
    // 1) Use DB coords first
    if (lot.lat != null && lot.lng != null) {
        console.log(`✅ using DB coords for lot ${lot.id}`, {
            lat: lot.lat,
            lng: lot.lng,
            address: lot.address,
        });
        return { lat: Number(lot.lat), lng: Number(lot.lng) };
    }

    // 2) If no coords, only geocode valid addresses
    if (!isGeocodable?.(lot.address)) return null;

    const query = cleanAddr(lot.address); // <-- modify this in case you want to set defaults cities or something.
    const pos = await geocodeAddress(geocoder, query);

    // 3) Persist once we find them
    if (pos && lot.id && typeof saveCoords === 'function') {
        saveCoords(lot.id, pos);
    }

    return pos;
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

// Expose init function globally for dynamic calls
window.initLotMap = init;

export default init;
