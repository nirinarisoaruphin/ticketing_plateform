<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<?php
$role = $_SESSION['user_role'] ?? 'commercial';
$userName = $_SESSION['user_name'] ?? 'Utilisateur';

// ✅ DÉFINIR SI L'UTILISATEUR EST ADMIN, RESPONSABLE OU COMMERCIAL
$isAdmin = ($role === 'admin');
$isResponsable = in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux']);
$isCommercial = ($role === 'commercial');

// ✅ DÉFINIR LE LABEL ET LA VALEUR DU CHAMP SELON LE RÔLE
if ($isAdmin) {
    $dedieLabel = 'Administrateur dédié';
    $dediePlaceholder = 'Nom de l\'administrateur';
    $dedieValue = 'Administrateur - ' . $userName;
} elseif ($isResponsable) {
    $dedieLabel = 'Responsable';
    $dediePlaceholder = 'Nom du responsable';
    $dedieValue = 'Responsable - ' . $userName;
} elseif ($isCommercial) {
    $dedieLabel = 'Commercial dédié';
    $dediePlaceholder = 'Nom du commercial dédié';
    $dedieValue = $userName;
} else {
    $dedieLabel = 'Commercial dédié';
    $dediePlaceholder = 'Nom du commercial dédié';
    $dedieValue = $userName;
}

// ✅ RÉCUPÉRER LES ID DES RESPONSABLES POUR LE MAPPING (gardé pour le backend)
$db = Database::getInstance();

$dina = $db->fetch("SELECT id FROM users WHERE role = 'responsable_sav' LIMIT 1");
$dinaId = $dina ? $dina['id'] : 0;

$andry = $db->fetch("SELECT id FROM users WHERE role = 'responsable_travaux' LIMIT 1");
$andryId = $andry ? $andry['id'] : 0;

$mahery = $db->fetch("SELECT id FROM users WHERE role = 'responsable_support_technique' LIMIT 1");
$maheryId = $mahery ? $mahery['id'] : 0;

// ✅ DÉFINIR LES CATÉGORIES DISPONIBLES SELON LE RÔLE
$categoryResponsibleMap = [
    'responsable_support_technique' => [
        'categories' => ['sav', 'travaux'],
        'label' => 'Responsable Support Technique & BE',
        'message' => 'Vous pouvez créer des tickets SAV et Travaux uniquement.'
    ],
    'responsable_sav' => [
        'categories' => ['support_technique', 'bureau_etude', 'travaux'],
        'label' => 'Responsable SAV',
        'message' => 'Vous pouvez créer des tickets Support Technique, BE et Travaux uniquement.'
    ],
    'responsable_travaux' => [
        'categories' => ['support_technique', 'bureau_etude', 'sav'],
        'label' => 'Responsable Travaux',
        'message' => 'Vous pouvez créer des tickets Support Technique, BE et SAV uniquement.'
    ],
    'admin' => [
        'categories' => ['support_technique', 'bureau_etude', 'sav', 'travaux'],
        'label' => 'Administrateur',
        'message' => 'Toutes les catégories sont disponibles.'
    ],
    'coordinateur' => [
        'categories' => ['support_technique', 'bureau_etude', 'sav', 'travaux'],
        'label' => 'Coordinateur',
        'message' => 'Toutes les catégories sont disponibles.'
    ],
    'commercial' => [
        'categories' => ['support_technique', 'bureau_etude', 'sav', 'travaux'],
        'label' => 'Commercial',
        'message' => 'Toutes les catégories sont disponibles.'
    ],
    'charge_etude_electricite' => [
        'categories' => ['support_technique', 'bureau_etude'],
        'label' => 'Chargé d\'Étude Electricité',
        'message' => 'Support Technique et Bureau d\'Étude uniquement.'
    ],
    'charge_etude_courant_faible' => [
        'categories' => ['support_technique', 'bureau_etude'],
        'label' => 'Chargé d\'Étude Courant Faible',
        'message' => 'Support Technique et Bureau d\'Étude uniquement.'
    ],
    'charge_etude_climatisation' => [
        'categories' => ['support_technique', 'bureau_etude'],
        'label' => 'Chargé d\'Étude Climatisation',
        'message' => 'Support Technique et Bureau d\'Étude uniquement.'
    ]
];

// ✅ VÉRIFIER SI L'UTILISATEUR EST UN RESPONSABLE
$isResponsable = in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux']);

