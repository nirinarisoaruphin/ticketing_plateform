<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-slate-50 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-history text-gray-600 mr-2"></i>
                        Historique de l'intervention
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Ticket <?= htmlspecialchars($intervention['ticket_number'] ?? 'N/A') ?>
                    </p>
                </div>
                <a href="index.php?page=planning" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Retour
                </a>
            </div>
        </div>
        
        <div class="p-6">
            <?php if (empty($history)): ?>
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-inbox text-4xl block mb-3"></i>
                <p>Aucun historique pour cette intervention.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($history as $item): ?>
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex-shrink-0 mt-0.5">
                        <?php 
                        $icons = [
                            'Démarrée' => 'fa-play text-green-600',
                            'Terminée' => 'fa-check text-green-600',
                            'Annulée' => 'fa-times text-red-600',
                            'Reportée' => 'fa-calendar-plus text-orange-600',
                            'Note ajoutée' => 'fa-sticky-note text-purple-600',
                            'Mise en pause' => 'fa-pause text-yellow-600',
                            'Reprise' => 'fa-play text-blue-600'
                        ];
                        $icon = $icons[$item['action']] ?? 'fa-clock text-gray-600';
                        ?>
                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($item['action']) ?></span>
                                <p class="text-sm text-gray-600 mt-0.5"><?= nl2br(htmlspecialchars($item['details'] ?? '')) ?></p>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0 ml-4">
                                <?= formatDate($item['created_at']) ?>
                            </span>
                        </div>
                        <?php if (!empty($item['user_id'])): 
                            $user = $db->fetch("SELECT full_name FROM users WHERE id = ?", [$item['user_id']]);
                        ?>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-user mr-1"></i>
                            Par <?= htmlspecialchars($user['full_name'] ?? 'Inconnu') ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>