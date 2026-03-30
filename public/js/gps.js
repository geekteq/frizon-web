// GPS capture for place creation
function initGpsCapture(mapId, latId, lngId) {
    var mapEl = document.getElementById(mapId);
    var latInput = document.getElementById(latId);
    var lngInput = document.getElementById(lngId);

    if (!mapEl || !navigator.geolocation) {
        if (mapEl) mapEl.innerHTML = '<p style="padding:1rem; text-align:center; color:#4A6070;">GPS är inte tillgänglig.</p>';
        return;
    }

    var map = L.map(mapId).setView([59.33, 18.07], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    var marker = null;

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;

            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);

            map.setView([lat, lng], 15);
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);

            marker.on('dragend', function() {
                var p = marker.getLatLng();
                latInput.value = p.lat.toFixed(7);
                lngInput.value = p.lng.toFixed(7);
            });

            // Check for nearby places
            fetch('/api/platser/nearby?lat=' + lat + '&lng=' + lng)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.places && data.places.length > 0) {
                        var p = data.places[0];
                        var dist = Math.round(p.distance_meters);
                        if (confirm('Det ser ut som att du är vid ' + p.name + ' (' + dist + ' m bort).\n\nSkapa ett nytt besök istället?')) {
                            window.location.href = '/platser/' + p.slug + '/besok/nytt';
                        }
                    }
                });
        },
        function(err) {
            mapEl.innerHTML = '<p style="padding:1rem; text-align:center; color:#4A6070;">Kunde inte hämta position. Ange koordinater manuellt.</p>';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );

    map.on('click', function(e) {
        latInput.value = e.latlng.lat.toFixed(7);
        lngInput.value = e.latlng.lng.toFixed(7);
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { draggable: true }).addTo(map);
            marker.on('dragend', function() {
                var p = marker.getLatLng();
                latInput.value = p.lat.toFixed(7);
                lngInput.value = p.lng.toFixed(7);
            });
        }
    });
}
