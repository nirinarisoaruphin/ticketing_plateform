// ============================================
// NOTIFICATIONS EN TEMPS RÉEL - CLASSE COMPLÈTE
// ============================================

class RealTimeNotification {
    constructor(options = {}) {
        this.pollingInterval = options.pollingInterval || 8000;
        this.maxDisplay = options.maxDisplay || 5;
        this.soundEnabled = options.soundEnabled !== false;
        this.lastNotifId = 0;
        this.isPlaying = false;
        this.notificationCount = 0;
        this.container = null;
        this.soundIndicator = null;
        this.pollingId = null;
        this.isPaused = false;
        
        this.init();
    }
    
    init() {
        // Créer le conteneur de notifications
        this.container = document.createElement('div');
        this.container.className = 'notification-stack';
        document.body.appendChild(this.container);
        
        // Créer l'indicateur sonore
        this.soundIndicator = document.createElement('div');
        this.soundIndicator.className = 'notification-sound-indicator';
        this.soundIndicator.innerHTML = '<i class="fas fa-volume-up"></i> <span>Nouvelle notification</span>';
        document.body.appendChild(this.soundIndicator);
        
        // Démarrer le polling
        this.startPolling();
        
        // Écouter les changements de visibilité
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pause();
            } else {
                this.resume();
            }
        });
    }
    
    startPolling() {
        if (this.pollingId) {
            clearInterval(this.pollingId);
        }
        
        this.pollingId = setInterval(() => {
            if (!this.isPaused) {
                this.checkNotifications();
            }
        }, this.pollingInterval);
        
        // Vérifier immédiatement
        this.checkNotifications();
        
        console.log('🔔 Notifications en temps réel activées (polling: ' + (this.pollingInterval/1000) + 's)');
    }
    
    pause() {
        this.isPaused = true;
        console.log('⏸️ Polling en pause');
    }
    
    resume() {
        this.isPaused = false;
        console.log('▶️ Polling repris');
        this.checkNotifications();
    }
    
    checkNotifications() {
        fetch('index.php?page=api&action=notifications', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401 || response.status === 302) {
                    throw new Error('SESSION_EXPIRED');
                }
                throw new Error('HTTP_' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success === false) {
                console.warn('⚠️ Erreur API:', data.error);
                return;
            }
            
            if (data.notifications && Array.isArray(data.notifications)) {
                // Filtrer les nouvelles notifications
                const newNotifs = data.notifications.filter(n => n.id > this.lastNotifId);
                
                if (newNotifs.length > 0) {
                    this.processNewNotifications(newNotifs);
                }
                
                // Mettre à jour le dernier ID
                if (data.notifications.length > 0) {
                    this.lastNotifId = data.notifications[0].id;
                }
                
                // Mettre à jour le badge
                this.updateBadge(data.unread_count || 0);
            }
        })
        .catch(err => {
            if (err.message === 'SESSION_EXPIRED') {
                console.warn('⚠️ Session expirée, arrêt du polling');
                this.stop();
            } else {
                console.error('❌ Erreur polling:', err);
            }
        });
    }
    
    processNewNotifications(notifications) {
        // Trier par ID décroissant (les plus récents d'abord)
        notifications.sort((a, b) => b.id - a.id);
        
        notifications.forEach((notif, index) => {
            // Ajouter un délai progressif pour un effet de cascade
            setTimeout(() => {
                this.showNotification(notif);
            }, index * 300);
        });
        
        // Jouer le son
        if (this.soundEnabled) {
            this.playSound();
        }
        
        // Afficher l'indicateur sonore
        this.showSoundIndicator(notifications.length);
        
        // Mettre à jour le titre de la page
        if (document.hidden) {
            const count = this.notificationCount + notifications.length;
            document.title = `(${count}) 📬 TicketingPro`;
        }
        
        this.notificationCount += notifications.length;
    }
    
    showNotification(notif) {
        // Vérifier le nombre maximal de notifications affichées
        const currentNotifs = this.container.querySelectorAll('.notification-toast');
        if (currentNotifs.length >= this.maxDisplay) {
            const last = currentNotifs[currentNotifs.length - 1];
            if (last) {
                last.classList.add('removing');
                setTimeout(() => last.remove(), 400);
            }
        }
        
        // Créer l'élément
        const toast = document.createElement('div');
        toast.className = `notification-toast notif-${notif.type || 'general'}`;
        
        // Icônes par type
        const icons = {
            'ticket': 'fa-ticket-alt',
            'comment': 'fa-comment',
            'status': 'fa-exchange-alt',
            'action': 'fa-bolt',
            'message': 'fa-comment-dots',
            'validation': 'fa-check-circle',
            'assignation': 'fa-user-check',
            'planning': 'fa-calendar-check',
            'general': 'fa-bell'
        };
        const icon = icons[notif.type] || 'fa-bell';
        
        // Labels de type
        const typeLabels = {
            'ticket': 'Nouveau ticket',
            'comment': 'Commentaire',
            'status': 'Changement statut',
            'action': 'Action',
            'message': 'Message',
            'validation': 'Validation',
            'assignation': 'Assignation',
            'planning': 'Planning',
            'general': 'Notification'
        };
        const typeLabel = typeLabels[notif.type] || 'Notification';
        
        // Couleurs
        const colorMap = {
            'ticket': 'ticket',
            'comment': 'comment',
            'status': 'status',
            'action': 'action',
            'message': 'message',
            'validation': 'validation',
            'assignation': 'assignation',
            'planning': 'planning',
            'general': 'general'
        };
        const colorClass = colorMap[notif.type] || 'general';
        
        // Lien
        const link = notif.link || '#';
        
        toast.innerHTML = `
            <div class="notif-header">
                <div class="notif-icon ${colorClass}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">
                        ${typeLabel}
                        <span class="notif-badge">Nouveau</span>
                    </div>
                    <div class="notif-message">${this.escapeHtml(notif.message)}</div>
                    <div class="notif-time">
                        <i class="far fa-clock"></i>
                        ${this.timeAgo(notif.created_at)}
                    </div>
                    <div class="notif-action">
                        <a href="${link}">
                            Voir le détail <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <button class="notif-close" onclick="this.closest('.notification-toast').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Ajouter au conteneur
        this.container.prepend(toast);
        
        // Auto-suppression après 8 secondes
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 400);
            }
        }, 8000);
    }
    
    updateBadge(count) {
        // Mettre à jour le badge dans la navbar
        const badge = document.getElementById('notificationCount');
        const badgeDot = document.querySelector('.badge-dot');
        const badgeWrapper = document.querySelector('.notification-badge-wrapper');
        
        if (badge) {
            if (count > 0) {
                badge.style.display = 'flex';
                badge.textContent = count > 9 ? '9+' : count;
            } else {
                badge.style.display = 'none';
            }
        }
        
        if (badgeDot) {
            if (count > 0) {
                badgeDot.style.display = 'block';
            } else {
                badgeDot.style.display = 'none';
            }
        }
        
        if (badgeWrapper) {
            const icon = badgeWrapper.querySelector('.badge-icon');
            if (icon) {
                if (count > 0) {
                    icon.style.color = '#4F46E5';
                } else {
                    icon.style.color = '#64748b';
                }
            }
        }
    }
    
    playSound() {
        if (this.isPlaying) return;
        this.isPlaying = true;
        
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            
            // Premier son - note aiguë
            const osc1 = audioCtx.createOscillator();
            const gain1 = audioCtx.createGain();
            osc1.connect(gain1);
            gain1.connect(audioCtx.destination);
            osc1.frequency.value = 880;
            osc1.type = 'sine';
            gain1.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
            osc1.start();
            osc1.stop(audioCtx.currentTime + 0.15);
            
            // Deuxième son - note plus basse
            setTimeout(() => {
                const osc2 = audioCtx.createOscillator();
                const gain2 = audioCtx.createGain();
                osc2.connect(gain2);
                gain2.connect(audioCtx.destination);
                osc2.frequency.value = 660;
                osc2.type = 'sine';
                gain2.gain.setValueAtTime(0.25, audioCtx.currentTime);
                gain2.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
                osc2.start();
                osc2.stop(audioCtx.currentTime + 0.15);
            }, 150);
            
            setTimeout(() => {
                this.isPlaying = false;
            }, 400);
            
        } catch(e) {
            console.log('🔊 Son de notification non disponible');
            this.isPlaying = false;
        }
    }
    
    showSoundIndicator(count) {
        const indicator = this.soundIndicator;
        if (!indicator) return;
        
        const text = count > 1 ? `${count} nouvelles notifications` : 'Nouvelle notification';
        indicator.querySelector('span').textContent = text;
        indicator.classList.add('show');
        
        clearTimeout(this.indicatorTimeout);
        this.indicatorTimeout = setTimeout(() => {
            indicator.classList.remove('show');
        }, 2000);
    }
    
    timeAgo(dateStr) {
        if (!dateStr) return 'À l\'instant';
        
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 10) return 'À l\'instant';
        if (diff < 60) return `Il y a ${diff}s`;
        if (diff < 3600) return `Il y a ${Math.floor(diff/60)}min`;
        if (diff < 86400) return `Il y a ${Math.floor(diff/3600)}h`;
        if (diff < 2592000) return `Il y a ${Math.floor(diff/86400)}j`;
        
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    stop() {
        if (this.pollingId) {
            clearInterval(this.pollingId);
            this.pollingId = null;
        }
        console.log('🔔 Polling arrêté');
    }
    
    // ✅ MÉTHODE POUR TESTER UNE NOTIFICATION
    testNotification() {
        const testNotif = {
            id: Date.now(),
            type: 'general',
            message: '🧪 Ceci est une notification de test',
            link: '#',
            created_at: new Date().toISOString()
        };
        this.showNotification(testNotif);
        this.playSound();
    }
}

// ============================================
// INITIALISATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le gestionnaire de notifications
    window.notifSystem = new RealTimeNotification({
        pollingInterval: 8000,
        maxDisplay: 5,
        soundEnabled: true
    });
    
    // Arrêter le polling quand on quitte la page
    window.addEventListener('beforeunload', function() {
        if (window.notifSystem) {
            window.notifSystem.stop();
        }
    });
});

// ============================================
// FONCTION GLOBALE POUR TESTER
// ============================================

function testNotification() {
    if (window.notifSystem) {
        window.notifSystem.testNotification();
    } else {
        console.warn('⚠️ Système de notification non initialisé');
    }
}