// ✅ RÉCUPÉRER LES CATÉGORIES DISPONIBLES POUR L'UTILISATEUR
$availableCategories = $categoryResponsibleMap[$role]['categories'] ?? ['support_technique', 'bureau_etude', 'sav', 'travaux'];
$roleMessage = $categoryResponsibleMap[$role]['message'] ?? 'Toutes les catégories sont disponibles.';

// ✅ VÉRIFIER QUE L'UTILISATEUR PEUT CRÉER UN TICKET
$canCreate = in_array($role, ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation', 'commercial']);

if (!$canCreate) {
    setFlash('danger', 'Vous n\'avez pas la permission de créer un ticket.');
    redirect('index.php?page=dashboard');
}
?>

<style>
    .wizard-step {
        display: none;
        animation: fadeStep 0.4s ease-out;
    }
    .wizard-step.active {
        display: block;
    }
    @keyframes fadeStep {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .step-indicator {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 16px 0;
        margin-bottom: 20px;
    }
    .step-dot {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .step-dot .circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        transition: all 0.3s ease;
        border: 2px solid #D1D5DB;
        background: white;
        color: #9CA3AF;
    }
    .step-dot .circle.active {
        border-color: #4F46E5;
        background: #4F46E5;
        color: white;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .step-dot .circle.done {
        border-color: #10B981;
        background: #10B981;
        color: white;
    }
    .step-dot .label {
        font-size: 11px;
        font-weight: 500;
        color: #9CA3AF;
        white-space: nowrap;
    }
    .step-dot .label.active {
        color: #4F46E5;
    }
    .step-dot .label.done {
        color: #10B981;
    }
    .step-line {
        width: 40px;
        height: 2px;
        background: #D1D5DB;
        transition: background 0.3s ease;
    }
    .step-line.done {
        background: #10B981;
    }
    .step-line.active {
        background: #4F46E5;
    }
    .wizard-nav {
        display: flex;
        justify-content: space-between;
        padding-top: 20px;
        border-top: 1px solid #E5E7EB;
        margin-top: 24px;
        gap: 10px;
        flex-wrap: wrap;
    }
    .wizard-nav .btn {
        min-width: 120px;
    }
    .required-star {
        color: #EF4444;
    }
    .form-section-title {
        font-size: 14px;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section-title i {
        color: #4F46E5;
    }
    .auto-fill-badge {
        display: inline-block;
        font-size: 10px;
        color: #059669;
        background: #D1FAE5;
        padding: 1px 8px;
        border-radius: 10px;
        margin-left: 6px;
    }
    .field-auto {
        background: #F0FDF4 !important;
        border-color: #6EE7B7 !important;
    }
    .field-auto:focus {
        border-color: #059669 !important;
        ring-color: #059669 !important;
    }
    .field-auto:read-only {
        background: #F0FDF4 !important;
        cursor: not-allowed;
    }
    .field-auto[readonly] {
        background: #F0FDF4 !important;
        cursor: not-allowed;
    }
    .priority-option {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .priority-option:hover {
        transform: translateY(-2px);
    }
    .priority-option.selected {
        border-color: #4F46E5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }
    .priority-option input[type="radio"] {
        display: none;
    }
    #submitBtn {
        display: none;
    }
    .step-3-active #submitBtn {
        display: inline-flex !important;
    }
    .step-3-active #nextBtn {
        display: none !important;
    }
    @keyframes fadeInSubmit {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    @media (max-width: 640px) {
        .step-dot .label { display: none; }
        .step-line { width: 20px; }
        .step-dot .circle { width: 30px; height: 30px; font-size: 11px; }
        .wizard-nav { flex-direction: column-reverse; }
        .wizard-nav .btn { width: 100%; }
        .priority-options { flex-direction: column; }
    }
    .loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        backdrop-filter: blur(6px);
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        animation: fadeInOverlay 0.3s ease-out;
    }
    .loading-overlay.active {
        display: flex;
    }
    @keyframes fadeInOverlay {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .loading-overlay .loader-box {
        background: white;
        border-radius: 20px;
        padding: 40px 48px;
        text-align: center;
        max-width: 380px;
        width: 95%;
        animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 30px 60px rgba(0,0,0,0.2);
    }
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.9) translateY(30px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .loading-overlay .loader-spinner {
        width: 56px;
        height: 56px;
        border: 4px solid #e2e8f0;
        border-top-color: #4F46E5;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        margin: 0 auto 16px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .loading-overlay .loader-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .loading-overlay .loader-subtitle {
        font-size: 13px;
        color: #94a3b8;
        margin-bottom: 4px;
    }
</style>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 page-enter">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Nouvelle demande
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-info-circle text-indigo-400 mr-1"></i>
                        Remplissez toutes les étapes pour créer une nouvelle demande
                    </p>
                </div>
                <span class="text-sm text-gray-400" id="stepCounter">Étape 1 / 3</span>
            </div>
        </div>
        
        <div class="step-indicator" id="stepIndicator">
            <div class="step-dot" data-step="1">
                <span class="circle active" id="circle1">1</span>
                <span class="label active" id="label1">Informations</span>
            </div>
            <div class="step-line" id="line1"></div>
            <div class="step-dot" data-step="2">
                <span class="circle" id="circle2">2</span>
                <span class="label" id="label2">Client</span>
            </div>
            <div class="step-line" id="line2"></div>
            <div class="step-dot" data-step="3">
                <span class="circle" id="circle3">3</span>
                <span class="label" id="label3">Intervention</span>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="p-6" id="ticketForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <!-- ÉTAPE 1 : INFORMATIONS GÉNÉRALES -->
            <div class="wizard-step active" data-step="1">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i> Informations générales
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Catégorie <span class="required-star">*</span>
                            <?php if (in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux'])): ?>
                            <span class="text-xs text-amber-500 ml-2">(Limitée selon votre rôle)</span>
                            <?php endif; ?>
                        </label>
                        <select name="category" id="category" required 
                                class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <option value="">Sélectionnez une catégorie</option>
                            
                            <?php if (in_array('support_technique', $availableCategories) || in_array('bureau_etude', $availableCategories)): ?>
                            <optgroup label="📚 Support Technique & Bureau d'Étude">
                                <?php if (in_array('support_technique', $availableCategories)): ?>
                                <option value="support_technique">Support Technique</option>
                                <?php endif; ?>
                                <?php if (in_array('bureau_etude', $availableCategories)): ?>
                                <option value="bureau_etude">Bureau d'Étude</option>
                                <?php endif; ?>
                            </optgroup>
                            <?php endif; ?>
                            
                            <?php if (in_array('sav', $availableCategories)): ?>
                            <optgroup label="🔧 SAV">
                                <option value="sav">SAV</option>
                            </optgroup>
                            <?php endif; ?>
                            
                            <?php if (in_array('travaux', $availableCategories)): ?>
                            <optgroup label="🏗️ Travaux">
                                <option value="travaux">Travaux</option>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                        
                        <?php if (in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux'])): ?>
                        <p class="text-xs text-amber-600 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?= $roleMessage ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Type de demande <span class="required-star">*</span>
                        </label>
                        <select name="type_demande" id="type_demande" required 
                                class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <option value="">Sélectionnez une catégorie d'abord</option>
                            
                            <optgroup label="🔧 SAV" class="type-group" data-category="sav">
                                <option value="sav">SAV</option>
                            </optgroup>
                            
                            <optgroup label="🏗️ Travaux" class="type-group" data-category="travaux">
                                <option value="travaux">Travaux</option>
                            </optgroup>
                            
                            <optgroup label="📚 Support Technique" class="type-group" data-category="support_technique">
                                <option value="etude">Étude</option>
                                <option value="visite">Visite</option>
                                <option value="visite_etude">Visite + Étude</option>
                                <option value="visite_etude_installation">Visite + Étude + Installation</option>
                            </optgroup>
                            
                            <optgroup label="📐 Bureau d'Étude" class="type-group" data-category="bureau_etude">
                                <option value="etude">Étude</option>
                                <option value="visite">Visite</option>
                                <option value="visite_etude">Visite + Étude</option>
                                <option value="visite_etude_installation">Visite + Étude + Installation</option>
                            </optgroup>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Le type de demande s'adapte automatiquement selon la catégorie choisie
                        </p>
                    </div>
                </div>
                
                <!-- ✅ BLOC ASSIGNÉ À SUPPRIMÉ - L'assignation se fait automatiquement en backend -->
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Identifiant <span class="required-star">*</span>
                            <span class="auto-fill-badge">Auto</span>
                        </label>
                        <input type="text" name="title" id="title" required 
                               class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition field-auto"
                               placeholder="L'identifiant sera généré automatiquement" 
                               value="" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            <?= $dedieLabel ?> 
                            <span class="auto-fill-badge">Auto</span>
                        </label>
                        <input type="text" name="commercial_dedie" id="commercial_dedie"
                               class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition field-auto"
                               placeholder="<?= $dediePlaceholder ?>"
                               value="<?= htmlspecialchars($dedieValue) ?>" readonly>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Rempli automatiquement avec le nom de l'utilisateur connecté
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- ÉTAPE 2 : INFORMATIONS CLIENT -->
            <div class="wizard-step" data-step="2">
                <div class="form-section-title">
                    <i class="fas fa-building"></i> Informations client
                </div>
                
                <?php if ($isResponsable): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <!--<i class="fas fa-info-circle text-blue-600 mt-0.5"></i>!-->
                        <div>
                            <p class="text-sm text-blue-800 font-medium">Création par un responsable</p>
                            <p class="text-xs text-blue-600">Les informations client ne sont pas requises pour les responsables.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="clientFields">
                    <div class="<?= $isResponsable ? 'hidden' : '' ?>" id="clientNameField">
                        <label class="block text-sm font-medium text-gray-700">
                            Intitulé client <span class="required-star">*</span>
                        </label>
                        <input type="text" name="client_name" id="client_name" 
                               class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                               placeholder="Nom du client / société"
                               <?= $isResponsable ? 'disabled' : 'required' ?>>
                    </div>
                    
                    <div class="<?= $isResponsable ? 'hidden' : '' ?>" id="adresseClientField">
                        <label class="block text-sm font-medium text-gray-700">
                            Adresse client <span class="required-star">*</span>
                        </label>
                        <input type="text" name="adresse_client" id="adresse_client" 
                               class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                               placeholder="Adresse complète du client"
                               <?= $isResponsable ? 'disabled' : 'required' ?>>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Interlocuteur / Décideur technique
                            <span class="auto-fill-badge">Auto</span>
                        </label>
                        <input type="text" name="interlocuteur" id="interlocuteur"
                               class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition field-auto"
                               placeholder="Se remplit automatiquement selon la catégorie" readonly>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Rempli automatiquement selon la catégorie choisie
                        </p>
                    </div>
                    
                    <div class="<?= $isResponsable ? 'hidden' : '' ?>" id="contactField">
                        <label class="block text-sm font-medium text-gray-700">
                            Contact (Email ou Téléphone)
                        </label>
                        <input type="text" name="contact_technique" id="contact_technique"
                               class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                               placeholder="Email ou numéro de téléphone">
                    </div>
                </div>
            </div>
            
            <!-- ÉTAPE 3 : INTERVENTION -->
            <div class="wizard-step" data-step="3">
                <div class="form-section-title">
                    <i class="fas fa-calendar-check"></i> Intervention
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Priorité <span class="required-star">*</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 priority-options" id="priorityOptions">
                        <label class="priority-option p-3 rounded-lg text-center cursor-pointer bg-gray-50 hover:bg-gray-100 transition selected" data-value="basse">
                            <input type="radio" name="priority" value="basse" checked>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-sm font-medium text-gray-600">🟢 Basse</span>
                            </div>
                        </label>
                        <label class="priority-option p-3 rounded-lg text-center cursor-pointer bg-gray-50 hover:bg-gray-100 transition" data-value="moyenne">
                            <input type="radio" name="priority" value="moyenne">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-sm font-medium text-blue-600">🔵 Moyenne</span>
                            </div>
                        </label>
                        <label class="priority-option p-3 rounded-lg text-center cursor-pointer bg-gray-50 hover:bg-gray-100 transition" data-value="haute">
                            <input type="radio" name="priority" value="haute">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-sm font-medium text-orange-600">🟠 Haute</span>
                            </div>
                        </label>
                        <label class="priority-option p-3 rounded-lg text-center cursor-pointer bg-gray-50 hover:bg-gray-100 transition" data-value="critique">
                            <input type="radio" name="priority" value="critique">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-sm font-medium text-red-600">🔴 Critique</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div id="lieuVisiteContainer" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700">
                        Lieu de visite <span class="required-star" id="lieuVisiteStar">*</span>
                    </label>
                    <input type="text" name="lieu_visite" id="lieu_visite" 
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                           placeholder="Adresse du lieu de visite">
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Obligatoire pour les interventions Support Technique et Bureau d'Étude
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Date et heure de la visite <span class="required-star">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="date" name="visite_date" id="visite_date" required
                                   class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition field-auto">
                            <input type="time" name="visite_heure" id="visite_heure" required
                                   class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition field-auto">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Rempli automatiquement avec la date et heure actuelles</p>
                    </div>
                    <div>
                        <div id="moyenTransportContainer" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700">
                                Moyen de transport <span class="required-star" id="moyenTransportStar">*</span>
                            </label>
                            <select name="moyen_transport" id="moyen_transport" class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                <option value="">Sélectionnez un moyen de transport</option>
                                <option value="voiture_service">Voiture de service</option>
                                <option value="voiture_personnelle">Voiture personnelle</option>
                                <option value="taxi">Taxi / VTC</option>
                                <option value="moto">Moto</option>
                                <option value="transport_public">Transport public</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Obligatoire pour les interventions Support Technique et Bureau d'Étude
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Brève description <span class="required-star">*</span>
                    </label>
                    <textarea name="description" rows="4" required 
                              class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"
                              placeholder="Résumé concis de la demande du client"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Résumé concis de la demande du client</p>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Pièce jointe</label>
                    <input type="file" name="attachment" 
                           class="mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                    <p class="text-xs text-gray-400 mt-1">Formats acceptés : PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max 5MB)</p>
                </div>
            </div>
            
            <div class="wizard-nav" id="wizardNav">
                <button type="button" id="prevBtn" class="btn btn-outline hidden">
                    <i class="fas fa-arrow-left mr-2"></i>Précédent
                </button>
                <button type="button" id="nextBtn" class="btn btn-primary">
                    Suivant <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button type="submit" id="submitBtn" class="btn btn-success">
                    <i class="fas fa-paper-plane mr-2"></i>Créer la demande
                </button>
            </div>
            
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. SÉLECTION DE LA PRIORITÉ
    // ============================================
    const priorityOptions = document.querySelectorAll('.priority-option');
    priorityOptions.forEach(option => {
        option.addEventListener('click', function() {
            priorityOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
    
    const defaultOption = document.querySelector('.priority-option[data-value="basse"]');
    if (defaultOption) defaultOption.classList.add('selected');
    
    // ============================================
    // 2. REMPLISSAGE AUTO : CATÉGORIE → TYPE + INTERLOCUTEUR + IDENTIFIANT
    // ============================================
    const category = document.getElementById('category');
    const typeDemande = document.getElementById('type_demande');
    const title = document.getElementById('title');
    const interlocuteur = document.getElementById('interlocuteur');
    const lieuVisiteContainer = document.getElementById('lieuVisiteContainer');
    const lieuVisite = document.getElementById('lieu_visite');
    const moyenTransportContainer = document.getElementById('moyenTransportContainer');
    const moyenTransport = document.getElementById('moyen_transport');
    
    const defaultTypeMap = {
        'support_technique': 'etude',
        'bureau_etude': 'etude',
        'sav': 'sav',
        'travaux': 'travaux'
    };
    
    const interlocuteurMap = {
        'support_technique': 'Mahery',
        'bureau_etude': 'Mahery',
        'sav': 'Dina',
        'travaux': 'Andry'
    };
    
    // ✅ MAPPING RESPONSABLE (gardé pour le backend, mais non affiché)
    const responsibleMap = {
        'sav': { id: <?= $dinaId ?>, name: 'Dina' },
        'travaux': { id: <?= $andryId ?>, name: 'Andry' },
        'support_technique': { id: <?= $maheryId ?>, name: 'Mahery' },
        'bureau_etude': { id: <?= $maheryId ?>, name: 'Mahery' }
    };
    
    const typeConfig = {
        'sav': { label: 'SAV', options: ['sav'] },
        'travaux': { label: 'Travaux', options: ['travaux'] },
        'support_technique': { label: 'Support Technique', options: ['etude', 'visite', 'visite_etude', 'visite_etude_installation'] },
        'bureau_etude': { label: 'Bureau d\'Étude', options: ['etude', 'visite', 'visite_etude', 'visite_etude_installation'] }
    };
    
    const typeLabels = {
        'sav': 'SAV',
        'travaux': 'Travaux',
        'etude': 'Étude',
        'visite': 'Visite',
        'visite_etude': 'Visite + Étude',
        'visite_etude_installation': 'Visite + Étude + Installation'
    };
    
    // ✅ Fonction pour générer un titre UNIQUE
    function generateUniqueTitle(categoryValue) {
        const date = new Date();
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        const timestamp = year + month + day + hours + minutes + seconds;
        const random = String(Math.floor(Math.random() * 10000)).padStart(4, '0');
        
        let prefix = 'TK-';
        switch (categoryValue) {
            case 'support_technique': prefix = 'TK-ST'; break;
            case 'bureau_etude': prefix = 'TK-BE'; break;
            case 'sav': prefix = 'TK-SAV'; break;
            case 'travaux': prefix = 'TK-TVX'; break;
            default: prefix = 'TK-';
        }
        
        return prefix + year + month + day + '-' + timestamp + '-' + random;
    }
    
    function toggleSupportFields(categoryValue) {
        const isSupportOrBE = (categoryValue === 'support_technique' || categoryValue === 'bureau_etude');
        
        if (isSupportOrBE) {
            lieuVisiteContainer.style.display = 'block';
            lieuVisite.required = true;
            lieuVisite.disabled = false;
            document.getElementById('lieuVisiteStar').style.display = 'inline';
        } else {
            lieuVisiteContainer.style.display = 'none';
            lieuVisite.required = false;
            lieuVisite.disabled = true;
            lieuVisite.value = '';
            document.getElementById('lieuVisiteStar').style.display = 'none';
        }
        
        if (isSupportOrBE) {
            moyenTransportContainer.style.display = 'block';
            moyenTransport.required = true;
            moyenTransport.disabled = false;
            document.getElementById('moyenTransportStar').style.display = 'inline';
        } else {
            moyenTransportContainer.style.display = 'none';
            moyenTransport.required = false;
            moyenTransport.disabled = true;
            moyenTransport.value = '';
            document.getElementById('moyenTransportStar').style.display = 'none';
        }
    }
    
    function filterTypeDemande(categoryValue) {
        typeDemande.innerHTML = '<option value="">Sélectionnez un type</option>';
        
        if (categoryValue && typeConfig[categoryValue]) {
            const config = typeConfig[categoryValue];
            config.options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.textContent = typeLabels[opt] || opt;
                typeDemande.appendChild(option);
            });
            const defaultVal = defaultTypeMap[categoryValue];
            if (defaultVal && config.options.includes(defaultVal)) {
                typeDemande.value = defaultVal;
                typeDemande.classList.add('field-auto');
            }
        } else {
            const allTypes = [
                { value: 'sav', label: 'SAV', group: 'sav' },
                { value: 'travaux', label: 'Travaux', group: 'travaux' },
                { value: 'etude', label: 'Étude', group: 'support_technique' },
                { value: 'visite', label: 'Visite', group: 'support_technique' },
                { value: 'visite_etude', label: 'Visite + Étude', group: 'support_technique' },
                { value: 'visite_etude_installation', label: 'Visite + Étude + Installation', group: 'support_technique' }
            ];
            const groups = {
                'sav': '🔧 SAV',
                'travaux': '🏗️ Travaux',
                'support_technique': '📚 Support Technique',
                'bureau_etude': '📐 Bureau d\'Étude'
            };
            let currentGroup = '';
            allTypes.forEach(t => {
                const groupLabel = groups[t.group] || t.group;
                if (groupLabel !== currentGroup) {
                    currentGroup = groupLabel;
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = groupLabel;
                    typeDemande.appendChild(optgroup);
                }
                const option = document.createElement('option');
                option.value = t.value;
                option.textContent = t.label;
                typeDemande.lastChild.appendChild(option);
            });
            typeDemande.classList.remove('field-auto');
        }
    }
    
    // Écouter le changement de catégorie
    category.addEventListener('change', function() {
        const value = this.value;
        
        filterTypeDemande(value);
        toggleSupportFields(value);
        
        if (value && interlocuteurMap[value]) {
            interlocuteur.value = interlocuteurMap[value];
            interlocuteur.classList.add('field-auto');
        } else {
            interlocuteur.value = '';
            interlocuteur.classList.remove('field-auto');
        }
        
        if (value) {
            title.value = generateUniqueTitle(value);
            title.classList.add('field-auto');
        } else {
            title.value = '';
            title.classList.remove('field-auto');
        }
    });
    
    // ============================================
    // 3. REMPLISSAGE AUTO : DATE ET HEURE ACTUELLES
    // ============================================
    const visiteDate = document.getElementById('visite_date');
    const visiteHeure = document.getElementById('visite_heure');
    
    function setCurrentDateTime() {
        const now = new Date();
        const date = now.toISOString().split('T')[0];
        const time = now.toTimeString().slice(0, 5);
        
        if (!visiteDate.value) {
            visiteDate.value = date;
            visiteDate.classList.add('field-auto');
        }
        if (!visiteHeure.value) {
            visiteHeure.value = time;
            visiteHeure.classList.add('field-auto');
        }
    }
    
    setCurrentDateTime();
    toggleSupportFields('');
    
    // ============================================
    // 4. WIZARD NAVIGATION
    // ============================================
    const steps = document.querySelectorAll('.wizard-step');
    const dots = document.querySelectorAll('.step-dot');
    const circles = document.querySelectorAll('.step-dot .circle');
    const labels = document.querySelectorAll('.step-dot .label');
    const lines = document.querySelectorAll('.step-line');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const stepCounter = document.getElementById('stepCounter');
    let currentStep = 1;
    const totalSteps = steps.length;
    
    function updateStep(step) {
        steps.forEach(s => s.classList.remove('active'));
        document.querySelector(`.wizard-step[data-step="${step}"]`).classList.add('active');
        
        if (step === totalSteps) {
            document.querySelector('.max-w-3xl').classList.add('step-3-active');
        } else {
            document.querySelector('.max-w-3xl').classList.remove('step-3-active');
        }
        
        circles.forEach((circle, index) => {
            const num = index + 1;
            circle.classList.remove('active', 'done');
            if (num === step) {
                circle.classList.add('active');
                circle.innerHTML = num;
            } else if (num < step) {
                circle.classList.add('done');
                circle.innerHTML = '<i class="fas fa-check"></i>';
            } else {
                circle.textContent = num;
            }
        });
        
        labels.forEach((label, index) => {
            const num = index + 1;
            label.classList.remove('active', 'done');
            if (num === step) {
                label.classList.add('active');
            } else if (num < step) {
                label.classList.add('done');
            }
        });
        
        lines.forEach((line, index) => {
            const num = index + 1;
            line.classList.remove('active', 'done');
            if (num < step) {
                line.classList.add('done');
            } else if (num === step) {
                line.classList.add('active');
            }
        });
        
        stepCounter.textContent = `Étape ${step} / ${totalSteps}`;
        
        if (step === 1) {
            prevBtn.classList.add('hidden');
        } else {
            prevBtn.classList.remove('hidden');
        }
        
        if (step === totalSteps) {
            nextBtn.classList.add('hidden');
            submitBtn.style.display = 'inline-flex';
            submitBtn.style.animation = 'fadeInSubmit 0.5s ease-out';
        } else {
            nextBtn.classList.remove('hidden');
            submitBtn.style.display = 'none';
        }
    }
    
    function nextStep() {
        if (currentStep < totalSteps) {
            const currentStepElement = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
            const inputs = currentStepElement.querySelectorAll('input[required], textarea[required], select[required]');
            let valid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    valid = false;
                } else {
                    input.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                }
            });
            
            if (!valid) {
                alert('Veuillez remplir tous les champs obligatoires (marqués par *) avant de continuer.');
                return;
            }
            
            currentStep++;
            updateStep(currentStep);
        }
    }
    
    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateStep(currentStep);
        }
    }
    
    dots.forEach(dot => {
        dot.addEventListener('click', function() {
            const step = parseInt(this.dataset.step);
            if (step <= currentStep + 1) {
                currentStep = step;
                updateStep(currentStep);
            }
        });
    });
    
    nextBtn.addEventListener('click', nextStep);
    prevBtn.addEventListener('click', prevStep);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const activeStep = document.querySelector('.wizard-step.active');
            if (activeStep) {
                const step = parseInt(activeStep.dataset.step);
                if (step === totalSteps && submitBtn.style.display !== 'none') {
                    document.getElementById('ticketForm').submit();
                } else if (step < totalSteps && !nextBtn.classList.contains('hidden')) {
                    nextStep();
                }
            }
        }
    });
    
    updateStep(1);
});
</script>

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
.btn-primary {
    background: #4F46E5;
    color: white;
}
.btn-primary:hover {
    background: #4338CA;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
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
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>