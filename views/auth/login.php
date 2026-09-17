<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="spd-login">

    <!-- ============================================ -->
    <!-- PANNEAU DE MARQUE (gauche sur desktop, bandeau sur mobile) -->
    <!-- ============================================ -->
    <aside class="spd-brand">
        <div class="spd-brand-grid"></div>
        <div class="spd-orb spd-orb-red" aria-hidden="true"></div>
        <div class="spd-orb spd-orb-yellow" aria-hidden="true"></div>
        <div class="spd-noise" aria-hidden="true"></div>

        <div class="spd-brand-top">
            <img src="assets/images/spider-logo-clean.png" alt="SPIDER" class="spd-badge">
            <span class="spd-badge-word">SERVICE TECHNIQUE</span>
        </div>

        <div class="spd-brand-mid">
            <span class="spd-kicker">Plateforme de ticket</span>
            <h1 class="spd-headline">Le service technique qui garde vos installations sous tension.</h1>
            <p class="spd-sub">Suivi des interventions, planning et équipes techniques Spider, centralisés en un seul endroit.</p>

            <div class="spd-signal" aria-hidden="true">
                <span class="spd-signal-track"></span>
                <span class="spd-signal-spark"></span>
            </div>

            <ul class="spd-features">
                <li><i class="fas fa-bolt"></i>Interventions suivies en temps réel</li>
                <li><i class="fas fa-shield-halved"></i>Accès sécurisé par rôle</li>
                <li><i class="fas fa-chart-line"></i>Planning &amp; reporting centralisés</li>
            </ul>
        </div>

        <div class="spd-brand-bottom">
            <p>SPIDER — Leader des solutions énergétiques, électriques et numériques</p>
        </div>
    </aside>

    <!-- ============================================ -->
    <!-- PANNEAU FORMULAIRE -->
    <!-- ============================================ -->
    <main class="spd-form-panel">
        <div class="spd-form-card">

            <div class="spd-form-head">
                <span class="spd-eyebrow-line"></span>
                <h2>Connexion</h2>
                <p>Connectez-vous à votre espace Service Technique</p>
            </div>

            <?php $flash = getFlash(); if ($flash): ?>
            <div class="spd-flash spd-flash-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'danger' ? 'danger' : 'warning') ?>">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="spd-form" id="loginForm">
                <!-- Email -->
                <div class="spd-field">
                    <label for="email">Adresse email</label>
                    <div class="spd-input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" required
                               placeholder="nom@spider.mg"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="spd-field">
                    <label for="password">Mot de passe</label>
                    <div class="spd-input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" required placeholder="••••••••">
                        <button type="button" id="togglePassword" title="Afficher/Masquer le mot de passe">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <div id="capsLockWarning" class="spd-caps-warning hidden">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Verrouillage majuscule activé</span>
                    </div>
                </div>

                <button type="submit" class="spd-submit">
                    Se connecter
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="spd-form-foot">
                <p><i class="fas fa-shield-alt"></i> Connexion sécurisée</p>
                <p class="spd-noaccount">Pas de compte ? Contactez votre administrateur pour obtenir vos identifiants.</p>
            </div>

        </div>
    </main>
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
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap');

:root {
    --spd-ink: #0E1116;
    --spd-ink-soft: #171B22;
    --spd-line: #262B33;
    --spd-red: #16A34A;
    --spd-red-dark: #0D9488;
    --spd-yellow: #34D399;
    --spd-paper: #FAFAF9;
    --spd-muted: #9AA1AC;
}

body { background: var(--spd-paper); }

.spd-login {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr;
}

@media (min-width: 1024px) {
    .spd-login { grid-template-columns: 46% 54%; }
}

/* ============================================ */
/* PANNEAU DE MARQUE */
/* ============================================ */
.spd-brand {
    position: relative;
    background: var(--spd-ink);
    color: white;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 2.5rem 2.5rem 2rem;
    min-height: 280px;
}

@media (min-width: 1024px) {
    .spd-brand { padding: 3rem 4rem 3rem; min-height: 100vh; }
}

