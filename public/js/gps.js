// GPS capture for place creation — supports GPS mode and manual (lat/lng + map click) mode.
function initGpsCapture(mapId, latId, lngId) {
    var mapEl = document.getElementById(mapId);
    var latInput = document.getElementById(latId);
    var lngInput = document.getElementById(lngId);

    if (!mapEl) return;

    // Resolve visible lat/lng fields for manual mode (may differ from hidden inputs)
    var manualLatEl = document.getElementById('manual-lat');
    var manualLngEl = document.getElementById('manual-lng');
    var modeGpsBtn  = document.getElementById('mode-gps');
    var modeManBtn  = document.getElementById('mode-manual');
    var gpsStatus   = document.getElementById('gps-status');

    // Initialize Leaflet map centered on Sweden
    var map = L.map(mapId).setView([59.33, 18.07], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    var marker = null;
    var currentMode = 'gps'; // 'gps' | 'manual'

    // --- Nearby check helper ---
    function checkNearby(lat, lng) {
        fetch('/adm/api/platser/nearby?lat=' + lat + '&lng=' + lng)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.places && data.places.length > 0) {
                    var p = data.places[0];
                    var dist = Math.round(p.distance_meters);
                    if (confirm('Det ser ut som att du är vid ' + p.name + ' (' + dist + ' m bort).\n\nSkapa ett nytt besök istället?')) {
                        window.location.href = '/adm/platser/' + p.slug + '/besok/nytt';
                    }
                }
            })
            .catch(function() { /* silently ignore network errors */ });
    }

    // --- Place/move marker helper ---
    function placeMarker(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
        if (manualLatEl) manualLatEl.value = lat.toFixed(7);
        if (manualLngEl) manualLngEl.value = lng.toFixed(7);

        map.setView([lat, lng], 15);

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function() {
                var p = marker.getLatLng();
                latInput.value = p.lat.toFixed(7);
                lngInput.value = p.lng.toFixed(7);
                if (manualLatEl) manualLatEl.value = p.lat.toFixed(7);
                if (manualLngEl) manualLngEl.value = p.lng.toFixed(7);
            });
        }
    }

    // --- Map click sets position in both modes ---
    map.on('click', function(e) {
        placeMarker(e.latlng.lat, e.latlng.lng);
        checkNearby(e.latlng.lat, e.latlng.lng);
    });

    // --- Manual lat/lng field input ---
    function onManualInput() {
        var lat = parseFloat(manualLatEl ? manualLatEl.value : '');
        var lng = parseFloat(manualLngEl ? manualLngEl.value : '');
        if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            placeMarker(lat, lng);
            checkNearby(lat, lng);
        }
    }

    if (manualLatEl) manualLatEl.addEventListener('change', onManualInput);
    if (manualLngEl) manualLngEl.addEventListener('change', onManualInput);

    // --- GPS fetch ---
    function startGps() {
        if (!navigator.geolocation) {
            if (gpsStatus) gpsStatus.textContent = 'GPS är inte tillgänglig på den här enheten.';
            return;
        }
        if (gpsStatus) gpsStatus.textContent = 'Hämtar position…';
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                if (gpsStatus) gpsStatus.textContent = 'Position hämtad.';
                placeMarker(lat, lng);
                checkNearby(lat, lng);
            },
            function() {
                if (gpsStatus) gpsStatus.textContent = 'Kunde inte hämta GPS-position. Prova manuellt läge.';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    // --- Mode switching ---
    function setMode(mode) {
        currentMode = mode;
        var manualFields = document.getElementById('manual-fields');

        if (mode === 'gps') {
            if (modeGpsBtn) { modeGpsBtn.classList.add('chip--active'); modeGpsBtn.style.opacity = '1'; }
            if (modeManBtn) { modeManBtn.classList.remove('chip--active'); modeManBtn.style.opacity = '0.6'; }
            if (manualFields) manualFields.style.display = 'none';
            startGps();
        } else {
            if (modeManBtn) { modeManBtn.classList.add('chip--active'); modeManBtn.style.opacity = '1'; }
            if (modeGpsBtn) { modeGpsBtn.classList.remove('chip--active'); modeGpsBtn.style.opacity = '0.6'; }
            if (manualFields) manualFields.style.display = 'block';
            if (gpsStatus) gpsStatus.textContent = 'Ange koordinater eller klicka på kartan.';
        }
        // Always invalidate map size after any layout change
        setTimeout(function() { map.invalidateSize(); }, 50);
    }

    if (modeGpsBtn) modeGpsBtn.addEventListener('click', function() { setMode('gps'); });
    if (modeManBtn) modeManBtn.addEventListener('click', function() { setMode('manual'); });

    // Start in GPS mode by default
    setMode('gps');
}
