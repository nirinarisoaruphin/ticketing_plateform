<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 page-enter">
    
    <!-- ============================================ -->
    <!-- EN-TÊTE - SANS ICÔNE -->
    <!-- ============================================ -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            Mon profil
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Consultez vos informations personnelles et vos statistiques
        </p>
    </div>
    
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-message flash-<?= $flash['type'] ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- CARTE DE PROFIL - DESIGN 2026 -->
    <!-- ============================================ -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- ============================================ -->
        <!-- EN-TÊTE AVEC AVATAR (SANS ICÔNE) -->
        <!-- ============================================ -->
        <div class="relative bg-gradient-to-r from-indigo-500 via-indigo-600 to-violet-600 px-6 py-8">
            <div class="flex items-center gap-4">
                <!-- Avatar uniquement, pas d'icône -->
                <div class="avatar avatar-<?= $user['role'] ?> avatar-lg border-4 border-white shadow-lg">
                    <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                </div>
                <div class="text-white">
                    <h2 class="text-xl font-bold"><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p class="text-indigo-100 text-sm">
                        <?= ucfirst($user['role']) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- INFORMATIONS DU PROFIL -->
        <!-- ============================================ -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Colonne gauche -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Nom d'utilisateur</label>
                        <p class="text-gray-800 font-medium mt-1">
                            <?= htmlspecialchars($user['username']) ?>
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Nom complet</label>
                        <p class="text-gray-800 font-medium mt-1">
                            <?= htmlspecialchars($user['full_name']) ?>
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Email</label>
                        <p class="text-gray-800 font-medium mt-1">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>
                    </div>
                </div>
                
                <!-- Colonne droite -->
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</label>
                        <p class="text-gray-800 font-medium mt-1">
                            <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span class="text-gray-400">Non renseigné</span>' ?>
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</label>
                        <p class="text-gray-800 font-medium mt-1">
                            <span class="badge <?= 
                                $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                ($user['role'] === 'coordinateur' ? 'bg-purple-100 text-purple-800' : 
                                ($user['role'] === 'responsable_support_technique' ? 'bg-indigo-100 text-indigo-800' :
                                ($user['role'] === 'responsable_sav' ? 'bg-pink-100 text-pink-800' :
                                ($user['role'] === 'responsable_travaux' ? 'bg-amber-100 text-amber-800' :
                                ($user['role'] === 'commercial' ? 'bg-blue-100 text-blue-800' :
                                ($user['role'] === 'charge_etude_electricite' ? 'bg-orange-100 text-orange-800' :
                                ($user['role'] === 'charge_etude_courant_faible' ? 'bg-cyan-100 text-cyan-800' :
                                ($user['role'] === 'charge_etude_climatisation' ? 'bg-emerald-100 text-emerald-800' : 
                                'bg-gray-100 text-gray-800')))))))) 
                            ?>">
                                <?= getRoleLabel($user['role']) ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</label>
                        <p class="text-gray-800 font-medium mt-1">
                            <?= formatDate($user['created_at'], 'd/m/Y à H:i') ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- DERNIÈRE ACTIVITÉ -->
            <!-- ============================================ -->
            <?php if ($lastActivity): ?>
            <div class="mt-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                <p class="text-sm text-indigo-800">
                    Dernière activité : 
                    <strong>
                        <?= $lastActivity['type'] === 'comment' ? 'Commentaire' : 'Création de ticket' ?>
                    </strong>
                    le <?= formatDate($lastActivity['created_at']) ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- STATISTIQUES -->
            <!-- ============================================ -->
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white rounded-lg p-4 border border-gray-200 text-center hover:shadow-md transition">
                    <p class="text-2xl font-bold text-indigo-600"><?= $ticketsCreated['count'] ?? 0 ?></p>
                    <p class="text-xs text-gray-500">Tickets créés</p>
                </div>
                <?php if (in_array($user['role'], ['technicien', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'admin', 'coordinateur'])): ?>
                <div class="bg-white rounded-lg p-4 border border-gray-200 text-center hover:shadow-md transition">
                    <p class="text-2xl font-bold text-blue-600"><?= $ticketsAssigned['count'] ?? 0 ?></p>
                    <p class="text-xs text-gray-500">Tickets assignés</p>
                </div>
                <div class="bg-white rounded-lg p-4 border border-gray-200 text-center hover:shadow-md transition">
                    <p class="text-2xl font-bold text-green-600"><?= $ticketsResolved['count'] ?? 0 ?></p>
                    <p class="text-xs text-gray-500">Tickets résolus</p>
                </div>
                <?php endif; ?>
                <div class="bg-white rounded-lg p-4 border border-gray-200 text-center hover:shadow-md transition">
                    <p class="text-2xl font-bold text-gray-600"><?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                    <p class="text-xs text-gray-500">Membre depuis</p>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- DERNIERS TICKETS -->
            <!-- ============================================ -->
            <?php if (!empty($recentTickets)): ?>
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    Derniers tickets
                </h3>
                <div class="space-y-2">
                    <?php foreach ($recentTickets as $ticket): ?>
                    <a href="index.php?page=tickets&action=show&id=<?= $ticket['id'] ?>" 
                       class="block bg-gray-50 hover:bg-gray-100 rounded-lg p-3 border border-gray-200 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-800">#<?= htmlspecialchars($ticket['ticket_number']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($ticket['title']) ?></p>
                            </div>
                            <span class="badge badge-status-<?= $ticket['status'] ?>">
                                <?= getStatusLabel($ticket['status']) ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- MESSAGE INFORMATION -->
            <!-- ============================================ -->
            <!--<div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                    <div>
                        <p class="text-sm text-yellow-800 font-medium">Informations en lecture seule</p>
                        <p class="text-xs text-yellow-700 mt-0.5">
                            Pour modifier vos informations personnelles, veuillez contacter l'administrateur.
                        </p>
                    </div>
                </div>
            </div>!-->
            
            <!-- ============================================ -->
            <!-- BOUTONS D'ACTION -->
            <!-- ============================================ -->
            <div class="mt-6 flex flex-wrap gap-3 pt-6 border-t border-gray-200">
                <a href="index.php?page=dashboard" class="btn btn-primary">
                    <i class="fas fa-arrow-left mr-2"></i>Retour au tableau de bord
                </a>
                <a href="index.php?page=tickets" class="btn btn-outline">
                    <i class="fas fa-list mr-2"></i>Voir mes tickets
                </a>
                <?php if (isAdmin()): ?>
                <a href="index.php?page=users" class="btn btn-outline">
                    <i class="fas fa-users-cog mr-2"></i>Gérer les utilisateurs
                </a>
                <?php endif; ?>
                <!--<a href="index.php?page=change_password" class="btn btn-outline">
                    <i class="fas fa-key mr-2"></i>Changer mot de passe
                </a>!-->
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================ */
/* STYLES SPÉCIFIQUES - PROFIL 2026 */
/* ============================================ */

