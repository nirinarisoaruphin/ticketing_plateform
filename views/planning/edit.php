<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- En-tête -->
        <div class="px-6 py-4 bg-gradient-to-r from-amber-50 to-yellow-50 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                <i class="fas fa-edit text-amber-600"></i>
                Modifier l'intervention
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Ticket  <?= htmlspecialchars($intervention['ticket_number'] ?? 'N/A') ?>
            </p>
        </div>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="m-6 p-4 rounded-lg flash-message flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?> mr-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="p-6 space-y-5">
            
            <!-- ============================================ -->
            <!-- 1. RESPONSABLE - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Responsable <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 ml-2">(Lecture seule)</span>
                </label>
                <?php
                // Récupérer le nom du responsable
                $technicianName = '';
                $technicianRole = '';
                if (!empty($intervention['technician_id'])) {
                    $db = Database::getInstance();
                    $tech = $db->fetch("SELECT full_name, role FROM users WHERE id = ?", [$intervention['technician_id']]);
                    if ($tech) {
                        $technicianName = $tech['full_name'];
                        $technicianRole = $tech['role'];
                    }
                }
                ?>
                <input type="text" value="<?= htmlspecialchars($technicianName) ?> (<?= getRoleLabel($technicianRole) ?>)" 
                       disabled
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                <input type="hidden" name="technician_id" value="<?= $intervention['technician_id'] ?>">
                <p class="text-xs text-gray-400 mt-1">Le responsable est automatiquement assigné selon la catégorie du ticket</p>
            </div>
            
            <!-- ============================================ -->
            <!-- 2. DATE ET HEURE -->
            <!-- ============================================ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="planned_date" required value="<?= $intervention['planned_date'] ?>"
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Heure <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="planned_time" required value="<?= substr($intervention['planned_time'], 0, 5) ?>"
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <p class="text-xs text-gray-400 mt-1">Toutes les heures sont acceptées</p>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- 3. DURÉE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Durée (minutes)</label>
                <select name="duration" class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="15" <?= $intervention['duration'] == 15 ? 'selected' : '' ?>>15 min</option>
                    <option value="30" <?= $intervention['duration'] == 30 ? 'selected' : '' ?>>30 min</option>
                    <option value="45" <?= $intervention['duration'] == 45 ? 'selected' : '' ?>>45 min</option>
                    <option value="60" <?= $intervention['duration'] == 60 ? 'selected' : '' ?>>1 heure</option>
                    <option value="90" <?= $intervention['duration'] == 90 ? 'selected' : '' ?>>1h30</option>
                    <option value="120" <?= $intervention['duration'] == 120 ? 'selected' : '' ?>>2 heures</option>
                    <option value="180" <?= $intervention['duration'] == 180 ? 'selected' : '' ?>>3 heures</option>
                    <option value="240" <?= $intervention['duration'] == 240 ? 'selected' : '' ?>>4 heures</option>
                </select>
            </div>
            
            <!-- ============================================ -->
            <!-- 4. STATUT -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Statut</label>
                <select name="status" class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="planifiee" <?= $intervention['status'] === 'planifiee' ? 'selected' : '' ?>>Planifiée</option>
                    <option value="en_cours" <?= $intervention['status'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="realisee" <?= $intervention['status'] === 'realisee' ? 'selected' : '' ?>>Réalisée</option>
                    <option value="annulee" <?= $intervention['status'] === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                </select>
            </div>
            
            <!-- ============================================ -->
            <!-- 5. NOTES -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="3" 
                          class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"><?= htmlspecialchars($intervention['notes'] ?? '') ?></textarea>
            </div>
            
            <!-- ============================================ -->
            <!-- 6. BOUTONS -->
            <!-- ============================================ -->
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition shadow-md hover:shadow-lg font-medium flex items-center gap-2">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
                <a href="index.php?page=planning" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition font-medium flex items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
            
        </form>
    </div>
</div>

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
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>