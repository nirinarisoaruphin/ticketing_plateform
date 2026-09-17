// assets/js/ticket.js
document.addEventListener('DOMContentLoaded', function() {
    // Filtrer les tickets en temps réel
    const filterForm = document.querySelector('#ticket-filters');
    if (filterForm) {
        filterForm.addEventListener('change', function() {
            this.submit();
        });
    }
    
    // Prévisualisation de la pièce jointe
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileName = this.files[0]?.name || 'Aucun fichier sélectionné';
            const label = this.nextElementSibling;
            if (label) {
                label.textContent = fileName;
            }
        });
    }
});