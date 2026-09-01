<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<?php
// ✅ Récupérer le rôle pour cacher les boutons
$role = $_SESSION['user_role'] ?? 'commercial';
$isCommercial = ($role === 'commercial');
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 page-enter">
    
    <!-- ============================================ -->
    <!-- EN-TÊTE AVEC BOUTONS -->
    <!-- ============================================ -->
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <!--<i class="fas fa-history text-indigo-600"></i>!-->
                Historique des notifications
                <span class="text-sm font-medium text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
                    <?= count($notifications) + count($activities) ?> élément(s)
                </span>
            </h1>
            <p class="text-gray-500 mt-1">
                <!--<i class="fas fa-info-circle text-indigo-400 mr-1"></i>!-->
                Consultez toutes vos notifications et activités
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <!-- ✅ BOUTONS CACHÉS POUR LE COMMERCIAL -->
            <?php if (!$isCommercial): ?>
                <?php if (!empty($notifications)): ?>
                <button onclick="confirmDeleteAllNotifications()" 
                        class="btn btn-danger btn-sm flex items-center gap-1.5">
                    <i class="fas fa-trash"></i> Supprimer tout
                </button>
                <?php endif; ?>
                <?php if (!empty($notifications)): ?>
                <button onclick="markAllRead()" 
                        class="btn btn-primary btn-sm flex items-center gap-1.5">
                    <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
                <?php endif; ?>
            <?php endif; ?>
            <a href="index.php?page=dashboard" class="btn btn-outline btn-sm flex items-center gap-1.5">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- MESSAGES FLASH -->
    <!-- ============================================ -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-message flash-<?= $flash['type'] ?> mb-6 rounded-lg">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?> mr-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- STATISTIQUES -->
    <!-- ============================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center hover:shadow-md transition">
            <p class="text-2xl font-bold text-indigo-600"><?= count($notifications) ?></p>
            <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                <i class="fas fa-bell text-indigo-400"></i> Notifications
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center hover:shadow-md transition">
            <p class="text-2xl font-bold text-blue-600"><?= count(array_filter($activities, function($a) { return empty($a['is_action']) || $a['is_action'] == 0; })) ?></p>
            <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                <i class="fas fa-comment text-blue-400"></i> Commentaires
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center hover:shadow-md transition">
            <p class="text-2xl font-bold text-purple-600"><?= count(array_filter($activities, function($a) { return !empty($a['is_action']); })) ?></p>
            <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                <i class="fas fa-bolt text-purple-400"></i> Actions
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center hover:shadow-md transition">
            <p class="text-2xl font-bold text-emerald-600"><?= count(array_filter($notifications, function($n) { return $n['is_read'] == 1; })) ?></p>
            <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                <i class="fas fa-check-circle text-emerald-400"></i> Lues
            </p>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- FILTRES RAPIDES -->
    <!-- ============================================ -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button onclick="filterNotifications('all')" class="filter-btn active px-3 py-1.5 text-sm rounded-lg bg-indigo-600 text-white transition" data-filter="all">
            <i class="fas fa-list mr-1"></i> Toutes
        </button>
        <button onclick="filterNotifications('unread')" class="filter-btn px-3 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="unread">
            <i class="fas fa-circle text-indigo-500 text-[8px] mr-1"></i> Non lues
        </button>
        <button onclick="filterNotifications('read')" class="filter-btn px-3 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="read">
            <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Lues
        </button>
        <button onclick="filterNotifications('ticket')" class="filter-btn px-3 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="ticket">
            <i class="fas fa-ticket-alt text-indigo-500 mr-1"></i> Tickets
        </button>
        <button onclick="filterNotifications('comment')" class="filter-btn px-3 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="comment">
            <i class="fas fa-comment text-blue-500 mr-1"></i> Commentaires
        </button>
        <button onclick="filterNotifications('status')" class="filter-btn px-3 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="status">
            <i class="fas fa-exchange-alt text-amber-500 mr-1"></i> Statuts
        </button>
        <button onclick="filterNotifications('action')" class="filter-btn px-3 py-1.5 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition" data-filter="action">
            <i class="fas fa-bolt text-purple-500 mr-1"></i> Actions
        </button>
    </div>
    
    <!-- ============================================ -->
    <!-- LISTE DES NOTIFICATIONS -->
    <!-- ============================================ -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex justify-between items-center">
            <h2 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <i class="fas fa-bell text-indigo-500"></i>
                Notifications
                <span class="text-xs text-gray-400 font-normal ml-2" id="notifCount">(<?= count($notifications) ?>)</span>
            </h2>
            <!-- ✅ BOUTONS CACHÉS POUR LE COMMERCIAL -->
            <?php if (!$isCommercial): ?>
            <div class="flex items-center gap-3">
                <?php if (!empty($notifications)): ?>
                <button onclick="markAllRead()" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition flex items-center gap-1">
                    <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
                <span class="text-gray-300">|</span>
                <button onclick="confirmDeleteAllNotifications()" class="text-xs text-red-600 hover:text-red-800 font-medium transition flex items-center gap-1">
                    <i class="fas fa-trash"></i> Supprimer tout
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
        <div class="p-12 text-center text-gray-400">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bell-slash text-3xl text-gray-300"></i>
            </div>
            <p class="text-lg font-medium text-gray-600">Aucune notification</p>
            <p class="text-sm text-gray-400 mt-1">Vous n'avez pas encore reçu de notifications.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto custom-scroll" id="notificationList">
            <?php foreach ($notifications as $notif): ?>
            <?php
            $icons = [
                'ticket' => 'fa-ticket-alt',
                'comment' => 'fa-comment',
                'status' => 'fa-exchange-alt',
                'action' => 'fa-bolt',
                'message' => 'fa-comment-dots',
                'validation' => 'fa-check-circle',
                'assignation' => 'fa-user-check',
                'general' => 'fa-bell'
            ];
            $icon = $icons[$notif['type'] ?? 'general'] ?? 'fa-bell';
            
            $colors = [
                'ticket' => 'text-indigo-600 bg-indigo-50',
                'comment' => 'text-blue-600 bg-blue-50',
                'status' => 'text-amber-600 bg-amber-50',
                'action' => 'text-purple-600 bg-purple-50',
                'message' => 'text-emerald-600 bg-emerald-50',
                'validation' => 'text-green-600 bg-green-50',
                'assignation' => 'text-cyan-600 bg-cyan-50',
                'general' => 'text-gray-600 bg-gray-50'
            ];
            $color = $colors[$notif['type'] ?? 'general'] ?? 'text-gray-600 bg-gray-50';
            ?>
            <div class="notification-item px-6 py-4 hover:bg-gray-50 transition <?= $notif['is_read'] ? '' : 'bg-indigo-50/30 border-l-4 border-indigo-500' ?>" 
                 data-read="<?= $notif['is_read'] ? 'read' : 'unread' ?>"
                 data-type="<?= $notif['type'] ?? 'general' ?>">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-10 h-10 rounded-full <?= $color ?> flex items-center justify-center">
                            <i class="fas <?= $icon ?> text-sm"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm <?= $notif['is_read'] ? 'text-gray-600' : 'text-gray-800 font-medium' ?>">
                                        <?= htmlspecialchars($notif['message']) ?>
                                    </p>
                                    <?php if (!$notif['is_read']): ?>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full font-medium">Nouveau</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <i class="far fa-clock"></i>
                                        <?= formatDate($notif['created_at']) ?>
                                    </span>
                                    <?php if ($notif['is_read']): ?>
                                    <span class="text-xs text-emerald-600 flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Lu
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($notif['link']): ?>
                            <a href="<?= htmlspecialchars($notif['link']) ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium ml-2 flex-shrink-0 flex items-center gap-1 group">
                                Voir <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ============================================ -->
    <!-- DERNIÈRES ACTIVITÉS -->
    <!-- ============================================ -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <i class="fas fa-bolt text-amber-500"></i>
                Dernières activités
                <span class="text-xs text-gray-400 font-normal ml-2">(<?= count($activities) ?>)</span>
            </h2>
        </div>
        
        <?php if (empty($activities)): ?>
        <div class="p-12 text-center text-gray-400">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-inbox text-3xl text-gray-300"></i>
            </div>
            <p class="text-sm">Aucune activité récente</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto custom-scroll">
            <?php foreach ($activities as $activity): ?>
            <?php
            $isAction = !empty($activity['is_action']);
            $isStatusChange = in_array($activity['action_type'] ?? '', ['en_cours', 'resolu', 'en_attente', 'cloture']);
            
            if ($isStatusChange) {
                $icon = 'fa-exchange-alt';
                $color = 'bg-amber-100 text-amber-600';
                $label = 'Changement de statut';
            } elseif ($isAction) {
                $icon = 'fa-bolt';
                $color = 'bg-purple-100 text-purple-600';
                $label = 'Action';
            } else {
                $icon = 'fa-comment';
                $color = 'bg-blue-100 text-blue-600';
                $label = 'Commentaire';
            }
            
            $avatarColor = getRoleAvatarClass($activity['role'] ?? 'commercial');
            ?>
            <div class="px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-9 h-9 rounded-full <?= $avatarColor ?> flex items-center justify-center text-white text-sm font-bold shadow-sm">
                            <?= strtoupper(substr($activity['full_name'] ?? 'U', 0, 2)) ?>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium text-sm text-gray-900">
                                <?= htmlspecialchars($activity['full_name'] ?? 'Inconnu') ?>
                            </span>
                            <span class="text-xs text-gray-400">
                                (<?= getRoleLabel($activity['role'] ?? 'commercial') ?>)
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full <?= $color ?>">
                                <i class="fas <?= $icon ?> mr-1"></i>
                                <?= $label ?>
                            </span>
                        </div>
                        <div class="mt-1">
                            <a href="index.php?page=tickets&action=show&id=<?= $activity['ticket_id'] ?>" 
                               class="text-indigo-600 hover:text-indigo-800 font-medium text-sm hover:underline">
                                  <?= htmlspecialchars($activity['ticket_number'] ?? 'N/A') ?>
                            </a>
                            <span class="text-sm text-gray-600">
                                - <?= htmlspecialchars($activity['ticket_title'] ?? 'Sans titre') ?>
                            </span>
                            <span class="text-xs text-gray-400 ml-2">
                                (<?= getStatusLabel($activity['status'] ?? 'nouveau') ?>)
                            </span>
                        </div>
                        <?php if (!empty($activity['content'])): ?>
                        <div class="mt-2 bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <p class="text-sm text-gray-600">
                                <?= nl2br(htmlspecialchars(substr($activity['content'], 0, 150))) ?>
                                <?= strlen($activity['content']) > 150 ? '...' : '' ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                            <i class="far fa-clock"></i>
                            <?= formatDate($activity['created_at']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS -->
<!-- ============================================ -->
<script>
/**
 * Filtrer les notifications
 */
function filterNotifications(type) {
    const items = document.querySelectorAll('.notification-item');
    const countEl = document.getElementById('notifCount');
    let visibleCount = 0;
    
    // Mettre à jour les boutons de filtre
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700');
        if (btn.dataset.filter === type) {
            btn.classList.remove('bg-gray-100', 'text-gray-700');
            btn.classList.add('bg-indigo-600', 'text-white');
        }
    });
    
    items.forEach(item => {
        const readStatus = item.dataset.read;
        const itemType = item.dataset.type;
        
        let show = true;
        
        if (type === 'unread' && readStatus !== 'unread') show = false;
        if (type === 'read' && readStatus !== 'read') show = false;
        if (type === 'ticket' && itemType !== 'ticket') show = false;
        if (type === 'comment' && itemType !== 'comment') show = false;
        if (type === 'status' && itemType !== 'status') show = false;
        if (type === 'action' && itemType !== 'action') show = false;
        
        if (show) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    if (countEl) {
        countEl.textContent = `(${visibleCount})`;
    }
}

/**
 * Marquer toutes les notifications comme lues
 */
function markAllRead() {
    const btn = document.querySelector('[onclick="markAllRead()"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
    }
    
    fetch('index.php?page=api&action=mark_all_read', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('✅ Toutes les notifications ont été marquées comme lues', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ Erreur lors de l\'opération', 'danger');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-double"></i> Tout marquer comme lu';
                }
            }
        })
        .catch(err => {
            console.error('Erreur:', err);
            showToast('❌ Erreur lors de l\'opération', 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-double"></i> Tout marquer comme lu';
            }
        });
}

