// assets/js/planning.js
document.addEventListener('DOMContentLoaded', function() {
    // Mise à jour automatique de l'heure de fin en fonction de la durée
    const durationSelect = document.querySelector('select[name="duration"]');
    const timeInput = document.querySelector('input[name="planned_time"]');
    const endTimeDisplay = document.querySelector('#end-time-display');
    
    if (durationSelect && timeInput && endTimeDisplay) {
        function updateEndTime() {
            const duration = parseInt(durationSelect.value) || 60;
            const timeParts = timeInput.value.split(':');
            if (timeParts.length === 2) {
                let hours = parseInt(timeParts[0]);
                let minutes = parseInt(timeParts[1]) + duration;
                hours += Math.floor(minutes / 60);
                minutes = minutes % 60;
                endTimeDisplay.textContent = 
                    String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
            }
        }
        
        durationSelect.addEventListener('change', updateEndTime);
        timeInput.addEventListener('change', updateEndTime);
    }
    
    // Sélection rapide des dates
    const dateQuickBtns = document.querySelectorAll('.quick-date-btn');
    dateQuickBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const dateInput = document.querySelector('input[name="planned_date"]');
            if (dateInput) {
                const days = parseInt(this.dataset.days) || 0;
                const date = new Date();
                date.setDate(date.getDate() + days);
                dateInput.value = date.toISOString().split('T')[0];
            }
        });
    });
});