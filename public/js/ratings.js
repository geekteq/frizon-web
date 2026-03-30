// Rating input dots
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.rating-input').forEach(function(group) {
        var input = group.querySelector('input[type="hidden"]');
        var dots = group.querySelectorAll('.rating-dot');

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                var val = parseInt(this.dataset.value);
                input.value = val;
                dots.forEach(function(d) {
                    d.classList.toggle('rating-dot--active', parseInt(d.dataset.value) <= val);
                });
            });
        });
    });
});
