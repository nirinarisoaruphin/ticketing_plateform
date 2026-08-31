// assets/js/app.js - Fonctions globales

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dissimulation des messages flash
    document.querySelectorAll('.flash-message').forEach((msg, index) => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-20px) scale(0.95)';
            setTimeout(() => msg.remove(), 500);
        }, 4000 + (index * 300));
    });
    // Declaration de variable A,B et C
    // Confirmation de suppression avancée
    document.querySelectorAll('.delete-confirm').forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.dataset.confirmMessage || 
                           'Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Animation des statistiques
    document.querySelectorAll('.stat-number').forEach(el => {
        const target = parseInt(el.textContent);
        if (target > 0) {
            animateNumber(el, target);
        }
    });
});

/**
 * Animation des nombres
 */
function animateNumber(element, target, duration = 800) {
    const start = 0;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(start + (target - start) * eased);
        
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = target;
        }
    }
    
    requestAnimationFrame(update);
}

/**
 * Formater une date
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Formater une date courte
 */
function formatDateShort(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

/**
 * Copier du texte dans le presse-papier
 */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('✅ Copié dans le presse-papier !', 'success');
        }).catch(() => fallbackCopy(text));
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const input = document.createElement('input');
    input.value = text;
    input.style.position = 'fixed';
    input.style.opacity = '0';
    document.body.appendChild(input);
    input.select();
    try {
        document.execCommand('copy');
        showToast('✅ Copié dans le presse-papier !', 'success');
    } catch (e) {
        showToast('❌ Impossible de copier', 'danger');
    }
    document.body.removeChild(input);
}

/**
 * Afficher un toast (notification temporaire)
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast flash-${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info} text-${type}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(() => toast.remove(), 500);
    }, 3500);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

/**
 * Télécharger un fichier
 */
function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'download';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}