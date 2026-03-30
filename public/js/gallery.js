// Image upload preview
document.addEventListener('DOMContentLoaded', function() {
    var fileInput = document.querySelector('input[type="file"][name="photos[]"]');
    if (!fileInput) return;

    fileInput.addEventListener('change', function() {
        var preview = this.parentNode.querySelector('.upload-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'upload-preview';
            preview.style.cssText = 'display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;';
            this.parentNode.appendChild(preview);
        }
        preview.innerHTML = '';

        Array.from(this.files).slice(0, 8).forEach(function(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width:80px; height:80px; object-fit:cover; border-radius:6px;';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
});
