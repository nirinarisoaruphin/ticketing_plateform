<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- ============================================ -->
        <!-- EN-TÊTE -->
        <!-- ============================================ -->
        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-blue-50 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        <!--<i class="fas fa-user-edit text-green-600 mr-2"></i>!-->
                        Modifier l'utilisateur
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <i class="fas fa-shield-alt text-green-400 mr-1"></i>
                        Modification de compte - Réservé à l'administrateur
                    </p>
                </div>
                <a href="index.php?page=users" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Retour
                </a>
            </div>
        </div>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="m-6 p-4 rounded-lg flash-message flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- FORMULAIRE -->
        <!-- ============================================ -->
        <form method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Nom d'utilisateur <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                    <!--<p class="text-xs text-gray-400 mt-1">3 caractères minimum, lettres et chiffres uniquement</p>!-->
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Nom complet <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required
                           class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                       class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
            </div>
            
            <!-- ============================================ -->
            <!-- RÔLE - NOUVELLE HIÉRARCHIE -->
            <!-- ============================================ -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Rôle <span class="text-red-500">*</span>
                </label>
                <select name="role" required class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                    <optgroup label="Administration">
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                    </optgroup>
                    
                    <optgroup label="Coordination">
                        <option value="coordinateur" <?= $user['role'] === 'coordinateur' ? 'selected' : '' ?>>Coordinateur / Coordinatrice</option>
                    </optgroup>
                    
                    <optgroup label="Responsables">
                        <option value="responsable_support_technique" <?= $user['role'] === 'responsable_support_technique' ? 'selected' : '' ?>>Responsable Support Technique</option>
                        <option value="responsable_sav" <?= $user['role'] === 'responsable_sav' ? 'selected' : '' ?>>Responsable SAV</option>
                        <option value="responsable_travaux" <?= $user['role'] === 'responsable_travaux' ? 'selected' : '' ?>>Responsable Travaux</option>
                    </optgroup>
                    
                    <optgroup label="Chargés d'Étude">
                        <option value="charge_etude_courant_fort" <?= $user['role'] === 'charge_etude_courant_fort' ? 'selected' : '' ?>>Chargé d'Étude Courant Fort</option>
                        <option value="charge_etude_courant_faible" <?= $user['role'] === 'charge_etude_courant_faible' ? 'selected' : '' ?>>Chargé d'Étude Courant Faible</option>
                        <option value="charge_etude_climatisation" <?= $user['role'] === 'charge_etude_climatisation' ? 'selected' : '' ?>>Chargé d'Étude Climatisation</option>
                    </optgroup>
                    
                    <optgroup label="Commerciaux">
                        <option value="commercial" <?= $user['role'] === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                    </optgroup>
                </select>
            </div>

            <!-- ============================================ -->
            <!-- SÉCURITÉ - CHANGER LE MOT DE PASSE -->
            <!-- ============================================ -->
            <div id="pwd-wrapper" data-user-id="<?= (int)$user['id'] ?>">
                <h2 class="text-base font-bold text-gray-900 mb-1">
                    <!--<i class="fas fa-key text-amber-500 mr-2"></i>Changer le mot de passe!-->
                </h2>
                <!--<p class="text-xs text-gray-500 mb-4">Facultatif. Laissez vide si vous ne voulez rien changer.</p>!-->

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Mot de passe de <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'votre compte') ?></strong>
                        </label>
                        <div class="relative mt-1">
                            <input type="password" id="pwd-current" autocomplete="current-password"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition pr-10"
                                   placeholder="Mot de passe de l'Administrateur">
                            <button type="button" onclick="togglePwd('pwd-current', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                            <div class="relative mt-1">
                                <input type="password" id="pwd-new" autocomplete="new-password"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition pr-10"
                                       placeholder="Nouveau mot de passe">
                                <button type="button" onclick="togglePwd('pwd-new', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                            <div class="relative mt-1">
                                <input type="password" id="pwd-confirm" autocomplete="new-password"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition pr-10"
                                       placeholder="Retapez le mot de passe">
                                <button type="button" onclick="togglePwd('pwd-confirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="pwd-alert" class="hidden text-sm rounded-lg px-4 py-2"></div>

                    <button type="button" id="btn-change-pwd" onclick="changePassword()" 
                        class="btn" style="background-color: #1b8ae6; color: white;">
                        <i class="fas fa-key mr-2"></i>Changer le mot de passe
                    </button>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- BOUTONS -->
            <!-- ============================================ -->
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-2"></i>Mettre à jour
                </button>
                <a href="index.php?page=users" class="btn btn-outline">
                    Annuler
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
.btn-warning {
    background: #F59E0B;
    color: white;
}
.btn-warning:hover {
    background: #D97706;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
}
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}
.alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
.alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
</style>

<script>
(function() {
    const userId = document.getElementById('pwd-wrapper').dataset.userId;

    window.togglePwd = function(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    };

    function showAlert(type, message) {
        const el = document.getElementById('pwd-alert');
        el.className = 'text-sm rounded-lg px-4 py-2 ' + (type === 'error' ? 'alert-error' : 'alert-success');
        el.textContent = message;
        el.classList.remove('hidden');
    }
    function hideAlert() {
        document.getElementById('pwd-alert').classList.add('hidden');
    }

    function setButtonLoading(btn, loading) {
        if (loading) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Veuillez patienter...';
            btn.disabled = true;
        } else {
            btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
            btn.disabled = false;
        }
    }

    window.changePassword = function() {
        const currentPwd = document.getElementById('pwd-current').value;
        const newPwd = document.getElementById('pwd-new').value;
        const confirmPwd = document.getElementById('pwd-confirm').value;
        hideAlert();

        if (!currentPwd) {
            showAlert('error', "Merci de saisir votre mot de passe actuel.");
            return;
        }
        if (!newPwd) {
            showAlert('error', "Merci de saisir un nouveau mot de passe.");
            return;
        }
        if (newPwd !== confirmPwd) {
            showAlert('error', "Les deux mots de passe ne correspondent pas.");
            return;
        }

        const btn = document.getElementById('btn-change-pwd');
        setButtonLoading(btn, true);

        fetch('index.php?page=users&action=change_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ id: userId, current_password: currentPwd, new_password: newPwd, confirm_password: confirmPwd }).toString()
        })
        .then(r => r.json())
        .then(data => {
            setButtonLoading(btn, false);
            if (data.success) {
                showAlert('success', data.message);
                document.getElementById('pwd-current').value = '';
                document.getElementById('pwd-new').value = '';
                document.getElementById('pwd-confirm').value = '';
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            showAlert('error', "Erreur réseau, merci de réessayer.");
        });
    };
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>