.spd-brand-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(var(--spd-line) 1px, transparent 1px),
        linear-gradient(90deg, var(--spd-line) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.35;
    mask-image: radial-gradient(ellipse at 30% 30%, black 0%, transparent 70%);
}

.spd-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: 0.35;
    pointer-events: none;
    animation: spd-orb-float 14s ease-in-out infinite;
}
.spd-orb-red {
    width: 320px; height: 320px;
    background: var(--spd-red);
    top: -80px; right: -100px;
}
.spd-orb-yellow {
    width: 260px; height: 260px;
    background: var(--spd-yellow);
    bottom: -60px; left: -60px;
    animation-delay: -7s;
}
@keyframes spd-orb-float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(20px, -20px) scale(1.08); }
}

.spd-noise {
    position: absolute;
    inset: 0;
    opacity: 0.04;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

.spd-brand-top, .spd-brand-mid, .spd-brand-bottom {
    position: relative;
    z-index: 1;
}

.spd-brand-top {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.spd-badge {
    width: 52px;
    height: 52px;
    object-fit: contain;
    border-radius: 50%;
    background: white;
    box-shadow: 0 0 0 1px rgba(255,255,255,0.08), 0 8px 24px rgba(0,0,0,0.35);
}

.spd-badge-word {
    font-family: 'Space Grotesk', 'Inter', sans-serif;
    font-weight: 700;
    font-size: 1.1rem;
    letter-spacing: 0.02em;
    color: white;
}

.spd-brand-mid { margin: 2.5rem 0; max-width: 440px; }

@media (min-width: 1024px) {
    .spd-brand-mid { margin: 0; }
}

.spd-kicker {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--spd-yellow);
    margin-bottom: 0.9rem;
}

.spd-headline {
    font-family: 'Space Grotesk', 'Inter', sans-serif;
    font-weight: 600;
    font-size: 1.65rem;
    line-height: 1.28;
    letter-spacing: -0.01em;
    margin: 0 0 0.9rem;
}

@media (min-width: 1024px) {
    .spd-headline { font-size: 2.15rem; }
}

.spd-sub {
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--spd-muted);
    margin: 0;
    max-width: 38ch;
}

.spd-signal {
    position: relative;
    height: 2px;
    width: 180px;
    background: var(--spd-line);
    border-radius: 2px;
    margin-top: 2.25rem;
    overflow: hidden;
}

.spd-signal-track {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, var(--spd-yellow) 45%, var(--spd-red) 55%, transparent);
    opacity: 0.9;
}

.spd-signal-spark {
    position: absolute;
    top: 50%;
    left: -10px;
    width: 10px;
    height: 10px;
    margin-top: -5px;
    border-radius: 50%;
    background: var(--spd-yellow);
    box-shadow: 0 0 12px 2px rgba(244, 196, 48, 0.8);
    animation: spd-spark-move 2.8s cubic-bezier(0.65, 0, 0.35, 1) infinite;
}

@keyframes spd-spark-move {
    0%   { left: -10px; opacity: 0; }
    8%   { opacity: 1; }
    92%  { opacity: 1; }
    100% { left: 100%; opacity: 0; }
}

.spd-features {
    list-style: none;
    margin: 2rem 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
}
.spd-features li {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-size: 0.82rem;
    color: #C7CCD4;
}
.spd-features li i {
    width: 26px;
    height: 26px;
    flex: none;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(255,255,255,0.06);
    color: var(--spd-yellow);
    font-size: 0.72rem;
}

.spd-brand-bottom p {
    font-size: 0.78rem;
    color: var(--spd-muted);
    border-top: 1px solid var(--spd-line);
    padding-top: 1.1rem;
    margin: 0;
}

/* ============================================ */
/* PANNEAU FORMULAIRE */
/* ============================================ */
.spd-form-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.5rem 3rem;
    background:
        radial-gradient(circle at 15% 15%, rgba(225, 37, 27, 0.06), transparent 40%),
        radial-gradient(circle at 85% 85%, rgba(244, 196, 48, 0.08), transparent 40%),
        var(--spd-paper);
}

