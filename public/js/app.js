// Frizon.org — Global JS

// Auto-dismiss toasts
document.querySelectorAll('.toast').forEach(function(toast) {
    var delay = toast.classList.contains('toast--error') ? 8000 : 4000;
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(function() { toast.remove(); }, 200);
    }, delay);
});

// CSRF token for fetch requests
function getCsrfToken() {
    var el = document.querySelector('input[name="_csrf"]');
    return el ? el.value : '';
}
