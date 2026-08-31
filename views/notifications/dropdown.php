<?php
// views/notifications/dropdown.php - Dropdown des notifications
// À inclure dans navbar.php

// Récupérer le rôle pour cacher les boutons
$role = $_SESSION['user_role'] ?? 'commercial';
$isCommercial = ($role === 'commercial');
$unreadCount = $unreadCount ?? 0;
?>

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open; if(open) loadNotifications()" 
            class="navbar-notif-badge relative p-2 rounded-lg hover:bg-gray-100 transition" 
            title="Notifications"
            id="notificationBell">
        <i class="fas fa-bell text-xl text-gray-600 transition hover:text-indigo-600"></i>
        <span class="badge-dot" style="display: <?= $unreadCount > 0 ? 'block' : 'none' ?>"></span>
        <?php if (isset($unreadCount) && $unreadCount > 0): ?>
        <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 shadow-md animate-pulse" 
              id="notificationCount"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php else: ?>
        <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 shadow-md animate-pulse" 
              id="notificationCount" style="display: none;">0</span>
        <?php endif; ?>
    </button>
    
    <!-- Dropdown notifications -->
    <div x-show="open" @click.away="open = false" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-gray-200 z-50 max-h-[450px] overflow-hidden">
        
        <!-- En-tête -->
        <div class="p-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
            <span class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-bell text-indigo-600"></i>
                Notifications
                <span id="notifBadgeCount" class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full <?= $unreadCount > 0 ? '' : 'hidden' ?>">
                    <?= $unreadCount ?>
                </span>
            </span>
            <!-- BOUTONS CACHÉS POUR LE COMMERCIAL -->
            <?php if (!$isCommercial): ?>
            <div class="flex items-center gap-2">
                <button onclick="markAllRead()" 
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition flex items-center gap-1 <?= $unreadCount > 0 ? '' : 'hidden' ?>" 
                        id="markAllReadBtn">
                    <i class="fas fa-check-double"></i> Tout lire
                </button>
                <span class="text-gray-300">|</span>
                <button onclick="confirmDeleteAllNotifications()" 
                        class="text-xs text-red-600 hover:text-red-800 font-medium transition flex items-center gap-1" 
                        title="Supprimer toutes les notifications">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Liste -->
        <div id="notification-list" class="divide-y divide-gray-100 max-h-[350px] overflow-y-auto custom-scroll">
            <div class="p-6 text-center text-gray-400 text-sm" id="notifLoading">
                <i class="fas fa-spinner fa-spin mr-2"></i>Chargement...
            </div>
        </div>
        
        <!-- Pied -->
        <div class="p-2 border-t border-gray-100 text-center bg-gray-50 flex justify-center items-center gap-4">
            <a href="index.php?page=historique" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition flex items-center justify-center gap-1 group">
                Voir toutes les notifications
                <i class="fas fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
            </a>
            <!-- BOUTON SUPPRIMER CACHÉ POUR LE COMMERCIAL -->
            <?php if (!$isCommercial): ?>
            <span class="text-gray-300">|</span>
            <button onclick="confirmDeleteAllNotifications()" class="text-xs text-red-500 hover:text-red-700 font-medium transition flex items-center gap-1">
                <i class="fas fa-trash"></i> Supprimer tout
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SCRIPTS POUR LE DROPDOWN -->
<script>
// ============================================
// GESTION DES NOTIFICATIONS DANS LE DROPDOWN
// ============================================

let notifOpen = false;
let notifCount = <?= $unreadCount ?>;
let lastNotifId = 0;

function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    notifOpen = !notifOpen;
    
    if (notifOpen) {
        dropdown.classList.remove('hidden');
        loadNotifications();
    } else {
        dropdown.classList.add('hidden');
    }
}

