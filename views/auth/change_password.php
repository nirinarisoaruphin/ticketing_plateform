<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-lg shadow p-8">
        <div class="text-center mb-6">
            <i class="fas fa-key text-blue-600 text-5xl mb-3"></i>
            <h2 class="text-2xl font-bold text-gray-900">Changer votre mot de passe</h2>
            <p class="text-sm text-gray-500 mt-2">Pour des raisons de sécurité, veuillez changer votre mot de passe.</p>
        </div>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="mb-4 p-3 rounded flash-message <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Mot de passe actuel <span class="text-red-500">*</span></label>
                <input type="password" name="current_password" required 
                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nouveau mot de passe <span class="text-red-500">*</span></label>
                <input type="password" name="new_password" required 
                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-400 mt-1">Minimum 6 caractères.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirmer le mot de passe <span class="text-red-500">*</span></label>
                <input type="password" name="confirm_password" required 
                       class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                <i class="fas fa-save mr-2"></i>Changer le mot de passe
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>