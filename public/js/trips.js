// Decode Google-style encoded polyline to array of [lat, lng]
function decodePolyline(encoded) {
    var points = [];
    var lat = 0, lng = 0;
    var i = 0;
    while (i < encoded.length) {
        var shift = 0, result = 0, byte;
        do {
            byte = encoded.charCodeAt(i++) - 63;
            result |= (byte & 0x1f) << shift;
            shift += 5;
        } while (byte >= 0x20);
        lat += (result & 1) ? ~(result >> 1) : (result >> 1);

        shift = 0; result = 0;
        do {
            byte = encoded.charCodeAt(i++) - 63;
            result |= (byte & 0x1f) << shift;
            shift += 5;
        } while (byte >= 0x20);
        lng += (result & 1) ? ~(result >> 1) : (result >> 1);

        points.push([lat / 1e5, lng / 1e5]);
    }
    return points;
}

// Trip route map
function initTripMap(el, stops, segments) {
    if (!stops || stops.length === 0) return;

    var map = L.map(el);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    var bounds = L.latLngBounds();

    stops.forEach(function(stop, i) {
        var marker = L.marker([stop.lat, stop.lng]).addTo(map);
        var title = document.createElement('strong');
        title.textContent = (i + 1) + '. ' + stop.name;
        marker.bindPopup(title);
        bounds.extend([stop.lat, stop.lng]);
    });

    // Draw route segments with real geometry if available
    var hasGeometry = false;
    if (segments && segments.length > 0) {
        segments.forEach(function(seg) {
            if (seg.geometry) {
                var decoded = decodePolyline(seg.geometry);
                var line = L.polyline(decoded, {
                    color: '#2C5F6A',
                    weight: 4,
                    opacity: 0.85
                }).addTo(map);
                bounds.extend(line.getBounds());
                hasGeometry = true;
            }
        });
    }

    // Fallback: dashed straight lines if no geometry
    if (!hasGeometry && stops.length >= 2) {
        var coords = stops.map(function(s) { return [s.lat, s.lng]; });
        L.polyline(coords, {
            color: '#3D7A87',
            weight: 3,
            opacity: 0.8,
            dashArray: '8 4'
        }).addTo(map);
    }

    map.fitBounds(bounds, { padding: [30, 30] });
}

// Stop reorder via drag (simple implementation)
document.addEventListener('DOMContentLoaded', function() {
    var list = document.getElementById('stop-list');
    if (!list) return;

    var dragItem = null;

    list.querySelectorAll('.stop-card').forEach(function(card) {
        card.draggable = true;

        card.addEventListener('dragstart', function(e) {
            dragItem = this;
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', function() {
            this.style.opacity = '1';
            dragItem = null;
        });

        card.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        card.addEventListener('drop', function(e) {
            e.preventDefault();
            if (dragItem && dragItem !== this) {
                var cards = Array.from(list.querySelectorAll('.stop-card'));
                var fromIndex = cards.indexOf(dragItem);
                var toIndex = cards.indexOf(this);

                if (fromIndex < toIndex) {
                    this.parentNode.insertBefore(dragItem, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(dragItem, this);
                }

                saveStopOrder();
            }
        });
    });

    function saveStopOrder() {
        var cards = list.querySelectorAll('.stop-card');
        var stopIds = Array.from(cards).map(function(c) { return parseInt(c.dataset.stopId); });

        // Get trip slug from URL
        var slug = window.location.pathname.split('/resor/')[1];
        if (slug) slug = slug.split('/')[0];

        fetch('/adm/resor/' + slug + '/hallplatser/ordning', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify({ stop_ids: stopIds })
        });
    }
});
