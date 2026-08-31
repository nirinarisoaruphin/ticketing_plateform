<?php 
require_once __DIR__ . '/../../includes/header.php'; 
require_once __DIR__ . '/../../includes/navbar.php'; 
require_once __DIR__ . '/../../config/app.php';

$role = $_SESSION['user_role'] ?? 'commercial';
$userName = $_SESSION['user_name'] ?? 'Utilisateur';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- ============================================ -->
    <!-- EN-TÊTE -->
    <!-- ============================================ -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span>Tableau de bord</span>
                <span class="text-sm font-medium text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
                    <?= date('d/m/Y') ?>
                </span>
            </h1>
            <p class="text-gray-500 mt-1">
                Bonjour, <strong><?= htmlspecialchars($userName) ?></strong>
                <span class="text-sm text-gray-400 ml-2">(<?= getRoleLabel($role) ?>)</span>
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="index.php?page=tickets&action=create" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-2"></i>Nouveau ticket
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-message flash-<?= $flash['type'] ?> mb-6">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- STATISTIQUES - COMMERCIAL -->
    <?php if ($role === 'commercial'): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="stat-card primary">
            <div class="stat-icon bg-indigo-50 text-indigo-600">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['total'] ?? 0 ?>">0</p>
            <p class="stat-label">Mes tickets</p>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon bg-amber-50 text-amber-600">
                <i class="fas fa-clock"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['en_attente'] ?? 0 ?>">0</p>
            <p class="stat-label">En attente</p>
        </div>
        
        <div class="stat-card purple">
            <div class="stat-icon bg-purple-50 text-purple-600">
                <i class="fas fa-spinner"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['en_cours'] ?? 0 ?>">0</p>
            <p class="stat-label">En cours</p>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <i class="fas fa-check-circle"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['resolu'] ?? 0 ?>">0</p>
            <p class="stat-label">Résolus</p>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-icon bg-red-50 text-red-600">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['critique'] ?? 0 ?>">0</p>
            <p class="stat-label">Critiques</p>
        </div>
    </div>

    <!-- STATISTIQUES - ADMIN, COORDINATEUR, RESPONSABLES, CHARGÉS -->
    <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="stat-card primary">
            <div class="stat-icon bg-indigo-50 text-indigo-600">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['total'] ?? 0 ?>">0</p>
            <p class="stat-label">Total tickets</p>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon bg-amber-50 text-amber-600">
                <i class="fas fa-clock"></i>
            </div>
            <p class="stat-number" data-target="<?= ($stats['nouveau'] ?? 0) + ($stats['assigne'] ?? 0) ?>">0</p>
            <p class="stat-label">En attente</p>
        </div>
        
        <div class="stat-card purple">
            <div class="stat-icon bg-purple-50 text-purple-600">
                <i class="fas fa-spinner"></i>
            </div>
            <p class="stat-number" data-target="<?= ($stats['en_cours'] ?? 0) + ($stats['en_attente'] ?? 0) ?>">0</p>
            <p class="stat-label">En cours</p>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <i class="fas fa-check-circle"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['resolu'] ?? 0 ?>">0</p>
            <p class="stat-label">Résolus</p>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-icon bg-red-50 text-red-600">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['critique'] ?? 0 ?>">0</p>
            <p class="stat-label">Critiques</p>
        </div>
        
        <div class="stat-card" style="border-color: #8B5CF6;">
            <div class="stat-icon bg-purple-50 text-purple-600">
                <i class="fas fa-chart-simple"></i>
            </div>
            <p class="stat-number" data-target="<?= $stats['resolution_rate'] ?? 0 ?>">0</p>
            <p class="stat-label">Taux de résolution</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABLEAU DES TICKETS RÉCENTS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-clock text-indigo-500 mr-2"></i>
                <?php 
                if ($role === 'responsable_support_technique') {
                    echo 'Tickets Support Technique & Bureau d\'Étude';
                } elseif ($role === 'responsable_sav') {
                    echo 'Tickets SAV';
                } elseif ($role === 'responsable_travaux') {
                    echo 'Tickets Travaux';
                } elseif ($role === 'charge_etude_electricite' || $role === 'charge_etude_courant_faible' || $role === 'charge_etude_climatisation') {
                    echo 'Mes tickets assignés';
                } elseif ($role === 'commercial') {
                    echo 'Mes tickets';
                } else {
                    echo 'Tickets récents';
                }
                ?>
            </h2>
            <a href="index.php?page=tickets" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                Voir tout →
            </a>
        </div>
        
        <?php if (empty($recentTickets)): ?>
        <div class="p-8 text-center text-gray-400">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
            <p class="text-sm">
                <?php 
                if ($role === 'responsable_support_technique') {
                    echo 'Aucun ticket Support Technique ou Bureau d\'Étude.';
                } elseif ($role === 'responsable_sav') {
                    echo 'Aucun ticket SAV.';
                } elseif ($role === 'responsable_travaux') {
                    echo 'Aucun ticket Travaux.';
                } elseif ($role === 'charge_etude_electricite' || $role === 'charge_etude_courant_faible' || $role === 'charge_etude_climatisation') {
                    echo 'Aucun ticket ne vous a été assigné.';
                } elseif ($role === 'commercial') {
                    echo 'Vous n\'avez pas encore créé de ticket.';
                } else {
                    echo 'Aucun ticket dans le système.';
                }
                ?>
            </p>
            <?php if ($role === 'commercial'): ?>
            <a href="index.php?page=tickets&action=create" class="mt-3 inline-block text-indigo-600 hover:text-indigo-800 text-sm">
                Créer votre premier ticket →
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priorité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé par</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigné à</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                        <!-- ✅ NOUVELLE COLONNE WHATSAPP -->
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">WhatsApp</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($recentTickets as $ticket): ?>
                    <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='index.php?page=tickets&action=show&id=<?= $ticket['id'] ?>'">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                            <?= htmlspecialchars($ticket['ticket_number']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-800 max-w-xs truncate">
                            <?= htmlspecialchars($ticket['title']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="badge badge-status-<?= $ticket['status'] ?>">
                                <?= getStatusLabel($ticket['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="badge badge-priority-<?= $ticket['priority'] ?>">
                                <?= getPriorityLabel($ticket['priority']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= htmlspecialchars($ticket['created_by_name'] ?? 'Inconnu') ?>
                        </td>
                        <!-- ✅ COLONNE ASSIGNÉ À - AVEC ASSIGNATIONS MULTIPLES -->
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php 
                            // Récupérer les assignations multiples
                            $assignedUsers = $this->ticketModel->getAssignedUsers($ticket['id'] ?? 0);
                            if (!empty($assignedUsers)): 
                            ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($assignedUsers as $au): ?>
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                        <?= htmlspecialchars($au['full_name']) ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (!empty($ticket['assigned_to_name'])): ?>
                                <?= htmlspecialchars($ticket['assigned_to_name']) ?>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Non assigné</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= formatDate($ticket['created_at']) ?>
                        </td>
                        <!-- ✅ NOUVELLE COLONNE WHATSAPP AVEC BOUTON -->
                        <td class="px-6 py-4 whitespace-nowrap text-center" onclick="event.stopPropagation();">
                            <?php 
                            // ✅ UTILISER LA FONCTION DE config/app.php
                            if (function_exists('getWhatsAppLinkForTicket')) {
                                $whatsappLink = getWhatsAppLinkForTicket($ticket);
                            } else {
                                // Fallback si la fonction n'existe pas
                                $phoneNumber = '261340000001';
                                $message = "📋 Ticket " . ($ticket['ticket_number'] ?? 'N/A') . 
                                          "\n📝 Titre : " . ($ticket['title'] ?? 'Sans titre') . 
                                          "\n🔗 " . APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
                                $whatsappLink = "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
                            }
                            ?>
                            <a href="<?= $whatsappLink ?>" target="_blank" 
                               class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-500 hover:bg-green-600 text-white transition-all hover:scale-110 shadow-sm hover:shadow-md"
                               title="Partager sur WhatsApp">
                                <i class="fab fa-whatsapp text-sm"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- DERNIÈRES ACTIVITÉS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
            <h2 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-bolt text-amber-500 mr-2"></i>
                Dernières activités
            </h2>
        </div>
        
        <?php if (empty($activities)): ?>
        <div class="p-8 text-center text-gray-400">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
            <p class="text-sm">Aucune activité récente</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
            <?php foreach ($activities as $activity): ?>
            <div class="px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-8 h-8 rounded-full <?= 
                            $activity['role'] === 'admin' ? 'bg-red-100 text-red-600' : 
                            ($activity['role'] === 'responsable_support_technique' ? 'bg-indigo-100 text-indigo-600' : 
                            ($activity['role'] === 'responsable_sav' ? 'bg-pink-100 text-pink-600' : 
                            ($activity['role'] === 'responsable_travaux' ? 'bg-amber-100 text-amber-600' : 
                            ($activity['role'] === 'charge_etude_electricite' || $activity['role'] === 'charge_etude_courant_faible' || $activity['role'] === 'charge_etude_climatisation' ? 'bg-emerald-100 text-emerald-600' : 
                            ($activity['role'] === 'commercial' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'))))) 
                        ?> flex items-center justify-center text-sm font-bold">
                            <?= strtoupper(substr($activity['full_name'] ?? 'U', 0, 2)) ?>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800">
                            <span class="font-medium"><?= htmlspecialchars($activity['full_name'] ?? 'Inconnu') ?></span>
                            a commenté
                            <a href="index.php?page=tickets&action=show&id=<?= $activity['ticket_id'] ?>" 
                               class="text-indigo-600 hover:text-indigo-800 font-medium">
                                <?= htmlspecialchars($activity['ticket_number']) ?>
                            </a>
                            <span class="text-xs text-gray-400 ml-1">(<?= getStatusLabel($activity['status']) ?>)</span>
                        </p>
                        <?php if (!empty($activity['content'])): ?>
                        <p class="text-xs text-gray-500 mt-0.5 truncate max-w-md">
                            "<?= htmlspecialchars(substr($activity['content'], 0, 80)) ?><?= strlen($activity['content']) > 80 ? '...' : '' ?>"
                        </p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="far fa-clock mr-1"></i>
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

<!-- STYLES POUR LE BOUTON WHATSAPP -->
<style>
/* Animation du bouton WhatsApp au survol */
.whatsapp-btn {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.whatsapp-btn:hover {
    transform: scale(1.15) rotate(-5deg);
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
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
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.stat-number[data-target]').forEach(el => {
        const target = parseInt(el.dataset.target);
        animateNumber(el, target);
    });
    
    function animateNumber(element, target, duration = 1000) {
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
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>