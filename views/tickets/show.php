<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<?php
$role = $_SESSION['user_role'] ?? 'commercial';
$userId = $_SESSION['user_id'] ?? 0;

$canAct = $canAct ?? false;
$canAssign = $canAssign ?? false;
$chargesEtude = $chargesEtude ?? [];
$assignedUsers = $assignedUsers ?? [];

// ✅ GÉNÉRER LE LIEN WHATSAPP
function getWhatsAppLink($ticket) {
    $phoneNumber = '261340000001'; // ⚠️ REMPLACER PAR VOTRE NUMÉRO (format international sans le +)
    
    $message = 
        "📋 Ticket " . ($ticket['ticket_number'] ?? 'N/A') . 
        "\n📝 Titre : " . ($ticket['title'] ?? 'Sans titre') . 
        "\n📊 Statut : " . getStatusLabel($ticket['status'] ?? 'nouveau') . 
        "\n🎯 Priorité : " . getPriorityLabel($ticket['priority'] ?? 'moyenne') . 
        "\n📂 Catégorie : " . getCategoryLabel($ticket['category'] ?? 'general') .
        "\n👤 Créé par : " . ($ticket['created_by_name'] ?? 'Inconnu') . 
        "\n📅 Date : " . formatDate($ticket['created_at'] ?? date('Y-m-d H:i:s')) .
        "\n\n🔗 " . APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
    
    return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
}

$whatsappLink = isset($ticket) ? getWhatsAppLink($ticket) : '#';
?>

