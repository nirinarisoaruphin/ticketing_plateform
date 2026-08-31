<?php if (isLoggedIn()): ?>
<?php
require_once __DIR__ . '/../models/Notification.php';
$notificationModel = new Notification();
$unreadCount = $notificationModel->getUnreadCount($_SESSION['user_id'] ?? 0);
?>
<nav class="navbar-modern" id="navbar" x-data="{ mobileOpen: false, notifOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <!-- ========================================== -->
            <!-- LOGO -->
            <!-- ========================================== -->
            <div class="flex items-center gap-3">
                <a href="index.php?page=dashboard" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center shadow-md group-hover:shadow-lg transition-all group-hover:scale-105">
                        <i class="fas fa-ticket-alt text-white text-lg"></i>
                    </div>
                    <span class="logo-text text-xl hidden sm:inline">TicketingPro</span>
                </a>
            </div>
            
            <!-- ========================================== -->
            <!-- NAVIGATION DESKTOP -->
            <!-- ========================================== -->
            <div class="hidden md:flex items-center gap-1">
                <?php 
                $role = $_SESSION['user_role'] ?? 'commercial';
                $page = $_GET['page'] ?? 'dashboard';
                ?>
                
                <!-- Tableau de bord -->
                <a href="index.php?page=dashboard" class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> <span>Tableau de bord</span>
                </a>
                
                <!-- Messages -->
                <a href="index.php?page=messages" class="nav-link <?= $page === 'messages' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> <span>Messages</span>
                </a>
                
                <!-- Tickets -->
                <a href="index.php?page=tickets" class="nav-link <?= $page === 'tickets' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> <span>Tickets</span>
                </a>
                
                <!-- Planning -->
                <?php if (in_array($role, ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])): ?>
                <a href="index.php?page=planning" class="nav-link <?= $page === 'planning' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> <span>Planning</span>
                </a>
                <?php endif; ?>
                
                <!-- Utilisateurs -->
                <?php if (isAdmin()): ?>
                <a href="index.php?page=users" class="nav-link <?= $page === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> <span>Utilisateurs</span>
                </a>
                <?php endif; ?>
                
                <!-- Export -->
                <?php if (canExportData()): ?>
                <a href="index.php?page=export" class="nav-link <?= $page === 'export' ? 'active' : '' ?>">
                    <i class="fas fa-file-export"></i> <span>Exporter</span>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- ========================================== -->
            <!-- SECTION DROITE -->
            <!-- ========================================== -->
            <div class="flex items-center gap-2 sm:gap-3">
                
                <!-- ========================================== -->
                <!-- ✅ NOTIFICATIONS AVEC BADGE MODERNE -->
                <!-- ========================================== -->
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
                            <span class="text-gray-300">|</span>
                            <button onclick="confirmDeleteAllNotifications()" class="text-xs text-red-500 hover:text-red-700 font-medium transition flex items-center gap-1">
                                <i class="fas fa-trash"></i> Supprimer tout
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- ========================================== -->
                <!-- MENU UTILISATEUR -->
                <!-- ========================================== -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 hover:bg-[var(--bg-hover)] rounded-full px-2 py-1.5 sm:px-3 transition">
                        <div class="avatar <?= getRoleAvatarClass($role) ?> avatar-sm">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 2)) ?>
                        </div>
                        <span class="text-sm font-medium text-[var(--text-secondary)] hidden sm:inline">
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?>
                        </span>
                        <i class="fas fa-chevron-down text-[var(--text-muted)] text-xs"></i>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></p>
                            <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                            <span class="text-xs text-gray-500 mt-1 block">
                                <span class="badge bg-gray-100 text-gray-700 px-2 py-0.5">
                                    <i class="fas <?= getRoleIcon($role) ?> mr-1"></i>
                                    <?= getRoleLabel($role) ?>
                                </span>
                            </span>
                        </div>
                        <a href="index.php?page=profile" class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition text-sm text-gray-700">
                            <i class="fas fa-user-circle text-gray-400 w-5"></i> Mon profil
                        </a>
                        <a href="index.php?page=change_password" class="flex items-center gap-3 px-4 py-2.5 hover:bg-yellow-50 transition text-sm text-yellow-700 border-t border-gray-200">
                            <i class="fas fa-key text-yellow-500 w-5"></i> Changer mot de passe
                        </a>
                        <a href="index.php?page=logout" class="flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 transition text-sm text-red-600 border-t border-gray-200">
                            <i class="fas fa-sign-out-alt w-5"></i> Déconnexion
                        </a>
                    </div>
                </div>
                
                <!-- ========================================== -->
                <!-- BOUTON MENU MOBILE -->
                <!-- ========================================== -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-bars text-gray-700 text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- ========================================== -->
        <!-- MENU MOBILE -->
        <!-- ========================================== -->
        <div x-show="mobileOpen" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="md:hidden py-4 border-t border-gray-200">
            <div class="flex flex-col space-y-1">
                <a href="index.php?page=dashboard" class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> Tableau de bord
                </a>
                <a href="index.php?page=historique" class="nav-link <?= $page === 'historique' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Historique
                </a>
                <a href="index.php?page=messages" class="nav-link <?= $page === 'messages' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Messages
                </a>
                <a href="index.php?page=tickets" class="nav-link <?= $page === 'tickets' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> Tickets
                </a>
                <?php if (in_array($role, ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])): ?>
                <a href="index.php?page=planning" class="nav-link <?= $page === 'planning' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Planning
                </a>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <a href="index.php?page=users" class="nav-link <?= $page === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> Utilisateurs
                </a>
                <?php endif; ?>
                <?php if (canExportData()): ?>
                <a href="index.php?page=export" class="nav-link <?= $page === 'export' ? 'active' : '' ?>">
                    <i class="fas fa-file-export"></i> Exporter
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ========================================== -->
<!-- Alpine.js -->
<!-- ========================================== -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js"></script>

