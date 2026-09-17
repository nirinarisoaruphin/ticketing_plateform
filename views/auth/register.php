<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-user-plus text-blue-600 mr-2"></i>Ajouter un utilisateur
            </h1>
            <a href="index.php?page=users" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i>Retour
            </a>
        </div>
        
        <?php if (isset($_SESSION['register_errors'])): ?>
        <div class="mb-4 p-3 rounded bg-red-100 text-red-700 border border-red-200">
            <?php foreach ($_SESSION['register_errors'] as $error): ?>
                <p>• <?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['register_errors']); endif; ?>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="mb-4 p-3 rounded flash-message <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nom d'utilisateur <span class="text-red-500">*</span></label>
                    <input type="text" name="username" required 
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nom complet <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" required 
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required 
                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                <input type="tel" name="phone" 
                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Rôle <span class="text-red-500">*</span></label>
                    <select name="role" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md">
                        <option value="demandeur">Demandeur</option>
                        <option value="technicien">Technicien</option>
                        <option value="responsable">Responsable</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mot de passe temporaire <span class="text-red-500">*</span></label>
                    <input type="text" name="password" required 
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Généré automatiquement ou saisir">
                    <p class="text-xs text-gray-400 mt-1">L'utilisateur devra changer son mot de passe à la première connexion.</p>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" 
                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition">
                    <i class="fas fa-user-plus mr-2"></i>Créer l'utilisateur
                </button>
                <a href="index.php?page=users" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>