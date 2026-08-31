<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-ticket-alt text-white text-3xl"></i>
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900">SERVICE - TECHNIQUE</h2>
            <p class="text-sm text-gray-600 mt-1">Connectez-vous à votre compte</p>
        </div>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="mt-4 p-3 rounded-lg flash-message <?= $flash['type'] === 'success' ? 'flash-success' : ($flash['type'] === 'danger' ? 'flash-danger' : 'flash-warning') ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="mt-6 space-y-4" id="loginForm">
            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input type="email" name="email" required 
                           class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                           placeholder="admin@ticketing.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>
            
            <!-- Mot de passe avec toggle -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" name="password" id="password" required 
                           class="pl-10 pr-12 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                           placeholder="••••••••">
                    <button type="button" id="togglePassword" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-indigo-600 transition"
                            title="Afficher/Masquer le mot de passe">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
                <!-- Caps Lock Warning -->
                <div id="capsLockWarning" class="hidden text-xs text-amber-600 mt-1 flex items-center gap-1">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Verrouillage majuscule activé</span>
                </div>
            </div>
            
            <button type="submit" 
                    class="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold rounded-lg transition shadow-md hover:shadow-lg">
                <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
            </button>
        </form>
        
        <div class="mt-6 pt-4 border-t border-gray-200">
            <p class="text-center text-sm text-gray-500">
                <i class="fas fa-info-circle text-indigo-400 mr-1"></i>
                La création de comptes est réservée à l'administrateur
            </p>
            <p class="text-center text-xs text-gray-400 mt-2">
                <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                Connexion sécurisée
            </p>
        </div>
        
        <?php // Message pour les utilisateurs sans compte ?>
        <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200">
            <div class="flex items-start gap-2">
                <i class="fas fa-user-plus text-amber-600 mt-0.5"></i>
                <div>
                    <p class="text-sm text-amber-800 font-medium">Vous n'avez pas de compte ?</p>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Veuillez contacter l'administrateur pour obtenir vos identifiants de connexion.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // TOGGLE MOT DE PASSE
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
    // DÉTECTION VERROUILLAGE MAJUSCULE
    // ============================================
    const capsLockWarning = document.getElementById('capsLockWarning');
    
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
    
    // ============================================
    // SOUMISSION PAR TOUCHE ENTRÉE
    // ============================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && document.activeElement.tagName !== 'TEXTAREA') {
            const form = document.getElementById('loginForm');
            if (form && document.activeElement.closest('form') === form) {
                form.submit();
            }
        }
    });
});
</script>

<style>
.flash-message {
    animation: slideDown 0.4s ease-out;
}
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.flash-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}
.flash-danger {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.flash-warning {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    color: #92400e;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>