/**
 * Confirmer la suppression de toutes les notifications
 */
function confirmDeleteAllNotifications() {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer TOUTES vos notifications ?\n\nCette action est irréversible.')) {
        deleteAllNotifications();
    }
}

/**
 * Supprimer toutes les notifications
 */
function deleteAllNotifications() {
    const btn = document.querySelector('[onclick="confirmDeleteAllNotifications()"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
    }
    
    fetch('index.php?page=api&action=delete_all_notifications', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('🗑️ Toutes les notifications ont été supprimées', 'info');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ Erreur lors de la suppression', 'danger');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer tout';
                }
            }
        })
        .catch(err => {
            console.error('Erreur:', err);
            showToast('❌ Erreur lors de la suppression', 'danger');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer tout';
            }
        });
}

/**
 * Afficher un toast
 */
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
</script>

<style>
/* ============================================ */
/* STYLES POUR LA PAGE D'HISTORIQUE */
/* ============================================ */

.page-enter {
    animation: pageEnter 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes pageEnter {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.flash-message {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: slideDown 0.4s ease-out;
}
.flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.flash-danger { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
.flash-warning { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: pointer;
    text-decoration: none;
}
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}
.btn-primary {
    background: #4F46E5;
    color: white;
}
.btn-primary:hover {
    background: #4338CA;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}
.btn-danger {
    background: #EF4444;
    color: white;
}
.btn-danger:hover {
    background: #DC2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.btn-outline {
    background: transparent;
    color: #6B7280;
    border: 1.5px solid #E5E7EB;
}
.btn-outline:hover {
    background: #F9FAFB;
    border-color: #D1D5DB;
    transform: translateY(-2px);
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

/* Filtres actifs */
.filter-btn.active {
    background: #4F46E5 !important;
    color: white !important;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>