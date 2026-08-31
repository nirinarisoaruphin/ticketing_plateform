<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- En-tête -->
        <div class="px-6 py-5 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-900">
                Exporter les tickets
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Choisissez le format d'exportation et les filtres pour vos tickets
            </p>
        </div>
        
        <!-- Formulaire -->
        <form method="GET" action="index.php?page=export" class="p-6 space-y-6">
            <input type="hidden" name="page" value="export">
            
            <!-- ============================================ -->
            <!-- 1. CHOIX DU FORMAT - CSV ET PDF UNIQUEMENT -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Format d'exportation <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3 max-w-md">
                    <!-- CSV -->
                    <label class="relative flex items-center justify-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                        <input type="radio" name="format" value="csv" checked class="sr-only peer">
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-xl font-bold text-gray-400 peer-checked:text-green-600 transition">CSV</span>
                            <span class="text-xs text-gray-400">Excel / Tableur</span>
                        </div>
                    </label>
                    
                    <!-- PDF -->
                    <label class="relative flex items-center justify-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-red-400 transition has-[:checked]:border-red-600 has-[:checked]:bg-red-50">
                        <input type="radio" name="format" value="pdf" class="sr-only peer">
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-xl font-bold text-gray-400 peer-checked:text-red-600 transition">PDF</span>
                            <span class="text-xs text-gray-400">Document imprimable</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- 2. FILTRES -->
            <!-- ============================================ -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">
                    Filtres (optionnels)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Statut -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Statut</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="nouveau">Nouveau</option>
                            <option value="assigne">Assigné</option>
                            <option value="en_cours">En cours</option>
                            <option value="en_attente">En attente</option>
                            <option value="resolu">Résolu</option>
                            <option value="cloture">Clôturé</option>
                        </select>
                    </div>
                    
                    <!-- Priorité -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Priorité</label>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            <option value="">Toutes les priorités</option>
                            <option value="basse">Basse</option>
                            <option value="moyenne">Moyenne</option>
                            <option value="haute">Haute</option>
                            <option value="critique">Critique</option>
                        </select>
                    </div>
                    
                    <!-- Catégorie -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Catégorie</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            <option value="">Toutes les catégories</option>
                            <option value="support_technique">Support Technique</option>
                            <option value="bureau_etude">Bureau d'Étude</option>
                            <option value="sav">SAV</option>
                            <option value="travaux">Travaux</option>
                        </select>
                    </div>
                    
                    <!-- Date de début -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Date de début</label>
                        <input type="date" name="date_from" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                    </div>
                    
                    <!-- Date de fin -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Date de fin</label>
                        <input type="date" name="date_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                    </div>
                    
                    <!-- Assigné à -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Assigné à</label>
                        <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            <option value="">Tous</option>
                            <?php
                            $db = Database::getInstance();
                            $users = $db->fetchAll("SELECT id, full_name FROM users WHERE role IN ('responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation') ORDER BY full_name");
                            foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- 3. BOUTONS -->
            <!-- ============================================ -->
            <div class="border-t border-gray-200 pt-6 flex flex-wrap gap-4 justify-between items-center">
                <div class="text-sm text-gray-500">
                    L'exportation inclura les tickets correspondant aux filtres sélectionnés.
                </div>
                <div class="flex gap-3">
                    <a href="index.php?page=dashboard" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition font-medium">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium shadow-sm hover:shadow-md" id="exportBtn">
                        <span id="exportBtnText">Exporter en CSV</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- ============================================ -->
    <!-- INFORMATIONS SUR LES FORMATS -->
    <!-- ============================================ -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-lg font-medium text-gray-700">CSV</p>
            <p class="text-xs text-gray-400">Ouvrable avec Excel, Google Sheets</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-lg font-medium text-gray-700">PDF</p>
            <p class="text-xs text-gray-400">Document imprimable et partageable</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour le texte du bouton selon le format sélectionné
    const formatRadios = document.querySelectorAll('input[name="format"]');
    const exportBtnText = document.getElementById('exportBtnText');
    
    formatRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const format = this.value.toUpperCase();
            exportBtnText.textContent = 'Exporter en ' + format;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>