<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<?php
$role = $_SESSION['user_role'] ?? 'commercial';
$userId = $_SESSION['user_id'] ?? 0;

// ✅ Vérification : Seuls Responsables, Coordinateur et Admin peuvent modifier
$canEdit = in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']);

// ❌ Si l'utilisateur n'est pas autorisé, rediriger
if (!$canEdit) {
    setFlash('danger', 'Vous n\'avez pas la permission de modifier ce ticket.');
    redirect('index.php?page=tickets&action=show&id=' . $ticket['id']);
}

// ❌ Si le ticket est clôturé, bloquer (sauf admin)
if ($ticket['status'] === 'cloture' && $role !== 'admin') {
    setFlash('danger', 'Ce ticket est clôturé et ne peut plus être modifié.');
    redirect('index.php?page=tickets&action=show&id=' . $ticket['id']);
}

// ✅ MAPPING CATÉGORIE → RESPONSABLE
$db = Database::getInstance();
$categoryResponsibleMap = [
    'sav' => [
        'role' => 'responsable_sav',
        'name' => 'Dina',
        'label' => 'Responsable SAV'
    ],
    'travaux' => [
        'role' => 'responsable_travaux',
        'name' => 'Andry',
        'label' => 'Responsable Travaux'
    ],
    'support_technique' => [
        'role' => 'responsable_support_technique',
        'name' => 'Mahery',
        'label' => 'Responsable Support Technique'
    ],
    'bureau_etude' => [
        'role' => 'responsable_support_technique',
        'name' => 'Mahery',
        'label' => 'Responsable Bureau d\'Étude'
    ]
];

// Récupérer l'ID du responsable selon la catégorie du ticket
$responsibleId = null;
$responsibleName = '';
$responsibleRole = '';

if (isset($categoryResponsibleMap[$ticket['category']])) {
    $map = $categoryResponsibleMap[$ticket['category']];
    $responsible = $db->fetch("SELECT id, full_name, role FROM users WHERE role = ? LIMIT 1", [$map['role']]);
    if ($responsible) {
        $responsibleId = $responsible['id'];
        $responsibleName = $responsible['full_name'];
        $responsibleRole = $responsible['role'];
    }
}