function loadNotifications() {
    const list = document.getElementById('notification-list');
    const loading = document.getElementById('notifLoading');
    if (!list) return;
    
    if (loading) loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('index.php?page=api&action=notifications', {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            if (response.status === 302 || response.status === 401) {
                throw new Error('SESSION_EXPIRED');
            }
            throw new Error('REPONSE_NON_JSON');
        }
        return response.json();
    })
    .then(data => {
        if (loading) loading.style.display = 'none';
        
        if (data.code === 'AUTH_REQUIRED' || data.error === 'Non authentifié') {
            console.warn('⚠️ Session API expirée');
            updateNotificationBadge(0);
            list.innerHTML = getEmptyNotificationHTML();
            return;
        }
        
        if (data.error) {
            list.innerHTML = `
                <div class="p-6 text-center text-red-400 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    ${escapeHtml(data.error)}
                    <button onclick="loadNotifications()" 
                            class="mt-2 block text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                        <i class="fas fa-sync mr-1"></i> Réessayer
                    </button>
                </div>
            `;
            return;
        }
        
        if (!data.notifications || !Array.isArray(data.notifications)) {
            list.innerHTML = getEmptyNotificationHTML();
            updateNotificationBadge(0);
            return;
        }
        
        updateNotificationBadge(data.unread_count || 0);
        
        if (data.notifications.length > 0) {
            list.innerHTML = data.notifications.map(renderNotification).join('');
        } else {
            list.innerHTML = getEmptyNotificationHTML();
        }
        
        const markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.classList.toggle('hidden', data.unread_count <= 0);
        }
    })
    .catch(err => {
        console.error('Erreur chargement notifications:', err);
        if (loading) loading.style.display = 'none';
        list.innerHTML = getEmptyNotificationHTML();
        updateNotificationBadge(0);
    });
}

function renderNotification(n) {
    const iconMap = {
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
    const icon = iconMap[n.type] || 'fa-bell';
    
    const colorMap = {
        'ticket': 'text-indigo-500 bg-indigo-50',
        'comment': 'text-blue-500 bg-blue-50',
        'status': 'text-amber-500 bg-amber-50',
        'action': 'text-purple-500 bg-purple-50',
        'message': 'text-emerald-500 bg-emerald-50',
        'validation': 'text-green-500 bg-green-50',
        'assignation': 'text-cyan-500 bg-cyan-50',
        'planning': 'text-pink-500 bg-pink-50',
        'general': 'text-gray-500 bg-gray-50'
    };
    const color = colorMap[n.type] || 'text-gray-500 bg-gray-50';
    
    const typeLabels = {
        'ticket': 'Nouveau ticket',
        'comment': 'Commentaire',
        'status': 'Changement de statut',
        'action': 'Action',
        'message': 'Message',
        'validation': 'Validation',
        'assignation': 'Assignation',
        'planning': 'Planning',
        'general': 'Notification'
    };
    const typeLabel = typeLabels[n.type] || 'Notification';
    
    const message = n.message || 'Notification';
    const timeAgo = n.time_ago || 'N/A';
    const link = n.link || '#';
    const isRead = n.is_read || false;
    
    return `
        <a href="${link}" 
           class="block px-4 py-3 hover:bg-gray-50 transition group ${isRead ? '' : 'bg-indigo-50/50 border-l-4 border-indigo-500'}">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-8 h-8 rounded-full ${isRead ? 'bg-gray-100' : 'bg-indigo-100'} flex items-center justify-center ${color}">
                        <i class="fas ${icon} text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-400">${typeLabel}</span>
                        ${!isRead ? '<span class="w-2 h-2 bg-indigo-500 rounded-full flex-shrink-0"></span>' : ''}
                    </div>
                    <p class="text-sm ${isRead ? 'text-gray-600' : 'text-gray-800 font-medium'} mt-0.5">${escapeHtml(message)}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-gray-400">${escapeHtml(timeAgo)}</span>
                    </div>
                </div>
            </div>
        </a>
    `;
}

function getEmptyNotificationHTML() {
    return `
        <div class="p-8 text-center text-gray-400">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-bell-slash text-2xl text-gray-300"></i>
            </div>
            <p class="text-sm font-medium text-gray-500">Aucune notification</p>
            <p class="text-xs text-gray-300 mt-1">Toutes vos notifications apparaîtront ici</p>
        </div>
    `;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateNotificationBadge(count) {
    const countEl = document.getElementById('notificationCount');
    const badgeCount = document.getElementById('notifBadgeCount');
    const markAllBtn = document.getElementById('markAllReadBtn');
    const bellIcon = document.querySelector('#notificationBell i');
    const dot = document.querySelector('.badge-dot');
    
    if (count > 0) {
        if (countEl) {
            countEl.style.display = 'flex';
            countEl.textContent = count > 9 ? '9+' : count;
        }
        if (badgeCount) {
            badgeCount.classList.remove('hidden');
            badgeCount.textContent = count;
        }
        if (markAllBtn) {
            markAllBtn.classList.remove('hidden');
        }
        if (bellIcon) {
            bellIcon.classList.add('text-indigo-600');
            bellIcon.classList.remove('text-gray-600');
        }
        if (dot) {
            dot.style.display = 'block';
        }
    } else {
        if (countEl) countEl.style.display = 'none';
        if (badgeCount) badgeCount.classList.add('hidden');
        if (markAllBtn) markAllBtn.classList.add('hidden');
        if (bellIcon) {
            bellIcon.classList.remove('text-indigo-600');
            bellIcon.classList.add('text-gray-600');
        }
        if (dot) {
            dot.style.display = 'none';
        }
    }
}

function markAllRead() {
    const btn = document.getElementById('markAllReadBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    fetch('index.php?page=api&action=mark_all_read', { 
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge(0);
            loadNotifications();
            showToast('Toutes les notifications ont été marquées comme lues', 'success');
        } else {
            showToast('Erreur lors de l\'opération', 'danger');
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        showToast('Erreur lors de l\'opération', 'danger');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double"></i> Tout lire';
        }
    });
}

