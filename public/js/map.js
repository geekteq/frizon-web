// Static map for place detail
function initStaticMap(el, lat, lng) {
    var map = L.map(el).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
}
