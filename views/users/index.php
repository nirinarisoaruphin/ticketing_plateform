<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- ============================================ -->
    <!-- EN-TÊTE -->
    <!-- ============================================ -->
    <div class="flex flex-wrap justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <!--<i class="fas fa-users-cog text-indigo-600"></i>!-->
                Gestion des utilisateurs
                <span class="text-sm font-medium text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
                    <?= count($users) ?> utilisateur(s)
                </span>
            </h1>
            <p class="text-gray-500 mt-1">
                <i class="fas fa-info-circle text-indigo-400 mr-1"></i>
                Gérez les comptes utilisateurs de la plateforme
            </p>
        </div>
        <div class="flex space-x-2 mt-2 sm:mt-0">
            <a href="index.php?page=register" class="btn btn-primary">
                <i class="fas fa-user-plus mr-2"></i> Ajouter un utilisateur
            </a>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- STATISTIQUES -->
    <!-- ============================================ -->
    <?php
    $roleCounts = array();
    foreach ($users as $u) {
        $role = $u['role'];
        if (!isset($roleCounts[$role])) {
            $roleCounts[$role] = 0;
        }
        $roleCounts[$role]++;
    }
    ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center">
            <p class="text-xl font-bold text-red-600"><?= $roleCounts['admin'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Administrateurs</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center">
            <p class="text-xl font-bold text-purple-600"><?= $roleCounts['coordinateur'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Coordinateurs</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center">
            <p class="text-xl font-bold text-indigo-600"><?= ($roleCounts['responsable_support_technique'] ?? 0) + ($roleCounts['responsable_sav'] ?? 0) + ($roleCounts['responsable_travaux'] ?? 0) ?></p>
            <p class="text-xs text-gray-500">Responsables</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center">
            <p class="text-xl font-bold text-blue-600"><?= $roleCounts['commercial'] ?? 0 ?></p>
            <p class="text-xs text-gray-500">Commerciaux</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 text-center">
            <p class="text-xl font-bold text-emerald-600"><?= ($roleCounts['charge_etude_electricite'] ?? 0) + ($roleCounts['charge_etude_courant_faible'] ?? 0) + ($roleCounts['charge_etude_climatisation'] ?? 0) ?></p>
            <p class="text-xs text-gray-500">Chargés d'Étude</p>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- FLASH MESSAGES -->
    <!-- ============================================ -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="mb-4 p-4 rounded-lg flash-message flash-<?= $flash['type'] ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
        <?= $flash['message'] ?>
    </div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- TABLEAU DES UTILISATEURS -->
    <!-- ============================================ -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Équipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl text-gray-300 mb-2 block"></i>
                            Aucun utilisateur trouvé.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full <?= getRoleAvatarClass($user['role']) ?> flex items-center justify-center text-white font-bold text-sm">
                                    <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['full_name']) ?></p>
                                    <p class="text-xs text-gray-500">@<?= htmlspecialchars($user['username']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="px-6 py-4">
                            <?php 
                            // ✅ UTILISER getRoleLabel() DIRECTEMENT SANS getRoleColor()
                            $roleColor = [
                                'admin' => '#EF4444',
                                'coordinateur' => '#8B5CF6',
                                'responsable_support_technique' => '#4F46E5',
                                'responsable_sav' => '#EC4899',
                                'responsable_travaux' => '#F59E0B',
                                'commercial' => '#3B82F6',
                                'charge_etude_electricite' => '#F97316',
                                'charge_etude_courant_faible' => '#06B6D4',
                                'charge_etude_climatisation' => '#10B981'
                            ];
                            $color = $roleColor[$user['role']] ?? '#6B7280';
                            ?>
                            <span class="badge" style="background:<?= $color ?>20; color:<?= $color ?>;">
                                <i class="fas <?= getRoleIcon($user['role']) ?> mr-1"></i>
                                <?= getRoleLabel($user['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= getUserTeam($user['role']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?= formatDate($user['created_at']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="index.php?page=users&action=edit&id=<?= $user['id'] ?>" 
                               class="text-green-600 hover:text-green-800 transition" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="index.php?page=users&action=reset_password&id=<?= $user['id'] ?>" 
                                   onclick="return confirm('Réinitialiser le mot de passe de <?= htmlspecialchars($user['full_name']) ?> ?')"
                                   class="text-yellow-600 hover:text-yellow-800 transition" title="Réinitialiser">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="index.php?page=users&action=delete&id=<?= $user['id'] ?>" 
                                   onclick="return confirm('Supprimer définitivement <?= htmlspecialchars($user['full_name']) ?> ?')"
                                   class="text-red-600 hover:text-red-800 transition" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-300" title="Vous ne pouvez pas vous supprimer">
                                    <i class="fas fa-trash"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- ============================================ -->
        <!-- FOOTER DU TABLEAU -->
        <!-- ============================================ -->
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
            <p class="text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Total : <strong><?= count($users) ?></strong> utilisateur(s)
            </p>
            <div class="flex flex-wrap gap-4 text-sm text-gray-400">
                <span><i class="fas fa-circle text-red-500"></i> Admin</span>
                <span><i class="fas fa-circle text-purple-500"></i> Coordinateur</span>
                <span><i class="fas fa-circle text-indigo-500"></i> Responsable</span>
                <span><i class="fas fa-circle text-blue-500"></i> Commercial</span>
                <span><i class="fas fa-circle text-emerald-500"></i> Chargé d'Étude</span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>