function confirmDeleteAllNotifications() {
    if (confirm('Êtes-vous sûr de vouloir supprimer TOUTES vos notifications ?\n\nCette action est irréversible.')) {
        deleteAllNotifications();
    }
}

function deleteAllNotifications() {
    const btn = document.querySelector('[onclick="confirmDeleteAllNotifications()"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    fetch('index.php?page=api&action=delete_all_notifications', { 
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge(0);
            loadNotifications();
            showToast('Toutes les notifications ont été supprimées', 'info');
        } else {
            showToast('Erreur lors de la suppression', 'danger');
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        showToast('Erreur lors de la suppression', 'danger');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer tout';
        }
    });
}

function showToast(message, type = 'info') {
    const existing = document.querySelector('.toast-container');
    if (existing) existing.remove();
    
    const container = document.createElement('div');
    container.className = 'toast-container fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm';
    document.body.appendChild(container);
    
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
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s, transform 0.5s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) container.remove();
        }, 500);
    }, 4000);
}

// ============================================
// POLLING
// ============================================
function checkNewNotifications() {
    fetch('index.php?page=api&action=count_unread', {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.count !== undefined && data.count != notifCount) {
            updateNotificationBadge(data.count);
            if (data.count > notifCount) {
                playNotificationSound();
                showToast('🔔 ' + (data.count - notifCount) + ' nouvelle(s) notification(s)');
            }
            notifCount = data.count;
        }
    })
    .catch(err => console.error('Polling error:', err));
}

function playNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = 880;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.2);
    } catch(e) { /* Silent fallback */ }
}

// ============================================
// INITIALISATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier les notifications toutes les 10 secondes
    setInterval(checkNewNotifications, 10000);
    
    // Vérifier immédiatement après 2 secondes
    setTimeout(checkNewNotifications, 2000);
});
</script>