.page-enter {
    animation: pageEnter 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes pageEnter {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* ===== AVATAR MODERNE ===== */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.85rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    user-select: none;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.7rem;
}

.avatar-lg {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
}

.avatar:hover {
    transform: scale(1.05);
}

/* Avatars par rôle */
.avatar-admin { background: linear-gradient(135deg, #EF4444, #DC2626); }
.avatar-coordinateur { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
.avatar-responsable_support_technique { background: linear-gradient(135deg, #4F46E5, #4338CA); }
.avatar-responsable_sav { background: linear-gradient(135deg, #EC4899, #DB2777); }
.avatar-responsable_travaux { background: linear-gradient(135deg, #F59E0B, #D97706); }
.avatar-commercial { background: linear-gradient(135deg, #3B82F6, #2563EB); }
.avatar-charge_etude_electricite { background: linear-gradient(135deg, #F97316, #EA580C); }
.avatar-charge_etude_courant_faible { background: linear-gradient(135deg, #06B6D4, #0891B2); }
.avatar-charge_etude_climatisation { background: linear-gradient(135deg, #10B981, #059669); }

/* ===== BADGES ===== */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1.5;
    letter-spacing: 0.01em;
    transition: all 0.3s ease;
}

/* Badges Statut */
.badge-status-nouveau { background: #DBEAFE; color: #1E40AF; }
.badge-status-assigne { background: #EDE9FE; color: #5B21B6; }
.badge-status-en_cours { background: #FEF3C7; color: #92400E; }
.badge-status-en_attente { background: #FED7AA; color: #9A3412; }
.badge-status-resolu { background: #D1FAE5; color: #065F46; }
.badge-status-cloture { background: #F3F4F6; color: #374151; }

/* ===== BOUTONS ===== */
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
    background: linear-gradient(135deg, #4F46E5, #7C3AED);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}
.btn-primary:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
}

.btn-outline {
    background: transparent;
    color: #475569;
    border: 1.5px solid #E2E8F0;
}
.btn-outline:hover {
    background: #F1F5F9;
    border-color: #CBD5E1;
    transform: translateY(-2px);
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