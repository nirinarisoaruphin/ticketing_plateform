<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- ============================================ -->
        <!-- EN-TÊTE -->
        <!-- ============================================ -->
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-user-plus text-indigo-600 mr-2"></i>
                        Ajouter un utilisateur
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-shield-alt text-indigo-400 mr-1"></i>
                        Création de compte - Réservé à l'administrateur
                    </p>
                </div>
                <a href="index.php?page=users" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Retour
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['register_errors'])): ?>
        <div class="m-6 p-4 rounded-lg bg-red-50 border border-red-200">
            <p class="text-sm font-medium text-red-800 mb-1">❌ Erreurs :</p>
            <ul class="list-disc list-inside text-sm text-red-700">
            <?php foreach ($_SESSION['register_errors'] as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['register_errors']); endif; ?>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="m-6 p-4 rounded-lg flash-message flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- FORMULAIRE -->
        <!-- ============================================ -->
        <form method="POST" class="p-6 space-y-4">
            <!-- Nom d'utilisateur et Nom complet -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nom d'utilisateur <span class="text-red-500">*</span></label>
                    <input type="text" name="username" required 
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                           placeholder="ex: jean.dupont">
                    <p class="text-xs text-gray-400 mt-1">3 caractères minimum</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nom complet <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" required 
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                           placeholder="ex: Jean Dupont">
                </div>
            </div>
            
            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required 
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                       placeholder="ex: jean.dupont@spider.mg">
            </div>
            
            <!-- Téléphone -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input type="tel" name="phone" 
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                       placeholder="ex: 033 00 000 00">
            </div>
            
            <!-- ============================================ -->
            <!-- SÉLECTION DU RÔLE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Rôle <span class="text-red-500">*</span></label>
                <select name="role" required class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <optgroup label="👑 Administration">
                        <option value="admin">Administrateur</option>
                    </optgroup>
                    
                    <optgroup label="🤝 Coordination">
                        <option value="coordinateur">Coordinateur / Coordinatrice</option>
                    </optgroup>
                    
                    <optgroup label="👔 Responsables">
                        <option value="responsable_support_technique">Responsable Support Technique</option>
                        <option value="responsable_sav">Responsable SAV</option>
                        <option value="responsable_travaux">Responsable Travaux</option>
                    </optgroup>
                    
                    <optgroup label="📚 Chargés d'Étude">
                        <option value="charge_etude_electricite">Chargé d'Étude Electricité + Energie secouru</option>
                        <option value="charge_etude_courant_faible">Chargé d'Étude Courant Faible</option>
                        <option value="charge_etude_climatisation">Chargé d'Étude Climatisation</option>
                    </optgroup>
                    
                    <optgroup label="💼 Commerciaux">
                        <option value="commercial">Commercial</option>
                    </optgroup>
                </select>
            </div>
            
            <!-- ============================================ -->
            <!-- ✅ MOT DE PASSE - CÔTE À CÔTE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Mot de passe <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-400 ml-2">(Tous les caractères sont autorisés)</span>
                </label>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-1">
                    <!-- Mot de passe -->
                    <div>
                        <div class="relative">
                            <input type="password" name="password" id="password" required 
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition pr-10"
                                   placeholder="Mot de passe">
                            <button type="button" id="togglePassword" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-600 transition">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Confirmer le mot de passe -->
                    <div>
                        <input type="password" name="confirm_password" id="confirm_password" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                               placeholder="Confirmer le mot de passe">
                    </div>
                </div>
                
                <p class="text-xs text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Tous les caractères sont autorisés : lettres, chiffres, symboles spéciaux, espaces, etc.
                </p>
            </div>
            
            <!-- Message d'information -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Informations</p>
                        <p class="text-xs text-blue-700 mt-0.5">
                            Le mot de passe peut contenir n'importe quel caractère : 
                            lettres (A-Z, a-z), chiffres (0-9), symboles spéciaux (!@#$%^&*), espaces, etc.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Créer l'utilisateur
                </button>
                <a href="index.php?page=users" class="btn btn-outline">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // TOGGLE MOT DE PASSE (Afficher/Masquer)
    // ============================================
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    let isPasswordVisible = false;
    
    toggleBtn.addEventListener('click', function() {
        isPasswordVisible = !isPasswordVisible;
        passwordInput.type = isPasswordVisible ? 'text' : 'password';
        eyeIcon.className = isPasswordVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
        toggleBtn.title = isPasswordVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe';
    });
    
    // ============================================
    // DÉTECTION CAPS LOCK
    // ============================================
    const capsLockWarning = document.createElement('div');
    capsLockWarning.id = 'capsLockWarning';
    capsLockWarning.className = 'hidden text-xs text-amber-600 mt-1 flex items-center gap-1';
    capsLockWarning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Verrouillage majuscule activé';
    
    passwordInput.parentNode.insertBefore(capsLockWarning, passwordInput.nextSibling);
    
    passwordInput.addEventListener('keyup', function(e) {
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            capsLockWarning.classList.remove('hidden');
        } else {
            capsLockWarning.classList.add('hidden');
        }
    });
    
    passwordInput.addEventListener('keydown', function(e) {
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            capsLockWarning.classList.remove('hidden');
        }
    });
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
.btn-outline {
    background: transparent;
    color: #6B7280;
    border: 1.5px solid #E5E7EB;
}
.btn-outline:hover {
    background: #F9FAFB;
    border-color: #D1D5DB;
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
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>