<style>
.custom-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #4f46e5;
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #4338ca;
}
</style><?php
// views/notifications/dropdown.php - Dropdown des notifications
// À inclure dans navbar.php

// Récupérer le rôle pour cacher les boutons
$role = $_SESSION['user_role'] ?? 'commercial';
$isCommercial = ($role === 'commercial');
$unreadCount = $unreadCount ?? 0;
?>

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open; if(open) loadNotifications()" 
            class="navbar-notif-badge relative p-2 rounded-lg hover:bg-gray-100 transition" 
            title="Notifications"
            id="notificationBell">
        <i class="fas fa-bell text-xl text-gray-600 transition hover:text-indigo-600"></i>
        <span class="badge-dot" style="display: <?= $unreadCount > 0 ? 'block' : 'none' ?>"></span>
        <?php if (isset($unreadCount) && $unreadCount > 0): ?>
        <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 shadow-md animate-pulse" 
              id="notificationCount"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php else: ?>
        <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 shadow-md animate-pulse" 
              id="notificationCount" style="display: none;">0</span>
        <?php endif; ?>
    </button>
    
    <!-- Dropdown notifications -->
    <div x-show="open" @click.away="open = false" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-gray-200 z-50 max-h-[450px] overflow-hidden">
        
        <!-- En-tête -->
        <div class="p-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
            <span class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-bell text-indigo-600"></i>
                Notifications
                <span id="notifBadgeCount" class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full <?= $unreadCount > 0 ? '' : 'hidden' ?>">
                    <?= $unreadCount ?>
                </span>
            </span>
            <!-- BOUTONS CACHÉS POUR LE COMMERCIAL -->
            <?php if (!$isCommercial): ?>
            <div class="flex items-center gap-2">
                <button onclick="markAllRead()" 
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition flex items-center gap-1 <?= $unreadCount > 0 ? '' : 'hidden' ?>" 
                        id="markAllReadBtn">
                    <i class="fas fa-check-double"></i> Tout lire
                </button>
                <span class="text-gray-300">|</span>
                <button onclick="confirmDeleteAllNotifications()" 
                        class="text-xs text-red-600 hover:text-red-800 font-medium transition flex items-center gap-1" 
                        title="Supprimer toutes les notifications">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Liste -->
        <div id="notification-list" class="divide-y divide-gray-100 max-h-[350px] overflow-y-auto custom-scroll">
            <div class="p-6 text-center text-gray-400 text-sm" id="notifLoading">
                <i class="fas fa-spinner fa-spin mr-2"></i>Chargement...
            </div>
        </div>
        
        <!-- Pied -->
        <div class="p-2 border-t border-gray-100 text-center bg-gray-50 flex justify-center items-center gap-4">
            <a href="index.php?page=historique" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition flex items-center justify-center gap-1 group">
                Voir toutes les notifications
                <i class="fas fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
            </a>
            <!-- BOUTON SUPPRIMER CACHÉ POUR LE COMMERCIAL -->
            <?php if (!$isCommercial): ?>
            <span class="text-gray-300">|</span>
            <button onclick="confirmDeleteAllNotifications()" class="text-xs text-red-500 hover:text-red-700 font-medium transition flex items-center gap-1">
                <i class="fas fa-trash"></i> Supprimer tout
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SCRIPTS POUR LE DROPDOWN -->
<script>
// ============================================
// GESTION DES NOTIFICATIONS DANS LE DROPDOWN
// ============================================

let notifOpen = false;
let notifCount = <?= $unreadCount ?>;
let lastNotifId = 0;

function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    notifOpen = !notifOpen;
    
    if (notifOpen) {
        dropdown.classList.remove('hidden');
        loadNotifications();
    } else {
        dropdown.classList.add('hidden');
    }
}

