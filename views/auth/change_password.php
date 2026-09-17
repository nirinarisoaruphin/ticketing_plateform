<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php
$forceChange = !empty($_SESSION['user_id']);
// Navbar optionnelle
if (file_exists(__DIR__ . '/../../includes/navbar.php')) {
    @require_once __DIR__ . '/../../includes/navbar.php';
}
$step = $viewStep ?? ($_SESSION['pwd_change_step'] ?? 'form');
$maskedEmail = $viewMaskedEmail ?? 'votre adresse email';
?>

<style>
    .pwd-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 20px 60px -15px rgba(15, 118, 110, 0.15), 0 0 0 1px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .pwd-header {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
        padding: 2rem 1.75rem 1.75rem;
        text-align: center;
        color: #fff;
        position: relative;
    }
    .pwd-header::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0; right: 0;
        height: 24px;
        background: #fff;
        border-radius: 24px 24px 0 0;
    }
    .pwd-steps {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .pwd-step-dot {
        width: 10px; height: 10px;
        border-radius: 9999px;
        background: #e5e7eb;
        transition: all 0.25s ease;
    }
    .pwd-step-dot.active {
        width: 28px;
        background: #0d9488;
    }
    .pwd-step-dot.done {
        background: #14b8a6;
    }
    .otp-inputs {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin: 1.5rem 0;
    }
    .otp-inputs input {
        width: 3.25rem;
        height: 3.75rem;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        font-family: ui-monospace, monospace;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #f9fafb;
    }
    .otp-inputs input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2);
        background: #fff;
    }
    .pwd-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #f9fafb;
    }
    .pwd-input:focus {
        outline: none;
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        background: #fff;
    }
    .btn-primary-pwd {
        width: 100%;
        padding: 0.85rem 1.25rem;
        background: linear-gradient(135deg, #0f766e, #0d9488);
        color: #fff;
        font-weight: 600;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .btn-primary-pwd:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px -6px rgba(13, 148, 136, 0.45);
    }
    .btn-ghost-pwd {
        width: 100%;
        padding: 0.7rem;
        background: transparent;
        color: #6b7280;
        font-weight: 500;
        border-radius: 12px;
        border: 1.5px solid #e5e7eb;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-ghost-pwd:hover {
        background: #f3f4f6;
    }
    .strength-bar {
        height: 4px;
        border-radius: 2px;
        background: #e5e7eb;
        margin-top: 0.5rem;
        overflow: hidden;
    }
    .strength-bar > div {
        height: 100%;
        width: 0;
        transition: width 0.3s, background 0.3s;
        border-radius: 2px;
    }
</style>

<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="pwd-card">
            <div class="pwd-header">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/20 backdrop-blur mb-3">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                <h1 class="text-xl font-bold tracking-tight">Sécurité du compte</h1>
                <p class="text-sm text-teal-100 mt-1 opacity-90">Changement de mot de passe sécurisé</p>
            </div>

            <div class="px-6 pb-8 pt-2">
                <div class="pwd-steps">
                    <div class="pwd-step-dot <?= $step === 'form' ? 'active' : 'done' ?>"></div>
                    <div class="pwd-step-dot <?= $step === 'verify' ? 'active' : '' ?>"></div>
                </div>

                <?php $flash = getFlash(); if ($flash): ?>
                <div class="mb-5 p-3.5 rounded-xl text-sm flex items-start gap-2.5
                    <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-100' : 
                       ($flash['type'] === 'info' ? 'bg-sky-50 text-sky-800 border border-sky-100' : 'bg-red-50 text-red-800 border border-red-100') ?>">
                    <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : ($flash['type'] === 'info' ? 'fa-info-circle' : 'fa-exclamation-circle') ?> mt-0.5"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($step === 'form'): ?>
                <form method="POST" id="pwdForm" class="space-y-4" autocomplete="off">
                    <input type="hidden" name="action" value="send_code">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe actuel</label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password" required
                                   class="pwd-input pr-11" placeholder="••••••••">
                            <button type="button" onclick="toggleVisibility('current_password', this)" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nouveau mot de passe</label>
                        <div class="relative">
                            <input type="password" name="new_password" id="new_password" required
                                   class="pwd-input pr-11" placeholder="Choisissez un mot de passe" 
                                   oninput="updateStrength(this.value)">
                            <button type="button" onclick="toggleVisibility('new_password', this)" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="strength-bar"><div id="strengthFill"></div></div>
                        <p class="text-xs text-gray-400 mt-1.5">Aucune limite de longueur — plus c'est long, plus c'est sûr.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmer le nouveau mot de passe</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirm_password" required
                                   class="pwd-input pr-11" placeholder="Retapez le mot de passe">
                            <button type="button" onclick="toggleVisibility('confirm_password', this)" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-pwd mt-2">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer le code de vérification
                    </button>
                </form>

                <p class="text-center text-xs text-gray-400 mt-5 leading-relaxed">
                    Un code à <strong>4 chiffres</strong> sera envoyé sur votre adresse email<br>
                    pour confirmer le changement.
                </p>

                <?php else: ?>
                <div class="text-center mb-2">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-teal-50 text-teal-600 mb-3">
                        <i class="fas fa-envelope-open-text text-xl"></i>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Vérification</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Saisissez le code à 4 chiffres envoyé à<br>
                        <span class="font-medium text-teal-700"><?= htmlspecialchars($maskedEmail) ?></span>
                    </p>
                </div>

                <form method="POST" id="otpForm" autocomplete="off">
                    <input type="hidden" name="action" value="verify_code">
                    <input type="hidden" name="otp_code" id="otp_code_hidden" value="">

                    <div class="otp-inputs" id="otpBoxes">
                        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" data-idx="0" class="otp-digit" autofocus>
                        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" data-idx="1" class="otp-digit">
                        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" data-idx="2" class="otp-digit">
                        <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" data-idx="3" class="otp-digit">
                    </div>

                    <button type="submit" class="btn-primary-pwd">
                        <i class="fas fa-check-circle"></i>
                        Confirmer le changement
                    </button>
                </form>

                <div class="mt-4 space-y-2">
                    <form method="POST">
                        <input type="hidden" name="action" value="resend_code">
                        <button type="submit" class="btn-ghost-pwd text-sm">
                            <i class="fas fa-redo mr-1.5"></i> Renvoyer le code
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn-annuler btn-annuler-sm">
                            Annuler
                        </button>
                    </form>
                </div>

                <p class="text-center text-xs text-gray-400 mt-4">
                    Le code expire dans 10 minutes. Vérifiez aussi vos spams.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <p class="text-center mt-6">
            <a href="index.php?page=profile" class="text-sm text-gray-500 hover:text-teal-600 transition">
                <i class="fas fa-arrow-left mr-1"></i> Retour au profil
            </a>
        </p>
    </div>
</div>

<script>
function toggleVisibility(id, btn) {
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

function updateStrength(val) {
    const fill = document.getElementById('strengthFill');
    if (!fill) return;
    let score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (val.length >= 16) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/\d/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const pct = Math.min(100, score * 18);
    const colors = ['#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e', '#14b8a6'];
    fill.style.width = pct + '%';
    fill.style.background = colors[Math.min(score, colors.length - 1)];
}

(function() {
    const boxes = document.querySelectorAll('.otp-digit');
    if (!boxes.length) return;
    const hidden = document.getElementById('otp_code_hidden');

    function syncHidden() {
        let code = '';
        boxes.forEach(b => code += (b.value || ''));
        if (hidden) hidden.value = code;
    }

    boxes.forEach((box, i) => {
        box.addEventListener('input', (e) => {
            const v = e.target.value.replace(/\D/g, '').slice(-1);
            e.target.value = v;
            if (v && i < boxes.length - 1) boxes[i + 1].focus();
            syncHidden();
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && i > 0) {
                boxes[i - 1].focus();
            }
        });
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
            paste.split('').forEach((ch, idx) => {
                if (boxes[idx]) boxes[idx].value = ch;
            });
            const next = Math.min(paste.length, boxes.length - 1);
            boxes[next].focus();
            syncHidden();
        });
    });

    document.getElementById('otpForm')?.addEventListener('submit', function(e) {
        syncHidden();
        if ((hidden?.value || '').length !== 4) {
            e.preventDefault();
            alert('Veuillez saisir les 4 chiffres du code.');
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