<div class="page-wrapper">
    <div class="content max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 page-enter">
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="flash-message flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
            
            <?php if ($flash['type'] === 'success' && isset($flash['extra'])): ?>
            <div class="mt-2 flex flex-wrap gap-2">
                <a href="<?= $flash['extra'] ?>" target="_blank" 
                   class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition shadow-md">
                    <i class="fab fa-whatsapp text-xl"></i>
                    Partager sur WhatsApp
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- EN-TÊTE DU TICKET -->
        <!-- ============================================ -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($ticket['ticket_number']) ?></h1>
                            <?php if ($ticket['status'] === 'cloture' || $ticket['status'] === 'resolu'): ?>
                            <span class="badge badge-status-<?= $ticket['status'] ?>">
                                <i class="fas fa-check-circle mr-1"></i>
                                <?= getStatusLabel($ticket['status']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="far fa-user mr-1"></i>
                            Créé par <strong><?= htmlspecialchars($ticket['created_by_name']) ?></strong>
                            le <?= formatDate($ticket['created_at']) ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php if ($canEdit && $ticket['status'] !== 'cloture' && $role !== 'commercial'): ?>
                        <a href="index.php?page=tickets&action=edit&id=<?= $ticket['id'] ?>" 
                           class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <?php endif; ?>
                        
                        <?php if (function_exists('canDeleteTicket') && canDeleteTicket($ticket) && $ticket['status'] !== 'cloture'): ?>
                        <a href="index.php?page=tickets&action=delete&id=<?= $ticket['id'] ?>" 
                           onclick="return confirmDeleteTicket('<?= htmlspecialchars($ticket['ticket_number']) ?>')"
                           class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                        <?php endif; ?>
                        
                        <a href="index.php?page=tickets" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- CORPS DU TICKET -->
            <!-- ============================================ -->
            <div class="px-6 py-4">
                
                <!-- Badges -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="badge badge-status-<?= $ticket['status'] ?>">
                        <i class="fas fa-circle mr-1"></i>
                        <?= getStatusLabel($ticket['status']) ?>
                    </span>
                    <span class="badge badge-priority-<?= $ticket['priority'] ?>">
                        <i class="fas fa-flag mr-1"></i><?= getPriorityLabel($ticket['priority']) ?>
                    </span>
                    <span class="badge bg-blue-100 text-blue-800">
                        <i class="fas fa-tag mr-1"></i><?= getCategoryLabel($ticket['category']) ?>
                    </span>
                    <?php if (!empty($ticket['type_demande'])): ?>
                    <span class="badge bg-indigo-100 text-indigo-800">
                        <i class="fas fa-file-alt mr-1"></i><?= ucfirst($ticket['type_demande']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?= htmlspecialchars($ticket['title']) ?></h2>
                
                <!-- ============================================ -->
                <!-- INFORMATIONS DU TICKET -->
                <!-- ============================================ -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="space-y-3">
                        <?php if (!empty($ticket['client_name'])): ?>
                        <div class="flex items-start gap-2">
                            <span class="text-sm text-gray-500 w-32 flex-shrink-0">Intitulé client</span>
                            <span class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($ticket['client_name']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($ticket['adresse_client'])): ?>
                        <div class="flex items-start gap-2">
                            <span class="text-sm text-gray-500 w-32 flex-shrink-0">Adresse client</span>
                            <span class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($ticket['adresse_client']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($ticket['commercial_dedie'])): ?>
                        <div class="flex items-start gap-2">
                            <span class="text-sm text-gray-500 w-32 flex-shrink-0">Demandeur</span>
                            <span class="text-sm text-gray-800 font-medium"><?= htmlspecialchars($ticket['commercial_dedie']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-3">
                        <!-- ASSIGNATION -->
                        <div class="flex items-start gap-2">
                            <span class="text-sm text-gray-500 w-32 flex-shrink-0">Assigné à</span>
                            <span class="text-sm text-gray-800 font-medium">
                                <?php if (!empty($assignedUsers)): ?>
                                    <?php foreach ($assignedUsers as $au): ?>
                                    <span class="inline-block bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs mr-1">
                                        <?= htmlspecialchars($au['full_name']) ?>
                                    </span>
                                    <?php endforeach; ?>
                                <?php elseif (!empty($ticket['assigned_to_name'])): ?>
                                    <?= htmlspecialchars($ticket['assigned_to_name']) ?>
                                    <?php if (in_array($ticket['category'], ['sav', 'travaux'])): ?>
                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full ml-1">Responsable</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">Non assigné</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="flex items-start gap-2">
                            <span class="text-sm text-gray-500 w-32 flex-shrink-0">Date de création</span>
                            <span class="text-sm text-gray-800 font-medium"><?= formatDate($ticket['created_at']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <?php if (!empty($ticket['description'])): ?>
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>Description
                    </h4>
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-gray-700 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($ticket['attachment']): ?>
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center gap-3">
                    <i class="fas fa-paperclip text-gray-400"></i>
                    <a href="<?= htmlspecialchars($ticket['attachment']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">
                        Voir la pièce jointe
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Lien vers les messages -->
                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-800">
                            <i class="fas fa-comments mr-2"></i>Discussions
                        </p>
                        <p class="text-xs text-blue-600">
                            Voir tous les échanges sur ce ticket dans la page Messages
                        </p>
                    </div>
                    <a href="index.php?page=messages&ticket_id=<?= $ticket['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right mr-1"></i> Voir les messages
                    </a>
                </div>
                
                <!-- ============================================ -->
                <!-- ✅ BOUTON WHATSAPP -->
                <!-- ============================================ -->
                <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-green-800">
                            <i class="fab fa-whatsapp text-xl text-green-600 mr-2"></i>
                            Partager ce ticket
                        </p>
                        <p class="text-xs text-green-600">
                            Envoyez les informations du ticket via WhatsApp
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="<?= $whatsappLink ?>" target="_blank" 
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg transition shadow-md hover:shadow-lg flex items-center gap-2 font-medium">
                            <i class="fab fa-whatsapp text-xl"></i>
                            Partager sur WhatsApp
                        </a>
                        <!-- Bouton copier le lien (optionnel) -->
                        <button onclick="copyToClipboard('<?= APP_URL ?>/index.php?page=tickets&action=show&id=<?= $ticket['id'] ?>')" 
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2.5 rounded-lg transition flex items-center gap-2 font-medium">
                            <i class="fas fa-copy"></i>
                            Copier le lien
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- ACTIONS SUR LE TICKET -->
        <!-- ============================================ -->
        <?php if ($canAct && $ticket['status'] !== 'cloture' && $role !== 'commercial'): ?>
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                <h3 class="text-sm font-semibold text-gray-700">
                    <i class="fas fa-tools text-blue-500 mr-2"></i>
                    Actions sur le ticket
                </h3>
            </div>
            <div class="px-6 py-4">
                <form method="POST" action="index.php?page=tickets&action=action" class="space-y-4">
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <button type="submit" name="action_type" value="en_cours"
                                class="px-4 py-2.5 text-sm font-medium rounded-lg transition bg-purple-100 text-purple-700 hover:bg-purple-200 border-2 border-transparent hover:border-purple-300">
                            <i class="fas fa-spinner mr-1"></i> En cours
                        </button>
                        <button type="submit" name="action_type" value="resolu"
                                class="px-4 py-2.5 text-sm font-medium rounded-lg transition bg-green-100 text-green-700 hover:bg-green-200 border-2 border-transparent hover:border-green-300">
                            <i class="fas fa-check-circle mr-1"></i> Résolu
                        </button>
                        <button type="submit" name="action_type" value="en_attente"
                                class="px-4 py-2.5 text-sm font-medium rounded-lg transition bg-yellow-100 text-yellow-700 hover:bg-yellow-200 border-2 border-transparent hover:border-yellow-300">
                            <i class="fas fa-clock mr-1"></i> En attente
                        </button>
                        <button type="submit" name="action_type" value="signaler_probleme"
                                class="px-4 py-2.5 text-sm font-medium rounded-lg transition bg-red-100 text-red-700 hover:bg-red-200 border-2 border-transparent hover:border-red-300">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Signaler
                        </button>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message (optionnel)</label>
                        <textarea name="content" rows="2" 
                                  class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                  placeholder="Ajoutez un message pour cette action..."></textarea>
                    </div>
                    
                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-md hover:shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i> Valider l'action
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- HISTORIQUE DES ACTIONS -->
        <!-- ============================================ -->
        <?php
        $db = Database::getInstance();
        $actions = $db->fetchAll(
            "SELECT c.*, u.full_name, u.role 
             FROM comments c 
             INNER JOIN users u ON c.user_id = u.id 
             WHERE c.ticket_id = ? AND c.is_action = 1
             ORDER BY c.created_at DESC",
            [$ticket['id']]
        );
        ?>
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-700">
                    <i class="fas fa-history text-blue-500 mr-2"></i>
                    Historique des actions
                </h3>
                <span class="text-xs text-gray-400"><?= count($actions) ?> action(s)</span>
            </div>
            <div class="px-6 py-4 max-h-64 overflow-y-auto">
                <?php if (empty($actions)): ?>
                <p class="text-gray-400 text-sm text-center py-4">Aucune action enregistrée</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($actions as $action): 
                        $actionLabels = [
                            'signaler_probleme' => ['label' => '⚠️ Problème signalé', 'class' => 'text-red-600 bg-red-50'],
                            'notifier_client' => ['label' => '📢 Client notifié', 'class' => 'text-emerald-600 bg-emerald-50'],
                            'demander_info' => ['label' => '❓ Info demandée', 'class' => 'text-blue-600 bg-blue-50'],
                            'escalader' => ['label' => '⬆️ Escaladé', 'class' => 'text-orange-600 bg-orange-50'],
                            'resolu' => ['label' => '✅ Résolu', 'class' => 'text-green-600 bg-green-50'],
                            'en_cours' => ['label' => '🔄 En cours', 'class' => 'text-purple-600 bg-purple-50'],
                            'en_attente' => ['label' => '⏳ En attente', 'class' => 'text-yellow-600 bg-yellow-50'],
                            'commentaire' => ['label' => '💬 Commentaire', 'class' => 'text-gray-600 bg-gray-50']
                        ];
                        $actionInfo = $actionLabels[$action['action_type']] ?? ['label' => $action['action_type'], 'class' => 'bg-gray-50'];
                    ?>
                    <div class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold <?= $actionInfo['class'] ?>">
                                <?= strtoupper(substr($action['full_name'], 0, 2)) ?>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-1">
                                <span class="font-medium text-xs text-gray-800"><?= htmlspecialchars($action['full_name']) ?></span>
                                <span class="text-[10px] text-gray-400">(<?= ucfirst($action['role']) ?>)</span>
                                <span class="px-1.5 py-0.5 text-[10px] rounded-full <?= $actionInfo['class'] ?>">
                                    <?= $actionInfo['label'] ?>
                                </span>
                            </div>
                            <?php if (!empty($action['content'])): ?>
                            <p class="text-xs text-gray-600 mt-0.5"><?= nl2br(htmlspecialchars($action['content'])) ?></p>
                            <?php endif; ?>
                            <p class="text-[10px] text-gray-400 mt-0.5"><?= formatDate($action['created_at']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<style>
.page-enter {
    animation: pageEnter 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes pageEnter {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.flash-message {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    flex-direction: column;
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
    background: #2563EB;
    color: white;
}
.btn-primary:hover {
    background: #1D4ED8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1.5;
}
.badge-status-nouveau { background: #DBEAFE; color: #1E40AF; }
.badge-status-assigne { background: #EDE9FE; color: #5B21B6; }
.badge-status-en_cours { background: #FEF3C7; color: #92400E; }
.badge-status-en_attente { background: #FED7AA; color: #9A3412; }
.badge-status-resolu { background: #D1FAE5; color: #065F46; }
.badge-status-cloture { background: #F3F4F6; color: #374151; }
.badge-priority-basse { background: #F3F4F6; color: #374151; }
.badge-priority-moyenne { background: #DBEAFE; color: #1E40AF; }
.badge-priority-haute { background: #FEF3C7; color: #92400E; }
.badge-priority-critique { background: #FEE2E2; color: #991B1B; }
</style>

<script>
function confirmDeleteTicket(ticketNumber) {
    return confirm(
        '⚠️ Êtes-vous sûr de vouloir supprimer le ticket ' + ticketNumber + ' ?\n\n' +
        'Cette action est irréversible.\n' +
        'Tous les commentaires et données associés seront supprimés.'
    );
}

// Fonction pour copier le lien dans le presse-papier
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('✅ Lien copié dans le presse-papier !', 'success');
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
        showToast('✅ Lien copié dans le presse-papier !', 'success');
    } catch (e) {
        showToast('❌ Impossible de copier', 'danger');
    }
    document.body.removeChild(input);
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
            if (toast.parentNode) {
                toast.remove();
                if (container.children.length === 0) container.remove();
            }
        }, 500);
    }, 4000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>