function loadNotifications() {
    const list = document.getElementById('notification-list');
    const loading = document.getElementById('notifLoading');
    if (!list) return;
    
    if (loading) loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('index.php?page=api&action=notifications', {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            if (response.status === 302 || response.status === 401) {
                throw new Error('SESSION_EXPIRED');
            }
            throw new Error('REPONSE_NON_JSON');
        }
        return response.json();
    })
    .then(data => {
        if (loading) loading.style.display = 'none';
        
        if (data.code === 'AUTH_REQUIRED' || data.error === 'Non authentifié') {
            console.warn('⚠️ Session API expirée');
            updateNotificationBadge(0);
            list.innerHTML = getEmptyNotificationHTML();
            return;
        }
        
        if (data.error) {
            list.innerHTML = `
                <div class="p-6 text-center text-red-400 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    ${escapeHtml(data.error)}
                    <button onclick="loadNotifications()" 
                            class="mt-2 block text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                        <i class="fas fa-sync mr-1"></i> Réessayer
                    </button>
                </div>
            `;
            return;
        }
        
        if (!data.notifications || !Array.isArray(data.notifications)) {
            list.innerHTML = getEmptyNotificationHTML();
            updateNotificationBadge(0);
            return;
        }
        
        updateNotificationBadge(data.unread_count || 0);
        
        if (data.notifications.length > 0) {
            list.innerHTML = data.notifications.map(renderNotification).join('');
        } else {
            list.innerHTML = getEmptyNotificationHTML();
        }
        
        const markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.classList.toggle('hidden', data.unread_count <= 0);
        }
    })
    .catch(err => {
        console.error('Erreur chargement notifications:', err);
        if (loading) loading.style.display = 'none';
        list.innerHTML = getEmptyNotificationHTML();
        updateNotificationBadge(0);
    });
}

function renderNotification(n) {
    const iconMap = {
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
    const icon = iconMap[n.type] || 'fa-bell';
    
    const colorMap = {
        'ticket': 'text-indigo-500 bg-indigo-50',
        'comment': 'text-blue-500 bg-blue-50',
        'status': 'text-amber-500 bg-amber-50',
        'action': 'text-purple-500 bg-purple-50',
        'message': 'text-emerald-500 bg-emerald-50',
        'validation': 'text-green-500 bg-green-50',
        'assignation': 'text-cyan-500 bg-cyan-50',
        'planning': 'text-pink-500 bg-pink-50',
        'general': 'text-gray-500 bg-gray-50'
    };
    const color = colorMap[n.type] || 'text-gray-500 bg-gray-50';
    
    const typeLabels = {
        'ticket': 'Nouveau ticket',
        'comment': 'Commentaire',
        'status': 'Changement de statut',
        'action': 'Action',
        'message': 'Message',
        'validation': 'Validation',
        'assignation': 'Assignation',
        'planning': 'Planning',
        'general': 'Notification'
    };
    const typeLabel = typeLabels[n.type] || 'Notification';
    
    const message = n.message || 'Notification';
    const timeAgo = n.time_ago || 'N/A';
    const link = n.link || '#';
    const isRead = n.is_read || false;
    
    return `
        <a href="${link}" 
           class="block px-4 py-3 hover:bg-gray-50 transition group ${isRead ? '' : 'bg-indigo-50/50 border-l-4 border-indigo-500'}">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-8 h-8 rounded-full ${isRead ? 'bg-gray-100' : 'bg-indigo-100'} flex items-center justify-center ${color}">
                        <i class="fas ${icon} text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-400">${typeLabel}</span>
                        ${!isRead ? '<span class="w-2 h-2 bg-indigo-500 rounded-full flex-shrink-0"></span>' : ''}
                    </div>
                    <p class="text-sm ${isRead ? 'text-gray-600' : 'text-gray-800 font-medium'} mt-0.5">${escapeHtml(message)}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-gray-400">${escapeHtml(timeAgo)}</span>
                    </div>
                </div>
            </div>
        </a>
    `;
}

function getEmptyNotificationHTML() {
    return `
        <div class="p-8 text-center text-gray-400">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-bell-slash text-2xl text-gray-300"></i>
            </div>
            <p class="text-sm font-medium text-gray-500">Aucune notification</p>
            <p class="text-xs text-gray-300 mt-1">Toutes vos notifications apparaîtront ici</p>
        </div>
    `;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateNotificationBadge(count) {
    const countEl = document.getElementById('notificationCount');
    const badgeCount = document.getElementById('notifBadgeCount');
    const markAllBtn = document.getElementById('markAllReadBtn');
    const bellIcon = document.querySelector('#notificationBell i');
    const dot = document.querySelector('.badge-dot');
    
    if (count > 0) {
        if (countEl) {
            countEl.style.display = 'flex';
            countEl.textContent = count > 9 ? '9+' : count;
        }
        if (badgeCount) {
            badgeCount.classList.remove('hidden');
            badgeCount.textContent = count;
        }
        if (markAllBtn) {
            markAllBtn.classList.remove('hidden');
        }
        if (bellIcon) {
            bellIcon.classList.add('text-indigo-600');
            bellIcon.classList.remove('text-gray-600');
        }
        if (dot) {
            dot.style.display = 'block';
        }
    } else {
        if (countEl) countEl.style.display = 'none';
        if (badgeCount) badgeCount.classList.add('hidden');
        if (markAllBtn) markAllBtn.classList.add('hidden');
        if (bellIcon) {
            bellIcon.classList.remove('text-indigo-600');
            bellIcon.classList.add('text-gray-600');
        }
        if (dot) {
            dot.style.display = 'none';
        }
    }
}

function markAllRead() {
    const btn = document.getElementById('markAllReadBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    fetch('index.php?page=api&action=mark_all_read', { 
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge(0);
            loadNotifications();
            showToast('Toutes les notifications ont été marquées comme lues', 'success');
        } else {
            showToast('Erreur lors de l\'opération', 'danger');
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        showToast('Erreur lors de l\'opération', 'danger');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double"></i> Tout lire';
        }
    });
}

