<?php
// views/errors/404.php - Page d'erreur 404
$pageTitle = 'Page non trouvée';

// ✅ CHEMIN CORRIGÉ - Remonter de 2 niveaux pour atteindre la racine
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center">
        <div class="mx-auto h-24 w-24 bg-red-100 rounded-full flex items-center justify-center shadow-lg">
            <i class="fas fa-exclamation-triangle text-red-600 text-5xl"></i>
        </div>
        <h2 class="mt-4 text-3xl font-extrabold text-gray-900">404</h2>
        <p class="text-xl font-semibold text-gray-700 mt-2">Page non trouvée</p>
        <p class="text-sm text-gray-500 mt-2">
            La page que vous recherchez n'existe pas ou a été déplacée.
        </p>
        <div class="mt-6 flex flex-col gap-3">
            <a href="index.php?page=dashboard" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center justify-center">
                <i class="fas fa-home mr-2"></i>Retour au tableau de bord
            </a>
            <a href="javascript:history.back()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la page précédente
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>