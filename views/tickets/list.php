<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <!--<i class="fas fa-list text-indigo-600 mr-2"></i>!-->Tickets
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                <?php 
                $role = $_SESSION['user_role'] ?? 'commercial';
                if ($role === 'commercial') {
                    echo ' Vos tickets';
                } elseif ($role === 'responsable_support_technique') {
                    echo ' Support Technique & Bureau d\'Étude';
                } elseif ($role === 'responsable_sav') {
                    echo ' SAV';
                } elseif ($role === 'responsable_travaux') {
                    echo ' Travaux';
                } elseif ($role === 'coordinateur') {
                    echo ' Tous les tickets - Coordination';
                } elseif ($role === 'admin') {
                    echo ' Administration des tickets';
                } elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
                    echo ' Mes tickets assignés';
                } else {
                    echo ' Liste des tickets';
                }
                ?>
            </p>
        </div>
        <div class="flex gap-2 mt-2 sm:mt-0 flex-wrap">
            <?php if (in_array($role, ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation', 'commercial'])): ?>
            <a href="index.php?page=tickets&action=create" 
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-2"></i> Nouveau ticket
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-message flash-<?= $flash['type'] ?> mb-4 rounded-lg">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?> mr-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="page" value="tickets">
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider">Statut</label>
                <select name="status" class="mt-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">Tous</option>
                    <option value="nouveau" <?= ($_GET['status'] ?? '') === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                    <option value="assigne" <?= ($_GET['status'] ?? '') === 'assigne' ? 'selected' : '' ?>>Assigné</option>
                    <option value="en_cours" <?= ($_GET['status'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="en_attente" <?= ($_GET['status'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="resolu" <?= ($_GET['status'] ?? '') === 'resolu' ? 'selected' : '' ?>>Résolu</option>
                    <option value="cloture" <?= ($_GET['status'] ?? '') === 'cloture' ? 'selected' : '' ?>>Clôturé</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider">Priorité</label>
                <select name="priority" class="mt-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">Toutes</option>
                    <option value="basse" <?= ($_GET['priority'] ?? '') === 'basse' ? 'selected' : '' ?>>Basse</option>
                    <option value="moyenne" <?= ($_GET['priority'] ?? '') === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                    <option value="haute" <?= ($_GET['priority'] ?? '') === 'haute' ? 'selected' : '' ?>>Haute</option>
                    <option value="critique" <?= ($_GET['priority'] ?? '') === 'critique' ? 'selected' : '' ?>>Critique</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-200 hover:bg-gray-300 px-4 py-1.5 rounded-lg text-sm transition font-medium">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            <a href="index.php?page=tickets" class="text-gray-500 hover:text-gray-700 text-sm py-1.5 transition">
                <i class="fas fa-undo mr-1"></i> Réinitialiser
            </a>
        </form>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <?php if (empty($tickets)): ?>
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-3 block"></i>
                <p class="text-lg font-medium">Aucun ticket trouvé</p>
                <?php 
                $role = $_SESSION['user_role'] ?? 'commercial';
                if ($role === 'commercial'): ?>
                    <p class="text-sm text-gray-400 mt-1">Vous n'avez pas encore créé de ticket.</p>
                    <a href="index.php?page=tickets&action=create" class="mt-3 inline-block text-indigo-600 hover:text-indigo-800">
                        Créer votre premier ticket →
                    </a>
                <?php elseif ($role === 'responsable_support_technique' || $role === 'responsable_sav' || $role === 'responsable_travaux'): ?>
                    <p class="text-sm text-gray-400 mt-1">Aucun ticket dans votre catégorie.</p>
                <?php elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])): ?>
                    <p class="text-sm text-gray-400 mt-1">Aucun ticket ne vous a été assigné.</p>
                <?php else: ?>
                    <p class="text-sm text-gray-400 mt-1">Aucun ticket dans le système.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priorité</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigné à</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($tickets as $ticket): ?>
                        <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='index.php?page=tickets&action=show&id=<?= $ticket['id'] ?>'">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                <?= htmlspecialchars($ticket['ticket_number']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate">
                                <?= htmlspecialchars($ticket['title']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?= getCategoryLabel($ticket['category']) ?>
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
                                <?php 
                                $assignedUsers = $ticket['assigned_users'] ?? [];
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2" onclick="event.stopPropagation();">
                                <a href="index.php?page=tickets&action=show&id=<?= $ticket['id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-800 transition" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (in_array($role, ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux'])): ?>
                                <a href="index.php?page=tickets&action=edit&id=<?= $ticket['id'] ?>" 
                                   class="text-green-600 hover:text-green-800 transition" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (canDeleteTicket($ticket)): ?>
                                    <?php if ($ticket['status'] !== 'cloture'): ?>
                                    <a href="index.php?page=tickets&action=delete&id=<?= $ticket['id'] ?>" 
                                       onclick="return confirmDeleteTicket('<?= htmlspecialchars($ticket['ticket_number']) ?>')"
                                       class="text-red-600 hover:text-red-800 transition" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <span class="text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Total : <strong><?= count($tickets) ?></strong> ticket(s)
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDeleteTicket(ticketNumber) {
    return confirm(
        '⚠️ Êtes-vous sûr de vouloir supprimer le ticket ' + ticketNumber + ' ?\n\n' +
        'Cette action est irréversible.\n' +
        'Tous les commentaires et données associés seront supprimés.'
    );
}
</script>

<style>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>