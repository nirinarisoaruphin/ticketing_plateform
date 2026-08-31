<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- En-tête -->
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                <!--<i class="fas fa-calendar-plus text-green-600"></i>!-->
                Planifier une intervention
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Remplissez les informations pour planifier une intervention
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
            <!-- 1. TICKET -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Ticket <span class="text-red-500">*</span>
                </label>
                <select name="ticket_id" id="ticket_id" required 
                        class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="">Sélectionnez un ticket...</option>
                    <?php foreach ($tickets as $ticket): ?>
                        <option value="<?= $ticket['id'] ?>" data-category="<?= $ticket['category'] ?>">
                             <?= htmlspecialchars($ticket['ticket_number']) ?> - <?= htmlspecialchars($ticket['title']) ?> 
                            (<?= getStatusLabel($ticket['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <!--<p class="text-xs text-gray-400 mt-1">Le responsable sera automatiquement assigné selon la catégorie du ticket</p>!-->
            </div>
            
            <!-- ============================================ -->
            <!-- 2. RESPONSABLE - AUTO ET LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Responsable <span class="text-red-500">*</span>
                    <!--<span class="text-xs text-gray-400 ml-2">(Automatique)</span>!-->
                </label>
                
                <?php
                // Récupérer les responsables pour le mapping
                $db = Database::getInstance();
                $responsables = $db->fetchAll("
                    SELECT id, full_name, role 
                    FROM users 
                    WHERE role IN ('responsable_sav', 'responsable_travaux', 'responsable_support_technique')
                ");
                
                // Créer un tableau de mapping
                $responsibleMap = [];
                foreach ($responsables as $r) {
                    $responsibleMap[$r['role']] = [
                        'id' => $r['id'],
                        'name' => $r['full_name']
                    ];
                }
                ?>
                
                <select name="technician_id" id="technician_id" required 
                        class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        disabled>
                    <option value="">Sélectionnez un ticket d'abord...</option>
                    <?php foreach ($responsables as $tech): ?>
                        <option value="<?= $tech['id'] ?>" data-role="<?= $tech['role'] ?>">
                            <?= htmlspecialchars($tech['full_name']) ?> (<?= getRoleLabel($tech['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Champ caché pour envoyer la valeur -->
                <input type="hidden" name="technician_id" id="technician_id_hidden" value="">
                
                <div id="responsibleInfo" class="mt-1 text-xs text-blue-600">
                    <!--<i class="fas fa-info-circle mr-1"></i>!-->
                    <!--Le responsable sera automatiquement assigné selon la catégorie du ticket!-->
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- 3. DATE ET HEURE -->
            <!-- ============================================ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="planned_date" required min="<?= date('Y-m-d') ?>"
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Heure <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="planned_time" required step="300"
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <p class="text-xs text-gray-400 mt-1">Toutes les heures sont acceptées</p>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- 4. DURÉE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Durée (minutes)</label>
                <select name="duration" class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="15">15 min</option>
                    <option value="30">30 min</option>
                    <option value="45">45 min</option>
                    <option value="60" selected>1 heure</option>
                    <option value="90">1h30</option>
                    <option value="120">2 heures</option>
                    <option value="180">3 heures</option>
                    <option value="240">4 heures</option>
                </select>
            </div>
            
            <!-- ============================================ -->
            <!-- 5. NOTES -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="3" 
                          class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                          placeholder="Informations complémentaires..."></textarea>
            </div>
            
            <!-- ============================================ -->
            <!-- 6. BOUTONS -->
            <!-- ============================================ -->
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition shadow-md hover:shadow-lg font-medium flex items-center gap-2">
                    <i class="fas fa-save"></i> Planifier
                </button>
                <a href="index.php?page=planning" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition font-medium flex items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
            
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 1. HEURE PAR DÉFAUT
    // ============================================
    const timeInput = document.querySelector('input[name="planned_time"]');
    if (timeInput && !timeInput.value) {
        const now = new Date();
        const minutes = Math.ceil(now.getMinutes() / 5) * 5;
        now.setMinutes(minutes);
        if (now.getMinutes() === 60) {
            now.setHours(now.getHours() + 1);
            now.setMinutes(0);
        }
        const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        timeInput.value = timeStr;
    }
    
    // ============================================
    // 2. MAPPING CATÉGORIE → RESPONSABLE
    // ============================================
    const categoryResponsibleMap = {
        'sav': { 
            id: <?php 
                $dina = $db->fetch("SELECT id FROM users WHERE role = 'responsable_sav' LIMIT 1");
                echo $dina ? $dina['id'] : 0;
            ?>, 
            name: 'Dina',
            role: 'responsable_sav'
        },
        'travaux': { 
            id: <?php 
                $andry = $db->fetch("SELECT id FROM users WHERE role = 'responsable_travaux' LIMIT 1");
                echo $andry ? $andry['id'] : 0;
            ?>, 
            name: 'Andry',
            role: 'responsable_travaux'
        },
        'support_technique': { 
            id: <?php 
                $mahery = $db->fetch("SELECT id FROM users WHERE role = 'responsable_support_technique' LIMIT 1");
                echo $mahery ? $mahery['id'] : 0;
            ?>, 
            name: 'Mahery',
            role: 'responsable_support_technique'
        },
        'bureau_etude': { 
            id: <?php 
                $mahery = $db->fetch("SELECT id FROM users WHERE role = 'responsable_support_technique' LIMIT 1");
                echo $mahery ? $mahery['id'] : 0;
            ?>, 
            name: 'Mahery',
            role: 'responsable_support_technique'
        }
    };
    
    const ticketSelect = document.getElementById('ticket_id');
    const technicianSelect = document.getElementById('technician_id');
    const technicianHidden = document.getElementById('technician_id_hidden');
    const responsibleInfo = document.getElementById('responsibleInfo');
    
    // Fonction pour mettre à jour le responsable
    function updateResponsible() {
        const selectedOption = ticketSelect.options[ticketSelect.selectedIndex];
        const category = selectedOption ? selectedOption.dataset.category : '';
        
        // Réinitialiser
        technicianSelect.innerHTML = '<option value="">Aucun responsable disponible</option>';
        technicianHidden.value = '';
        
        if (category && categoryResponsibleMap[category]) {
            const responsible = categoryResponsibleMap[category];
            
            // Vider et ajouter l'option du responsable
            technicianSelect.innerHTML = '';
            const option = document.createElement('option');
            option.value = responsible.id;
            option.textContent = responsible.name + ' (Responsable ' + category + ')';
            option.selected = true;
            technicianSelect.appendChild(option);
            
            // Mettre à jour le champ caché
            technicianHidden.value = responsible.id;
            
            // Mettre à jour le message d'information
            const roleLabels = {
                'responsable_sav': 'SAV',
                'responsable_travaux': 'Travaux',
                'responsable_support_technique': 'Support Technique / BE'
            };
            const roleLabel = roleLabels[responsible.role] || responsible.role;
            responsibleInfo.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-1"></i> ' + 
                'Responsable automatique : <strong>' + responsible.name + '</strong> (' + roleLabel + ')';
            responsibleInfo.className = 'mt-1 text-xs text-green-600';
            
        } else {
            // Aucune catégorie reconnue
            technicianSelect.innerHTML = '<option value="">Aucun responsable disponible</option>';
            technicianHidden.value = '';
            responsibleInfo.innerHTML = '<i class="fas fa-info-circle mr-1"></i> ' + 
                'Le responsable sera automatiquement assigné selon la catégorie du ticket';
            responsibleInfo.className = 'mt-1 text-xs text-blue-600';
        }
    }
    
    // Écouter le changement de ticket
    ticketSelect.addEventListener('change', updateResponsible);
    
    // Initialiser si un ticket est déjà sélectionné
    if (ticketSelect.value) {
        updateResponsible();
    }
});
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
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>