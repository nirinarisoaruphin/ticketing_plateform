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
            <!-- BOUTONS -->
            <!-- ============================================ -->
            <div class="flex flex-wrap gap-3 pt-4 border-t">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-2"></i>Mettre à jour
                </button>
                <button type="button" class="btn btn-warning" onclick="openPwdModal()">
                    <i class="fas fa-key mr-2"></i>Changer mot de passe
                </button>
                <a href="index.php?page=users" class="btn btn-annuler">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL : CHANGER LE MOT DE PASSE (avec code de confirmation par email) -->
<!-- ============================================ -->
<div id="pwdModalOverlay" class="pwd-modal-overlay" style="display:none;">
    <div class="pwd-modal-card">
        <button type="button" class="pwd-modal-close" onclick="closePwdModal()">&times;</button>

        <div class="pwd-modal-header">
            <div class="pwd-modal-icon"><i class="fas fa-shield-alt"></i></div>
            <h2 class="pwd-modal-title">Changer le mot de passe</h2>
            <p class="pwd-modal-subtitle">Compte : <strong><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></strong></p>
        </div>

        <div class="pwd-modal-steps">
            <div id="pwdDot1" class="pwd-step-dot active"></div>
            <div id="pwdDot2" class="pwd-step-dot"></div>
        </div>

        <div id="pwdModalAlert" class="pwd-modal-alert" style="display:none;"></div>

        <!-- ÉTAPE 1 : formulaire -->
        <form id="pwdStepForm" class="space-y-4" autocomplete="off">
            <div>
                <label class="pwd-label">Votre mot de passe actuel (administrateur)</label>
                <div class="pwd-input-wrap">
                    <input type="password" id="pwd_current_password" class="pwd-input" placeholder="••••••••" required>
                    <button type="button" class="pwd-toggle-eye" onclick="togglePwdVisibility('pwd_current_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="pwd-label">Nouveau mot de passe pour cet utilisateur</label>
                <div class="pwd-input-wrap">
                    <input type="password" id="pwd_new_password" class="pwd-input" placeholder="Choisissez un mot de passe" required>
                    <button type="button" class="pwd-toggle-eye" onclick="togglePwdVisibility('pwd_new_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <p class="pwd-hint">Aucune limite de longueur ou de complexité.</p>
            </div>
            <div>
                <label class="pwd-label">Confirmer le nouveau mot de passe</label>
                <div class="pwd-input-wrap">
                    <input type="password" id="pwd_confirm_password" class="pwd-input" placeholder="Retapez le mot de passe" required>
                    <button type="button" class="pwd-toggle-eye" onclick="togglePwdVisibility('pwd_confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary-pwd">
                <i class="fas fa-paper-plane"></i> Envoyer le code de vérification
            </button>
            <p class="pwd-footnote">
                Un code à <strong>4 chiffres</strong> sera envoyé sur <strong>votre</strong> adresse email
                (administrateur) pour confirmer cette opération.
            </p>
        </form>

        <!-- ÉTAPE 2 : vérification du code -->
        <form id="pwdStepVerify" class="space-y-4" style="display:none;" autocomplete="off">
            <div class="text-center mb-2">
                <div class="pwd-verify-icon"><i class="fas fa-envelope-open-text"></i></div>
                <h3 class="pwd-verify-title">Vérification</h3>
                <p class="pwd-verify-text">
                    Saisissez le code à 4 chiffres envoyé à<br>
                    <span id="pwdMaskedEmail" class="pwd-verify-email"></span>
                </p>
            </div>

            <div class="otp-inputs" id="pwdOtpBoxes">
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-idx="0">
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-idx="1">
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-idx="2">
                <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" class="otp-digit" data-idx="3">
            </div>

            <button type="submit" class="btn-primary-pwd">
                <i class="fas fa-check-circle"></i> Confirmer le changement
            </button>

            <div class="pwd-modal-links">
                <button type="button" class="btn-ghost-pwd" onclick="resendPwdCode()">
                    <i class="fas fa-redo mr-1"></i> Renvoyer le code
                </button>
                <button type="button" class="btn-annuler btn-annuler-sm" onclick="closePwdModal()">
                    Annuler
                </button>
            </div>

            <p class="pwd-footnote">Le code expire dans 10 minutes. Vérifiez aussi vos spams.</p>
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

