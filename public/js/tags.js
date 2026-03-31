// Tag autocomplete for suitable_for field
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('suitable_for');
    if (!input || !input.dataset.suggestions) return;

    var suggestions = JSON.parse(input.dataset.suggestions);
    var dropdown = document.createElement('div');
    dropdown.className = 'tag-autocomplete';
    dropdown.style.display = 'none';
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(dropdown);

    input.addEventListener('input', function() {
        var parts = this.value.split(',');
        var current = parts[parts.length - 1].trim().toLowerCase();

        if (current.length < 1) { dropdown.style.display = 'none'; return; }

        var matches = suggestions.filter(function(s) {
            return s.toLowerCase().indexOf(current) !== -1;
        }).slice(0, 5);

        if (matches.length === 0) { dropdown.style.display = 'none'; return; }

        dropdown.replaceChildren();
        matches.forEach(function(m) {
            var item = document.createElement('div');
            item.className = 'tag-autocomplete__item';
            item.textContent = m;
            dropdown.appendChild(item);
        });
        dropdown.style.display = 'block';

        dropdown.querySelectorAll('.tag-autocomplete__item').forEach(function(item) {
            item.addEventListener('click', function() {
                parts[parts.length - 1] = ' ' + this.textContent;
                input.value = parts.join(',');
                dropdown.style.display = 'none';
                input.focus();
            });
        });
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