.spd-form-card {
    width: 100%;
    max-width: 400px;
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.6);
    border-radius: 20px;
    padding: 2.25rem 2rem;
    box-shadow: 0 20px 60px -20px rgba(14, 17, 22, 0.18);
    animation: spd-card-in 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes spd-card-in {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

.spd-form-head { margin-bottom: 1.75rem; }

.spd-eyebrow-line {
    display: block;
    width: 34px;
    height: 3px;
    border-radius: 2px;
    background: var(--spd-red);
    margin-bottom: 1.1rem;
}

.spd-form-head h2 {
    font-family: 'Space Grotesk', 'Inter', sans-serif;
    font-weight: 600;
    font-size: 1.65rem;
    color: var(--spd-ink);
    margin: 0 0 0.35rem;
    letter-spacing: -0.01em;
}

.spd-form-head p {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

.spd-flash {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    font-size: 0.85rem;
    padding: 0.75rem 0.9rem;
    border-radius: 10px;
    margin-bottom: 1.25rem;
    animation: spd-flash-in 0.35s ease-out;
}
@keyframes spd-flash-in {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.spd-flash-success { background: #EEF9F0; color: #1E6B33; border: 1px solid #CDEAD3; }
.spd-flash-danger  { background: #FDEEED; color: #96261E; border: 1px solid #F5CFCB; }
.spd-flash-warning { background: #FEF7E8; color: #8A6116; border: 1px solid #F5E1B0; }

.spd-form { display: flex; flex-direction: column; gap: 1.15rem; }

.spd-field label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #3f4652;
    margin-bottom: 0.4rem;
}

.spd-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    border: 1.5px solid #E2E4E9;
    border-radius: 10px;
    background: white;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.spd-input-wrap:focus-within {
    border-color: var(--spd-red);
    box-shadow: 0 0 0 4px rgba(225, 37, 27, 0.1);
}

.spd-input-wrap > i:first-child {
    padding-left: 0.9rem;
    color: #9AA1AC;
    font-size: 0.85rem;
}

.spd-input-wrap input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    padding: 0.72rem 0.75rem;
    font-size: 0.92rem;
    color: var(--spd-ink);
}

.spd-input-wrap button {
    background: transparent;
    border: none;
    color: #9AA1AC;
    padding: 0 0.9rem;
    cursor: pointer;
    transition: color 0.15s ease;
}
.spd-input-wrap button:hover { color: var(--spd-ink); }

.spd-caps-warning {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.76rem;
    color: #B8860B;
    margin-top: 0.4rem;
}
.spd-caps-warning.hidden { display: none; }

.spd-submit {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.85rem 1rem;
    margin-top: 0.35rem;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--spd-red) 0%, var(--spd-red-dark) 100%);
    color: white;
    font-weight: 600;
    font-size: 0.92rem;
    cursor: pointer;
    overflow: hidden;
    box-shadow: 0 8px 20px -6px rgba(225, 37, 27, 0.45);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.spd-submit::before {
    content: '';
    position: absolute;
    top: 0; left: -60%;
    width: 40%; height: 100%;
    background: linear-gradient(120deg, transparent, rgba(255,255,255,0.35), transparent);
    transform: skewX(-20deg);
    transition: left 0.6s ease;
}
.spd-submit:hover::before { left: 130%; }
.spd-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 26px -6px rgba(225, 37, 27, 0.55); }
.spd-submit i { font-size: 0.8rem; transition: transform 0.15s ease; }
.spd-submit:hover i { transform: translateX(3px); }

.spd-form-foot {
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid #ECEDF0;
}
.spd-form-foot p {
    font-size: 0.78rem;
    color: #9AA1AC;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.spd-form-foot p i { color: var(--spd-red); font-size: 0.75rem; }
.spd-noaccount { color: #6b7280 !important; line-height: 1.5; }

@media (prefers-reduced-motion: reduce) {
    .spd-signal-spark { animation: none; left: 40%; opacity: 1; }
    .spd-orb { animation: none; }
    .spd-form-card { animation: none; }
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
