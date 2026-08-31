<?php
// includes/footer.php - Version centrée sans point lumineux
?>
<footer class="mt-auto border-t border-gray-200 bg-white/80 backdrop-blur-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col items-center justify-center gap-3">
            
            <!-- Icône + Copyright centré -->
            <div class="flex items-center gap-3">
                <!-- Icône ticket -->
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-lg flex items-center justify-center shadow-sm">
                    <i class="fas fa-ticket-alt text-white text-sm"></i>
                </div>
                
                <!-- Texte copyright -->
                <p class="text-sm text-gray-500">
                    &copy; <?= date('Y') ?> <span class="font-semibold text-gray-700">Ticketing (SPIDER)</span>
                    <span class="text-gray-400">- Tous droits réservés</span>
                </p>
            </div>
            
            <!-- Version (optionnel, en petit) -->
            <span class="text-xs text-gray-400">
            </span>
        </div>
    </div>
</footer>

<!-- ============================================ -->
<!-- SCRIPTS JAVASCRIPT -->
<!-- ============================================ -->

<!-- Script principal -->
<script src="assets/js/app.js"></script>

<!-- ============================================ -->
<!-- FONCTIONS GLOBALES -->
<!-- ============================================ -->
<script>
/**
 * Confirmer la suppression d'un ticket
 * @param {string} ticketNumber - Le numéro du ticket à supprimer
 * @returns {boolean} - True si l'utilisateur confirme, False sinon
 */
function confirmDeleteTicket(ticketNumber) {
    return confirm(
        '⚠️ Êtes-vous sûr de vouloir supprimer le ticket ' + ticketNumber + ' ?\n\n' +
        'Cette action est irréversible.\n' +
        'Tous les commentaires et données associés seront supprimés.'
    );
}

/**
 * Confirmer la suppression d'une intervention
 * @param {string} interventionId - L'ID de l'intervention à supprimer
 * @returns {boolean} - True si l'utilisateur confirme, False sinon
 */
function confirmDeleteIntervention(interventionId) {
    return confirm(
        '⚠️ Êtes-vous sûr de vouloir supprimer cette intervention ?\n\n' +
        'Cette action est irréversible.'
    );
}

/**
 * Afficher un toast (notification temporaire)
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de toast (success, danger, warning, info)
 */
function showToast(message, type = 'info') {
    // Supprimer les toasts existants
    const existing = document.querySelector('.toast-container');
    if (existing) existing.remove();
    
    // Créer le conteneur
    const container = document.createElement('div');
    container.className = 'toast-container fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm';
    document.body.appendChild(container);
    
    // Créer le toast
    const toast = document.createElement('div');
    const colors = {
        success: 'bg-green-600',
        danger: 'bg-red-600',
        warning: 'bg-yellow-500',
        info: 'bg-indigo-600'
    };
    const icons = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.className = `${colors[type] || colors.info} text-white px-4 py-3 rounded-lg shadow-lg animate-slideUp`;
    toast.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas ${icons[type] || icons.info}"></i>
            <span class="text-sm">${escapeHtml(message)}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-white hover:text-gray-200 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Auto-suppression après 4 secondes
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s, transform 0.5s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
                if (container.children.length === 0) container.remove();
            }
        }, 500);
    }, 4000);
}

/**
 * Échapper le HTML pour éviter les injections XSS
 * @param {string} text - Le texte à échapper
 * @returns {string} - Le texte échappé
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Formater une date
 * @param {string} date - La date à formater
 * @returns {string} - La date formatée
 */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Copier du texte dans le presse-papier
 * @param {string} text - Le texte à copier
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
 * Télécharger un fichier
 * @param {string} url - L'URL du fichier
 * @param {string} filename - Le nom du fichier
 */
function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'download';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Générer un ID unique
 * @returns {string} - Un ID unique
 */
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
}

// ============================================
// STYLES DYNAMIQUES
// ============================================
const footerStyles = document.createElement('style');
footerStyles.textContent = `
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .animate-slideUp {
        animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .toast-container {
        pointer-events: none;
    }
    .toast-container > * {
        pointer-events: auto;
    }
`;
document.head.appendChild(footerStyles);

// ============================================
// INITIALISATION
// ============================================
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
    
    // Animation des nombres statistiques
    document.querySelectorAll('.stat-number').forEach(el => {
        const target = parseInt(el.textContent);
        if (target > 0 && !isNaN(target)) {
            animateNumber(el, target);
        }
    });
    
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
});

/**
 * Animation des nombres
 * @param {HTMLElement} element - L'élément à animer
 * @param {number} target - La valeur cible
 * @param {number} duration - La durée de l'animation
 */
function animateNumber(element, target, duration = 800) {
    const start = 0;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(start + (target - start) * eased);
        
        element.textContent = current.toLocaleString('fr-FR');
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = target.toLocaleString('fr-FR');
        }
    }
    
    requestAnimationFrame(update);
}
</script>

</body>
</html>