$isReadOnly = false;
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- En-tête -->
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-edit text-indigo-600 mr-2"></i>
                        Modifier le ticket <?= htmlspecialchars($ticket['ticket_number']) ?>
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-info-circle text-amber-500 mr-1"></i>
                        Seuls les responsables, coordinateur et l'administrateur peuvent modifier ce ticket.
                    </p>
                </div>
                <a href="index.php?page=tickets&action=show&id=<?= $ticket['id'] ?>"
                        class="group inline-flex items-center gap-3 px-4 py-3
                        rounded-2xl bg-gray-50 border border-gray-200
                        text-gray-700 font-medium
                        transition-all duration-300
                        hover:bg-blue-600 hover:text-white
                        hover:border-blue-600 hover:shadow-xl">

                <span class="flex items-center justify-center w-9 h-9
                        rounded-xl bg-white border border-gray-200
                        text-gray-600 transition-all duration-300
                        group-hover:bg-blue-500 group-hover:border-blue-400
                        group-hover:text-white">

                    <i class="fas fa-chevron-left text-xs"></i>
                </span>

                <span>Retour</span>
                </a>
            </div>
        </div>
        
        <!-- Formulaire -->
        <form method="POST" class="p-6 space-y-4">
            
            <!-- ============================================ -->
            <!-- TITRE - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Titre <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 ml-2"></span>
                </label>
                <input type="text" name="title" value="<?= htmlspecialchars($ticket['title']) ?>" required 
                       readonly
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
            </div>
            
            <!-- ============================================ -->
            <!-- DESCRIPTION - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Description <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 ml-2"></span>
                </label>
                <textarea name="description" rows="5" required 
                          readonly
                          class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"><?= htmlspecialchars($ticket['description']) ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
            </div>
            
            <!-- ============================================ -->
            <!-- CATÉGORIE ET PRIORITÉ - LECTURE SEULE -->
            <!-- ============================================ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Catégorie
                        <span class="text-xs text-gray-400 ml-2"></span>
                    </label>
                    <select name="category" disabled
                            class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                        <?php foreach (['visite', 'etude', 'visite_etude', 'visite_etude_installation', 'panne', 'installation', 'maintenance', 'demande_info', 'autre'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $ticket['category'] === $cat ? 'selected' : '' ?>>
                                <?= getCategoryLabel($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="category" value="<?= $ticket['category'] ?>">
                    <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Priorité
                        <span class="text-xs text-gray-400 ml-2"></span>
                    </label>
                    <select name="priority" disabled
                            class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                        <?php foreach (['basse', 'moyenne', 'haute', 'critique'] as $prio): ?>
                            <option value="<?= $prio ?>" <?= $ticket['priority'] === $prio ? 'selected' : '' ?>>
                                <?= getPriorityLabel($prio) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="priority" value="<?= $ticket['priority'] ?>">
                    <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- TYPE DE DEMANDE ET COMMERCIAL DÉDIÉ - LECTURE SEULE -->
            <!-- ============================================ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Type de demande
                        <span class="text-xs text-gray-400 ml-2"></span>
                    </label>
                    <select name="type_demande" disabled
                            class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                        <option value="etude" <?= $ticket['type_demande'] === 'etude' ? 'selected' : '' ?>>Étude</option>
                        <option value="support" <?= $ticket['type_demande'] === 'support' ? 'selected' : '' ?>>Support</option>
                        <option value="visite" <?= $ticket['type_demande'] === 'visite' ? 'selected' : '' ?>>Visite</option>
                    </select>
                    <input type="hidden" name="type_demande" value="<?= $ticket['type_demande'] ?>">
                    <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Commercial dédié
                        <span class="text-xs text-gray-400 ml-2"></span>
                    </label>
                    <input type="text" name="commercial_dedie" value="<?= htmlspecialchars($ticket['commercial_dedie'] ?? '') ?>"
                           readonly
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- INTITULÉ CLIENT - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Intitulé client
                    <span class="text-xs text-gray-400 ml-2"></span>
                </label>
                <input type="text" name="client_name" value="<?= htmlspecialchars($ticket['client_name'] ?? '') ?>"
                       readonly
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
            </div>
            
            <!-- ============================================ -->
            <!-- ADRESSE CLIENT - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Adresse client
                    <span class="text-xs text-gray-400 ml-2"></span>
                </label>
                <input type="text" name="adresse_client" value="<?= htmlspecialchars($ticket['adresse_client'] ?? '') ?>"
                       readonly
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
            </div>
            
            <!-- ============================================ -->
            <!-- INTERLOCUTEUR - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Interlocuteur
                    <span class="text-xs text-gray-400 ml-2"></span>
                </label>
                <input type="text" name="interlocuteur" value="<?= htmlspecialchars($ticket['interlocuteur'] ?? '') ?>"
                       readonly
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
            </div>
            
            <!-- ============================================ -->
            <!-- CONTACT TECHNIQUE - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Contact technique
                    <span class="text-xs text-gray-400 ml-2"></span>
                </label>
                <input type="text" name="contact_technique" value="<?= htmlspecialchars($ticket['contact_technique'] ?? '') ?>"
                       readonly
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
            </div>
            
            <!-- ============================================ -->
            <!-- MOYEN DE TRANSPORT - LECTURE SEULE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Moyen de transport
                    <span class="text-xs text-gray-400 ml-2"></span>
                </label>
                <select name="moyen_transport" disabled
                        class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                    <option value="">Non défini</option>
                    <option value="voiture_service" <?= ($ticket['moyen_transport'] ?? '') === 'voiture_service' ? 'selected' : '' ?>>Voiture de service</option>
                    <option value="voiture_personnelle" <?= ($ticket['moyen_transport'] ?? '') === 'voiture_personnelle' ? 'selected' : '' ?>>Voiture personnelle</option>
                    <option value="transport_public" <?= ($ticket['moyen_transport'] ?? '') === 'transport_public' ? 'selected' : '' ?>>Transport public</option>
                    <option value="taxi" <?= ($ticket['moyen_transport'] ?? '') === 'taxi' ? 'selected' : '' ?>>Taxi / VTC</option>
                    <option value="avion" <?= ($ticket['moyen_transport'] ?? '') === 'avion' ? 'selected' : '' ?>>Avion</option>
                </select>
                <input type="hidden" name="moyen_transport" value="<?= $ticket['moyen_transport'] ?? '' ?>">
                <p class="text-xs text-gray-400 mt-1">Ce champ est en lecture seule.</p>
            </div>
            
            <!-- Lieu de visite - MODIFIABLE -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Lieu de visite <span class="text-green-500 text-xs ml-2"></span>
                </label>
                <input type="text" name="lieu_visite" value="<?= htmlspecialchars($ticket['lieu_visite'] ?? '') ?>"
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                <p class="text-xs text-green-600 mt-1">Ce champ peut être modifié.</p>
            </div>
            
            <!-- Date et heure de visite - MODIFIABLE -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Date de visite <span class="text-green-500 text-xs ml-2"></span>
                    </label>
                    <input type="date" name="visite_date" value="<?= $ticket['visite_date'] ?? '' ?>"
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <p class="text-xs text-green-600 mt-1">Ce champ peut être modifié.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Heure de visite <span class="text-green-500 text-xs ml-2"></span>
                    </label>
                    <input type="time" name="visite_heure" value="<?= $ticket['visite_heure'] ?? '' ?>"
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <p class="text-xs text-green-600 mt-1">Ce champ peut être modifié.</p>
                </div>
            </div>
            
            <!-- Éléments complémentaires - MODIFIABLE -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Éléments complémentaires <span class="text-green-500 text-xs ml-2"></span>
                </label>
                <textarea name="elements_complement" rows="3" 
                          class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"><?= htmlspecialchars($ticket['elements_complement'] ?? '') ?></textarea>
                <p class="text-xs text-green-600 mt-1">Ce champ peut être modifié.</p>
            </div>
            
            <!-- ============================================ -->
            <!-- STATUT - MODIFIABLE -->
            <!-- ============================================ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Statut <span class="text-green-500 text-xs ml-2"></span>
                    </label>
                    <select name="status" class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <?php foreach (['nouveau', 'assigne', 'en_cours', 'en_attente', 'resolu', 'cloture'] as $status): ?>
                            <option value="<?= $status ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>>
                                <?= getStatusLabel($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-green-600 mt-1">Ce champ peut être modifié.</p>
                </div>
                
                <!-- ============================================ -->
                <!-- ASSIGNER À - AUTO ET LECTURE SEULE -->
                <!-- ============================================ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Assigner à
                        <span class="text-xs text-gray-400 ml-2"></span>
                    </label>
                    
                    <?php if ($responsibleId && $responsibleName): ?>
                        <!-- Affichage du responsable automatique -->
                        <input type="text" 
                               value="<?= htmlspecialchars($responsibleName) ?> (<?= getRoleLabel($responsibleRole) ?>)" 
                               disabled
                               class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                        <input type="hidden" name="assigned_to" value="<?= $responsibleId ?>">
                        <p class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Responsable automatique selon la catégorie : <strong><?= htmlspecialchars($responsibleName) ?></strong>
                        </p>
                    <?php else: ?>
                        <!-- Sélection manuelle si pas de mapping -->
                        <select name="assigned_to" class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <option value="">Non assigné</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?= $tech['id'] ?>" <?= $ticket['assigned_to'] == $tech['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tech['full_name']) ?> (<?= getRoleLabel($tech['role']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Aucun responsable automatique pour cette catégorie.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- BOUTONS -->
            <!-- ============================================ -->
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-2"></i>Mettre à jour
                </button>
                <a href="index.php?page=tickets&action=show&id=<?= $ticket['id'] ?>"
                        class="group inline-flex items-center gap-3 px-4 py-3
                        rounded-2xl bg-gray-50 border border-gray-200
                        text-gray-700 font-medium
                        transition-all duration-300
                        hover:bg-blue-600 hover:text-white
                        hover:border-blue-600 hover:shadow-xl">

                <span class="flex items-center justify-center w-9 h-9
                        rounded-xl bg-white border border-gray-200
                        text-gray-600 transition-all duration-300
                        group-hover:bg-blue-500 group-hover:border-blue-400
                        group-hover:text-white">

                    <i class="fas fa-chevron-left text-xs"></i>
                </span>

                <span>Retour</span>
                </a>
            </div>
            
        </form>
    </div>
</div>

<style>
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: pointer;
    text-decoration: none;
}
.btn-success {
    background: #10B981;
    color: white;
}
.btn-success:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}
.btn-outline {
    background: transparent;
    color: #6B7280;
    border: 1.5px solid #E5E7EB;
}
.btn-outline:hover {
    background: #F9FAFB;
    border-color: #D1D5DB;
}
input:read-only, textarea:read-only, select:disabled {
    background-color: #f3f4f6 !important;
    cursor: not-allowed !important;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>