/* ============================================ */
/* MODAL CHANGER MOT DE PASSE (même style que le profil) */
/* ============================================ */
.pwd-modal-overlay {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(15, 23, 42, 0.55);
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.pwd-modal-card {
    position: relative;
    background: #fff;
    border-radius: 20px;
    width: 100%; max-width: 420px;
    max-height: 90vh; overflow-y: auto;
    box-shadow: 0 25px 70px -15px rgba(0,0,0,0.35);
    padding: 2rem 1.75rem 1.75rem;
    animation: slideUp 0.25s ease-out;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
.pwd-modal-close {
    position: absolute; top: 0.9rem; right: 1rem;
    background: none; border: none; font-size: 1.5rem; line-height: 1;
    color: #9ca3af; cursor: pointer;
}
.pwd-modal-close:hover { color: #374151; }
.pwd-modal-header { text-align: center; margin-bottom: 1rem; }
.pwd-modal-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 3.25rem; height: 3.25rem; border-radius: 1rem;
    background: linear-gradient(135deg, #059669 0%, #34d399 100%);
    color: #fff; font-size: 1.25rem; margin-bottom: 0.75rem;
}
.pwd-modal-title { font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0; }
.pwd-modal-subtitle { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
.pwd-modal-steps { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.25rem; }
.pwd-step-dot { width: 10px; height: 10px; border-radius: 9999px; background: #e5e7eb; transition: all 0.25s ease; }
.pwd-step-dot.active { width: 28px; background: #10b981; }
.pwd-step-dot.done { background: #34d399; }
.pwd-modal-alert {
    margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.75rem;
    font-size: 0.85rem; display: flex; align-items: flex-start; gap: 0.5rem;
}
.pwd-modal-alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.pwd-modal-alert.danger { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
.pwd-label { display: block; font-size: 0.85rem; font-weight: 500; color: #374151; margin-bottom: 0.35rem; }
.pwd-input-wrap { position: relative; }
.pwd-input {
    width: 100%; padding: 0.7rem 2.75rem 0.7rem 1rem; border: 1.5px solid #e5e7eb; border-radius: 12px;
    font-size: 0.9rem; background: #f9fafb; transition: border-color 0.2s, box-shadow 0.2s;
}
.pwd-input:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); background: #fff; }
.pwd-toggle-eye {
    position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #9ca3af; cursor: pointer; padding: 0.25rem;
    font-size: 0.9rem; line-height: 1;
}
.pwd-toggle-eye:hover { color: #6b7280; }
.pwd-hint { font-size: 0.72rem; color: #9ca3af; margin-top: 0.35rem; }
.pwd-footnote { text-align: center; font-size: 0.72rem; color: #9ca3af; margin-top: 1rem; line-height: 1.4; }
.btn-primary-pwd {
    width: 100%; padding: 0.8rem 1.1rem;
    background: linear-gradient(135deg, #059669, #10b981);
    color: #fff; font-weight: 600; border-radius: 12px; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    transition: transform 0.15s, box-shadow 0.15s;
}
.btn-primary-pwd:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -6px rgba(16,185,129,0.45); }
.btn-primary-pwd:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
.btn-ghost-pwd {
    flex: 1; padding: 0.6rem; background: transparent; color: #6b7280; font-weight: 500;
    border-radius: 12px; border: 1.5px solid #e5e7eb; cursor: pointer; font-size: 0.82rem;
}
.btn-ghost-pwd:hover { background: #f3f4f6; }
.pwd-modal-links { display: flex; gap: 0.6rem; }
.pwd-verify-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 3rem; height: 3rem; border-radius: 9999px; background: #ecfdf5; color: #10b981;
    font-size: 1.15rem; margin-bottom: 0.6rem;
}
.pwd-verify-title { font-size: 1rem; font-weight: 600; color: #111827; margin: 0; }
.pwd-verify-text { font-size: 0.82rem; color: #6b7280; margin-top: 0.25rem; }
.pwd-verify-email { font-weight: 600; color: #059669; }
.otp-inputs { display: flex; gap: 0.6rem; justify-content: center; margin: 1.1rem 0; }
.otp-inputs input {
    width: 3rem; height: 3.4rem; text-align: center; font-size: 1.35rem; font-weight: 700;
    font-family: ui-monospace, monospace; border: 2px solid #e5e7eb; border-radius: 12px;
    outline: none; background: #f9fafb; transition: border-color 0.2s, box-shadow 0.2s;
}
.otp-inputs input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.2); background: #fff; }
</style>

<script>
function togglePwdVisibility(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

(function() {
    const TARGET_USER_ID = <?= (int)$user['id'] ?>;
    const ENDPOINT = 'index.php?page=users&action=change_password';

    const overlay   = document.getElementById('pwdModalOverlay');
    const alertBox  = document.getElementById('pwdModalAlert');
    const stepForm  = document.getElementById('pwdStepForm');
    const stepVerify= document.getElementById('pwdStepVerify');
    const dot1      = document.getElementById('pwdDot1');
    const dot2      = document.getElementById('pwdDot2');
    const maskedEl  = document.getElementById('pwdMaskedEmail');
    const otpBoxes  = document.querySelectorAll('#pwdOtpBoxes .otp-digit');

    window.openPwdModal = function() {
        resetPwdModal();
        overlay.style.display = 'flex';
    };

    window.closePwdModal = function() {
        overlay.style.display = 'none';
        resetPwdModal();
    };

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closePwdModal();
    });

    function resetPwdModal() {
        stepForm.reset();
        stepForm.style.display = 'block';
        stepVerify.style.display = 'none';
        dot1.classList.add('active'); dot1.classList.remove('done');
        dot2.classList.remove('active');
        hideAlert();
        otpBoxes.forEach(b => b.value = '');
    }

    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.className = 'pwd-modal-alert ' + (type === 'success' ? 'success' : 'danger');
        alertBox.style.display = 'flex';
    }
    function hideAlert() {
        alertBox.style.display = 'none';
    }

    function setLoading(form, loading) {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        btn.disabled = loading;
    }

    async function postStep(payload) {
        const body = new URLSearchParams({ id: TARGET_USER_ID, ...payload });
        const res = await fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body.toString()
        });
        return res.json();
    }

    // ÉTAPE 1 : envoyer le code
    stepForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideAlert();

        const current = document.getElementById('pwd_current_password').value;
        const pwd1 = document.getElementById('pwd_new_password').value;
        const pwd2 = document.getElementById('pwd_confirm_password').value;

        if (pwd1 !== pwd2) {
            showAlert('Les deux mots de passe ne correspondent pas.', 'danger');
            return;
        }

        setLoading(stepForm, true);
        try {
            const data = await postStep({
                step: 'send_code',
                current_password: current,
                new_password: pwd1,
                confirm_password: pwd2
            });

            if (data.success) {
                maskedEl.textContent = data.masked_email || 'votre adresse email';
                stepForm.style.display = 'none';
                stepVerify.style.display = 'block';
                dot1.classList.remove('active'); dot1.classList.add('done');
                dot2.classList.add('active');
                showAlert(data.message || 'Code envoyé.', 'success');
                otpBoxes[0]?.focus();
            } else {
                showAlert(data.message || "Une erreur est survenue.", 'danger');
            }
        } catch (err) {
            showAlert("Erreur réseau. Veuillez réessayer.", 'danger');
        } finally {
            setLoading(stepForm, false);
        }
    });

    // ÉTAPE 2 : vérifier le code
    stepVerify.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideAlert();

        let code = '';
        otpBoxes.forEach(b => code += (b.value || ''));

        if (code.length !== 4) {
            showAlert('Veuillez saisir les 4 chiffres du code.', 'danger');
            return;
        }

        setLoading(stepVerify, true);
        try {
            const data = await postStep({ step: 'verify_code', otp_code: code });

            if (data.success) {
                showAlert(data.message || 'Mot de passe changé avec succès.', 'success');
                setTimeout(() => { closePwdModal(); }, 1400);
            } else {
                showAlert(data.message || 'Code incorrect.', 'danger');
                if (data.reset) {
                    setTimeout(() => { resetPwdModal(); }, 1200);
                }
            }
        } catch (err) {
            showAlert("Erreur réseau. Veuillez réessayer.", 'danger');
        } finally {
            setLoading(stepVerify, false);
        }
    });

    window.resendPwdCode = async function() {
        hideAlert();
        try {
            const data = await postStep({ step: 'resend_code' });
            if (data.success) {
                maskedEl.textContent = data.masked_email || 'votre adresse email';
                showAlert(data.message || 'Nouveau code envoyé.', 'success');
            } else {
                showAlert(data.message || "Impossible de renvoyer le code.", 'danger');
            }
        } catch (err) {
            showAlert("Erreur réseau. Veuillez réessayer.", 'danger');
        }
    };

    // Navigation automatique entre les cases OTP
    otpBoxes.forEach((box, i) => {
        box.addEventListener('input', (e) => {
            const v = e.target.value.replace(/\D/g, '').slice(-1);
            e.target.value = v;
            if (v && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && i > 0) {
                otpBoxes[i - 1].focus();
            }
        });
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
            paste.split('').forEach((ch, idx) => { if (otpBoxes[idx]) otpBoxes[idx].value = ch; });
            const next = Math.min(paste.length, otpBoxes.length - 1);
            otpBoxes[next].focus();
        });
    });
})();
</script>