function confirmDeleteAllNotifications() {
    if (confirm('Êtes-vous sûr de vouloir supprimer TOUTES vos notifications ?\n\nCette action est irréversible.')) {
        deleteAllNotifications();
    }
}

function deleteAllNotifications() {
    const btn = document.querySelector('[onclick="confirmDeleteAllNotifications()"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    fetch('index.php?page=api&action=delete_all_notifications', { 
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge(0);
            loadNotifications();
            showToast('Toutes les notifications ont été supprimées', 'info');
        } else {
            showToast('Erreur lors de la suppression', 'danger');
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        showToast('Erreur lors de la suppression', 'danger');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer tout';
        }
    });
}

function showToast(message, type = 'info') {
    const existing = document.querySelector('.toast-container');
    if (existing) existing.remove();
    
    const container = document.createElement('div');
    container.className = 'toast-container fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm';
    document.body.appendChild(container);
    
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
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s, transform 0.5s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) container.remove();
        }, 500);
    }, 4000);
}

// ============================================
// POLLING
// ============================================
function checkNewNotifications() {
    fetch('index.php?page=api&action=count_unread', {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.count !== undefined && data.count != notifCount) {
            updateNotificationBadge(data.count);
            if (data.count > notifCount) {
                playNotificationSound();
                showToast('🔔 ' + (data.count - notifCount) + ' nouvelle(s) notification(s)');
            }
            notifCount = data.count;
        }
    })
    .catch(err => console.error('Polling error:', err));
}

function playNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = 880;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.2);
    } catch(e) { /* Silent fallback */ }
}

// ============================================
// INITIALISATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier les notifications toutes les 10 secondes
    setInterval(checkNewNotifications, 10000);
    
    // Vérifier immédiatement après 2 secondes
    setTimeout(checkNewNotifications, 2000);
});
</script>

<style>
.custom-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #4f46e5;
    
    border-radius: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #4338ca;
}
</style>