<!-- ========================================== -->
<!-- STYLE POUR x-cloak -->
<!-- ========================================== -->
<style>
    [x-cloak] { display: none !important; }
</style>

<script>
// ============================================
// GESTION DES NOTIFICATIONS
// ============================================

// ============================================
// 1. CHARGER LES NOTIFICATIONS
// ============================================
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

// ============================================
// 2. RENDU D'UNE NOTIFICATION
// ============================================
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

// ============================================
// 3. HTML VIDE
// ============================================
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

// ============================================
// 4. ESCAPE HTML
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// 5. METTRE À JOUR LE BADGE
// ============================================
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

// ============================================
// 6. TOUT MARQUER COMME LU
// ============================================
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
            showToast('✅ Toutes les notifications ont été marquées comme lues', 'success');
        } else {
            showToast('❌ Erreur lors de l\'opération', 'danger');
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        showToast('❌ Erreur lors de l\'opération', 'danger');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double"></i> Tout lire';
        }
    });
}

// ============================================
// 7. SUPPRIMER TOUTES LES NOTIFICATIONS
// ============================================
function confirmDeleteAllNotifications() {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer TOUTES vos notifications ?\n\nCette action est irréversible.')) {
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
            showToast('🗑️ Toutes les notifications ont été supprimées', 'info');
        } else {
            showToast('❌ Erreur lors de la suppression', 'danger');
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        showToast('❌ Erreur lors de la suppression', 'danger');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer tout';
        }
    });
}

// ============================================
// 8. TOAST
// ============================================
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
            if (toast.parentNode) {
                toast.remove();
                if (container.children.length === 0) container.remove();
            }
        }, 500);
    }, 4000);
}

// ============================================
// 9. POLLING DES NOTIFICATIONS
// ============================================

let notificationPolling = null;
let pollingRetryCount = 0;
const MAX_POLLING_RETRY = 3;

function startNotificationPolling() {
    if (notificationPolling) {
        clearInterval(notificationPolling);
    }
    
    notificationPolling = setInterval(() => {
        if (!document.querySelector('#notificationBell')) {
            stopNotificationPolling();
            return;
        }
        
        fetch('index.php?page=api&action=count_unread', {
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
            pollingRetryCount = 0;
            
            if (data.error === 'Non authentifié') {
                console.warn('⚠️ Session API expirée, arrêt du polling');
                stopNotificationPolling();
                return;
            }
            if (data && data.count !== undefined) {
                updateNotificationBadge(data.count);
            }
        })
        .catch(err => {
            console.error('Erreur polling:', err);
            pollingRetryCount++;
            if (pollingRetryCount >= MAX_POLLING_RETRY) {
                console.warn('⚠️ Polling arrêté après plusieurs échecs');
                stopNotificationPolling();
            }
        });
    }, 10000);
}

