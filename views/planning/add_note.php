<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-violet-50 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-sticky-note text-purple-600 mr-2"></i>
                        Ajouter une note
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Ticket <?= htmlspecialchars($intervention['ticket_number'] ?? 'N/A') ?>
                    </p>
                </div>
                <a href="index.php?page=planning" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Retour
                </a>
            </div>
        </div>
        
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="m-6 p-4 rounded-lg flash-message flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?> mr-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="p-6 space-y-4">
            <?php if (!empty($intervention['notes'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700">Notes existantes</label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-600 max-h-40 overflow-y-auto whitespace-pre-wrap">
                    <?= nl2br(htmlspecialchars($intervention['notes'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Nouvelle note <span class="text-red-500">*</span>
                </label>
                <textarea name="note" rows="4" required 
                          class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                          placeholder="Saisissez votre note..."></textarea>
                <p class="text-xs text-gray-400 mt-1">La note sera horodatée automatiquement.</p>
            </div>
            
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i> Ajouter la note
                </button>
                <a href="index.php?page=planning" class="btn btn-outline">
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
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>