function stopNotificationPolling() {
    if (notificationPolling) {
        clearInterval(notificationPolling);
        notificationPolling = null;
    }
}

// ============================================
// 10. INITIALISATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#notificationBell')) {
        startNotificationPolling();
    }
    
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    window.addEventListener('beforeunload', function() {
        stopNotificationPolling();
    });
});

// ============================================
// 11. STYLES DYNAMIQUES
// ============================================

const style = document.createElement('style');
style.textContent = `
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
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    .animate-pulse {
        animation: pulse 1.5s ease-in-out infinite;
    }
`;
document.head.appendChild(style);

// ============================================
// 12. SCRIPT DE NOTIFICATION EN TEMPS RÉEL
// ============================================

// Classe pour les notifications toast
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
        
        this.checkNotifications();
        console.log('🔔 Notifications en temps réel activées (polling: ' + (this.pollingInterval/1000) + 's)');
    }
    
    pause() {
        this.isPaused = true;
    }
    
    resume() {
        this.isPaused = false;
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
                return;
            }
            
            if (data.notifications && Array.isArray(data.notifications)) {
                const newNotifs = data.notifications.filter(n => n.id > this.lastNotifId);
                
                if (newNotifs.length > 0) {
                    this.processNewNotifications(newNotifs);
                }
                
                if (data.notifications.length > 0) {
                    this.lastNotifId = data.notifications[0].id;
                }
                
                updateNotificationBadge(data.unread_count || 0);
            }
        })
        .catch(err => {
            if (err.message === 'SESSION_EXPIRED') {
                this.stop();
            }
        });
    }
    
    processNewNotifications(notifications) {
        notifications.sort((a, b) => b.id - a.id);
        
        notifications.forEach((notif, index) => {
            setTimeout(() => {
                this.showNotification(notif);
            }, index * 300);
        });
        
        if (this.soundEnabled) {
            this.playSound();
        }
        this.showSoundIndicator(notifications.length);
        
        this.notificationCount += notifications.length;
    }
    
    showNotification(notif) {
        const currentNotifs = this.container.querySelectorAll('.notification-toast');
        if (currentNotifs.length >= this.maxDisplay) {
            const last = currentNotifs[currentNotifs.length - 1];
            if (last) {
                last.classList.add('removing');
                setTimeout(() => last.remove(), 400);
            }
        }
        
        const toast = document.createElement('div');
        toast.className = `notification-toast notif-${notif.type || 'general'}`;
        
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
        
        const link = notif.link || '#';
        
        toast.innerHTML = `
            <div class="notif-header">
                <div class="notif-icon ${notif.type || 'general'}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">
                        ${typeLabel}
                        <span class="notif-badge">Nouveau</span>
                    </div>
                    <div class="notif-message">${escapeHtml(notif.message)}</div>
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
        
        this.container.prepend(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 400);
            }
        }, 8000);
    }
    
    playSound() {
        if (this.isPlaying) return;
        this.isPlaying = true;
        
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            
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
    
    stop() {
        if (this.pollingId) {
            clearInterval(this.pollingId);
            this.pollingId = null;
        }
        console.log('🔔 Polling arrêté');
    }
    
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
// INITIALISATION DU SYSTÈME DE NOTIFICATIONS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    window.notifSystem = new RealTimeNotification({
        pollingInterval: 8000,
        maxDisplay: 5,
        soundEnabled: true
    });
    
    window.addEventListener('beforeunload', function() {
        if (window.notifSystem) {
            window.notifSystem.stop();
        }
    });
});

// ============================================
// FONCTION DE TEST
// ============================================

function testNotification() {
    if (window.notifSystem) {
        window.notifSystem.testNotification();
    } else {
        console.warn('⚠️ Système de notification non initialisé');
    }
}
</script>

<?php endif; ?>