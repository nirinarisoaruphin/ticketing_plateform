<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<?php
$role = $_SESSION['user_role'] ?? 'commercial';
$userId = $_SESSION['user_id'] ?? 0;
$isCommercial = isCommercial();
$canManage = canManagePlanning();
?>

<div class="planning-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- ============================================ -->
    <!-- EN-TÊTE -->
    <!-- ============================================ -->
    <div class="flex flex-wrap justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <span>Planning des interventions</span>
                <span class="text-sm font-medium text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
                    <?= count($interventions) ?> intervention(s)
                </span>
                <?php if ($isCommercial): ?>
                <span class="text-xs font-medium text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
                    <i class="fas fa-eye mr-1"></i> Lecture seule
                </span>
                <?php endif; ?>
            </h1>
            <p class="text-gray-500 mt-1 flex items-center gap-2">
                <span id="currentDateTime"><?= date('d/m/Y H:i') ?></span>
            </p>
        </div>
        
        <!-- BOUTONS -->
        <div class="flex gap-2 mt-2 sm:mt-0 flex-wrap">
            <?php if ($canManage): ?>
            <a href="index.php?page=planning&action=create" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-2"></i> Planifier
            </a>
            <?php endif; ?>
            <button onclick="toggleView()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition flex items-center">
                <span id="viewToggleLabel">Vue calendrier</span>
            </button>
        </div>
    </div>
    
    <!-- FLASH MESSAGES -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-message flash-<?= $flash['type'] ?> mb-4 rounded-lg">
        <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- INDICATEUR D'AUTOMATISATION (caché pour commercial) -->
    <!-- ============================================ -->
    <?php if ($canManage): ?>
    <div class="mb-4 px-4 py-3 bg-blue-50 rounded-xl border border-blue-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-blue-600 text-lg"></span>
            <div>
                <p class="text-sm font-medium text-blue-800">Gestion automatique des interventions</p>
                <p class="text-xs text-blue-600">
                    Les interventions démarrent et se terminent automatiquement aux heures prévues
                </p>
            </div>
        </div>
        <span class="text-xs text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
            <i class="fas fa-clock mr-1"></i>
            Vérification toutes les minutes
        </span>
    </div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- FILTRES -->
    <!-- ============================================ -->
    <div class="filter-card mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end" id="filterForm">
            <input type="hidden" name="page" value="planning">
            
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">Dates rapides</label>
                <div class="flex flex-wrap gap-1">
                    <button type="button" class="quick-date-btn active" data-days="0">Aujourd'hui</button>
                    <button type="button" class="quick-date-btn" data-days="1">Demain</button>
                    <button type="button" class="quick-date-btn" data-days="7">+7 jours</button>
                    <button type="button" class="quick-date-btn" data-days="14">+14 jours</button>
                    <button type="button" class="quick-date-btn" data-days="30">+30 jours</button>
                    <button type="button" class="quick-date-btn" data-days="-1">Hier</button>
                </div>
            </div>
            
            <?php if ($canManage): ?>
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider">Responsable</label>
                <select name="technician" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">Tous</option>
                    <?php foreach ($technicians as $tech): ?>
                        <option value="<?= $tech['id'] ?>" <?= ($_GET['technician'] ?? '') == $tech['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tech['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider">Statut</label>
                <select name="status" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">Tous</option>
                    <option value="planifiee" <?= ($_GET['status'] ?? '') === 'planifiee' ? 'selected' : '' ?>>Planifiée</option>
                    <option value="en_cours" <?= ($_GET['status'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="realisee" <?= ($_GET['status'] ?? '') === 'realisee' ? 'selected' : '' ?>>Réalisée</option>
                    <option value="annulee" <?= ($_GET['status'] ?? '') === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider">Du</label>
                <input type="date" name="date_from" id="date_from" value="<?= $_GET['date_from'] ?? '' ?>" 
                       class="mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider">Au</label>
                <input type="date" name="date_to" id="date_to" value="<?= $_GET['date_to'] ?? '' ?>" 
                       class="mt-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm transition font-medium">
                    Filtrer
                </button>
                <a href="index.php?page=planning" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition font-medium">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>
    
    <!-- ============================================ -->
    <!-- VUE LISTE -->
    <!-- ============================================ -->
    <div id="listView">
        <?php if (empty($interventions)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <span class="text-5xl text-gray-300 mb-3 block">📅</span>
                <p class="text-lg font-medium text-gray-500">Aucune intervention planifiée</p>
                <p class="text-sm text-gray-400 mt-1">
                    <?php 
                    if ($isCommercial) {
                        echo 'Aucune intervention n\'est actuellement planifiée.';
                    } elseif ($role === 'responsable_support_technique') {
                        echo 'Aucune intervention Support Technique ou Bureau d\'Étude planifiée.';
                    } elseif ($role === 'responsable_sav') {
                        echo 'Aucune intervention SAV planifiée.';
                    } elseif ($role === 'responsable_travaux') {
                        echo 'Aucune intervention Travaux planifiée.';
                    } else {
                        echo 'Aucune intervention n\'a été planifiée pour le moment.';
                    }
                    ?>
                </p>
                <?php if ($canManage): ?>
                <a href="index.php?page=planning&action=create" class="mt-3 inline-block text-green-600 hover:text-green-800 font-medium">
                    <i class="fas fa-plus mr-1"></i> Planifier une intervention
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($interventions as $intervention): 
                    $statusClass = $intervention['status'] ?? 'planifiee';
                    $statusLabels = [
                        'planifiee' => 'Planifiée',
                        'en_cours' => 'En cours',
                        'realisee' => 'Réalisée',
                        'annulee' => 'Annulée'
                    ];
                    $statusLabel = $statusLabels[$statusClass] ?? $statusClass;
                    
                    $dateTime = new DateTime($intervention['planned_date'] . ' ' . ($intervention['planned_time'] ?? '00:00'));
                    $isPast = $dateTime < new DateTime();
                    $isToday = $dateTime->format('Y-m-d') === date('Y-m-d');
                    
                    $endDateTime = clone $dateTime;
                    $endDateTime->modify('+' . ($intervention['duration'] ?? 60) . ' minutes');
                ?>
                <div class="intervention-card status-<?= $statusClass ?> bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="text-xs font-medium text-gray-400">
                                <?= formatDateOnly($intervention['planned_date']) ?>
                                <?php if ($isToday): ?>
                                <span class="text-indigo-600 font-semibold">(Aujourd'hui)</span>
                                <?php elseif ($isPast && $statusClass !== 'realisee' && $statusClass !== 'annulee'): ?>
                                <span class="text-red-500 font-semibold">(En retard)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="status-badge <?= $statusClass ?>">
                            <?= $statusLabel ?>
                            <?php if ($statusClass === 'en_cours'): ?>
                            <i class="fas fa-robot text-[10px] ml-1" title="Géré automatiquement"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="mb-2">
                        <a href="index.php?page=tickets&action=show&id=<?= $intervention['ticket_id'] ?>" 
                           class="text-indigo-600 hover:text-indigo-800 font-medium text-sm hover:underline">
                            <?= htmlspecialchars($intervention['ticket_number']) ?>
                        </a>
                        <p class="text-sm text-gray-700 truncate"><?= htmlspecialchars($intervention['ticket_title']) ?></p>
                        <?php if (!empty($intervention['category'])): ?>
                        <span class="text-xs text-gray-400">
                            <?= getCategoryLabel($intervention['category']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-wrap gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                            <span>👤</span>
                            <?= htmlspecialchars($intervention['technician_name']) ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <span>🕐</span>
                            <?= substr($intervention['planned_time'] ?? '00:00', 0, 5) ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <span>⏱</span>
                            <?= $intervention['duration'] ?> min
                        </span>
                        <span class="flex items-center gap-1 text-blue-600">
                            <span>🏁</span>
                            <?= $endDateTime->format('H:i') ?>
                        </span>
                        <?php if ($statusClass === 'en_cours'): ?>
                        <span class="auto-badge">
                            <i class="fas fa-robot"></i> Auto
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($intervention['notes'])): ?>
                    <div class="mt-2 text-xs text-gray-500 bg-gray-50 p-2 rounded-lg border border-gray-100">
                        <span>📝</span>
                        <?= htmlspecialchars(substr($intervention['notes'], 0, 60)) ?>
                        <?= strlen($intervention['notes']) > 60 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ACTIONS -->
                    <?php if ($canManage): ?>
                    <div class="mt-3 pt-3 border-t border-gray-100 flex gap-2 flex-wrap">
                        <a href="index.php?page=planning&action=edit&id=<?= $intervention['id'] ?>" 
                           class="text-indigo-600 hover:text-indigo-800 text-xs font-medium transition p-1 rounded hover:bg-indigo-50">
                            Modifier
                        </a>
                        
                        <?php if ($statusClass === 'planifiee'): ?>
                        <a href="index.php?page=planning&action=start&id=<?= $intervention['id'] ?>" 
                           onclick="return confirm('Démarrer cette intervention ?')"
                           class="text-yellow-600 hover:text-yellow-800 text-xs font-medium transition p-1 rounded hover:bg-yellow-50">
                            ▶Démarrer
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($statusClass === 'en_cours'): ?>
                        <span class="text-xs text-green-600 font-medium flex items-center gap-1 p-1">
                            <i class="fas fa-robot"></i> Auto (<?= $endDateTime->format('H:i') ?>)
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($statusClass === 'realisee' || $statusClass === 'annulee'): ?>
                        <a href="index.php?page=planning&action=delete&id=<?= $intervention['id'] ?>" 
                           onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer cette intervention ?\n\nCette action est irréversible.')"
                           class="text-red-600 hover:text-red-800 text-xs font-medium transition p-1 rounded hover:bg-red-50 flex items-center gap-1">
                            Supprimer
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($statusClass === 'planifiee' || $statusClass === 'en_cours'): ?>
                        <a href="index.php?page=planning&action=cancel&id=<?= $intervention['id'] ?>" 
                           onclick="return confirm('Annuler cette intervention ?')"
                           class="text-orange-600 hover:text-orange-800 text-xs font-medium transition p-1 rounded hover:bg-orange-50 ml-auto">
                            Annuler
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="mt-3 pt-3 border-t border-gray-100 text-center">
                        <span class="text-xs text-gray-400 flex items-center justify-center gap-1">
                            <i class="fas fa-eye"></i>
                            Lecture seule
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- STATISTIQUES -->
            <div class="mt-6 stats-grid">
                <div class="stat-item">
                    <span class="stat-number blue"><?= count(array_filter($interventions, function($i) { return $i['status'] === 'planifiee'; })) ?></span>
                    <span class="stat-label">Planifiées</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number yellow"><?= count(array_filter($interventions, function($i) { return $i['status'] === 'en_cours'; })) ?></span>
                    <span class="stat-label">En cours</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number green"><?= count(array_filter($interventions, function($i) { return $i['status'] === 'realisee'; })) ?></span>
                    <span class="stat-label">Réalisées</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number red"><?= count(array_filter($interventions, function($i) { return $i['status'] === 'annulee'; })) ?></span>
                    <span class="stat-label">Annulées</span>
                </div>
            </div>
            
            <!-- Pied -->
            <div class="mt-4 px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 flex justify-between items-center">
                <span class="text-sm text-gray-500">
                    Total : <strong><?= count($interventions) ?></strong> intervention(s)
                </span>
                <?php if ($canManage): ?>
                <span class="text-xs text-gray-400 flex gap-3">
                    <span><i class="fas fa-robot text-blue-500"></i> Automatique</span>
                    <span>✏️ Modifier</span>
                    <span>▶️ Démarrer</span>
                    <span>🗑️ Supprimer</span>
                    <span>❌ Annuler</span>
                </span>
                <?php else: ?>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                    <i class="fas fa-eye"></i> Lecture seule
                </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- ============================================ -->
    <!-- VUE CALENDRIER - CORRIGÉE -->
    <!-- ============================================ -->
    <div id="calendarView" style="display: none;">
        <div class="calendar-wrapper">
            <div class="calendar-header">
                <div class="month-title">
                    <span id="calendarMonth"><?= date('F Y') ?></span>
                    <span class="month-badge" id="eventCountBadge">0 événements</span>
                </div>
                <div class="calendar-nav">
                    <button onclick="changeMonth(-1)">◀</button>
                    <button class="today-btn" onclick="goToToday()">Aujourd'hui</button>
                    <button onclick="changeMonth(1)">▶</button>
                </div>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
            <div class="stats-grid" id="calendarStats"></div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS - VERSION CORRIGÉE -->
<!-- ============================================ -->
<script>
// ============================================
// DONNÉES DES INTERVENTIONS
// ============================================
const interventionsData = <?= json_encode($interventions) ?>;
const isCommercial = <?= $isCommercial ? 'true' : 'false' ?>;
const canManage = <?= $canManage ? 'true' : 'false' ?>;

// ============================================
// ÉTAT DU CALENDRIER - CORRIGÉ
// ============================================
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();

// ============================================
// FONCTIONS DU CALENDRIER - CORRIGÉES
// ============================================

function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    
    // ✅ AFFICHER LE MOIS CORRECT
    document.getElementById('calendarMonth').textContent = monthNames[currentMonth] + ' ' + currentYear;
    
    // ✅ PREMIER JOUR DU MOIS (CORRIGÉ)
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    // ✅ DATE DU JOUR (CORRIGÉE)
    const today = new Date();
    const todayStr = today.getFullYear() + '-' + 
                     String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                     String(today.getDate()).padStart(2, '0');
    
    let html = '';
    
    // Jours de la semaine
    const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    dayNames.forEach(name => {
        html += `<div class="calendar-day header">${name}</div>`;
    });
    
    // ✅ Ajustement du décalage (Lundi = 0, Dimanche = 6)
    const startOffset = firstDay === 0 ? 6 : firstDay - 1;
    for (let i = 0; i < startOffset; i++) {
        html += `<div class="calendar-day empty"></div>`;
    }
    
    let totalEvents = 0;
    for (let day = 1; day <= daysInMonth; day++) {
        // ✅ DATE CORRECTE
        const dateStr = currentYear + '-' + 
                        String(currentMonth + 1).padStart(2, '0') + '-' + 
                        String(day).padStart(2, '0');
        
        // ✅ VÉRIFIER SI C'EST AUJOURD'HUI (CORRIGÉ)
        const isToday = (dateStr === todayStr);
        const dayOfWeek = new Date(currentYear, currentMonth, day).getDay();
        const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
        
        const dayEvents = interventionsData.filter(i => i.planned_date === dateStr);
        const hasEvent = dayEvents.length > 0;
        if (hasEvent) totalEvents++;
        
        let mainStatus = 'planifiee';
        if (hasEvent) {
            const statusCounts = {};
            dayEvents.forEach(e => {
                const s = e.status || 'planifiee';
                statusCounts[s] = (statusCounts[s] || 0) + 1;
            });
            let maxCount = 0;
            for (const [status, count] of Object.entries(statusCounts)) {
                if (count > maxCount) {
                    maxCount = count;
                    mainStatus = status;
                }
            }
        }
        
        let eventDots = '';
        let eventLabels = '';
        let eventCount = '';
        
        if (hasEvent) {
            const statuses = [...new Set(dayEvents.map(e => e.status || 'planifiee'))];
            statuses.forEach(s => {
                eventDots += `<span class="day-event-dot ${s}"></span>`;
            });
            
            const displayEvents = dayEvents.slice(0, 2);
            displayEvents.forEach(e => {
                const statusLabel = e.status || 'planifiee';
                const time = e.planned_time ? e.planned_time.substring(0, 5) : '';
                eventLabels += `<div class="day-event-label ${statusLabel}">${time} ${e.ticket_number || ''}</div>`;
            });
            
            if (dayEvents.length > 2) {
                eventCount = `<span class="day-event-count">+${dayEvents.length - 2}</span>`;
            }
        }
        
        // ✅ AJOUTER LA CLASSE "today" CORRECTEMENT
        html += `
            <div class="calendar-day ${isToday ? 'today' : ''} ${isWeekend ? 'weekend' : ''} ${hasEvent ? 'has-event' : ''} ${hasEvent ? mainStatus : ''}" 
                 onclick="${hasEvent ? `showDayEvents('${dateStr}')` : ''}"
                 title="${hasEvent ? dayEvents.length + ' intervention(s)' : ''}">
                <span class="day-number">${day}</span>
                <div class="day-events">
                    ${eventDots}
                    ${eventLabels}
                    ${eventCount}
                </div>
            </div>
        `;
    }
    
    grid.innerHTML = html;
    document.getElementById('eventCountBadge').textContent = totalEvents + ' événement' + (totalEvents > 1 ? 's' : '');
    updateCalendarStats();
}

function updateCalendarStats() {
    const statsContainer = document.getElementById('calendarStats');
    
    // ✅ MOIS COURANT CORRECT
    const monthStart = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-01';
    const lastDay = new Date(currentYear, currentMonth + 1, 0).getDate();
    const monthEnd = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
    
    const monthInterventions = interventionsData.filter(i => {
        return i.planned_date >= monthStart && i.planned_date <= monthEnd;
    });
    
    const total = monthInterventions.length;
    const planifiees = monthInterventions.filter(i => i.status === 'planifiee').length;
    const enCours = monthInterventions.filter(i => i.status === 'en_cours').length;
    const realisees = monthInterventions.filter(i => i.status === 'realisee').length;
    const annulees = monthInterventions.filter(i => i.status === 'annulee').length;
    
    statsContainer.innerHTML = `
        <div class="stat-item">
            <span class="stat-number">${total}</span>
            <span class="stat-label">Total</span>
        </div>
        <div class="stat-item">
            <span class="stat-number blue">${planifiees}</span>
            <span class="stat-label">Planifiées</span>
        </div>
        <div class="stat-item">
            <span class="stat-number yellow">${enCours}</span>
            <span class="stat-label">En cours</span>
        </div>
        <div class="stat-item">
            <span class="stat-number green">${realisees}</span>
            <span class="stat-label">Réalisées</span>
        </div>
        <div class="stat-item">
            <span class="stat-number red">${annulees}</span>
            <span class="stat-label">Annulées</span>
        </div>
    `;
}

function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    } else if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    renderCalendar();
}

function goToToday() {
    const today = new Date();
    currentMonth = today.getMonth();
    currentYear = today.getFullYear();
    renderCalendar();
}

function showDayEvents(dateStr) {
    const events = interventionsData.filter(i => i.planned_date === dateStr);
    if (events.length === 0) return;
    
    const statusLabels = {
        'planifiee': '📌 Planifiée',
        'en_cours': '🔄 En cours (auto)',
        'realisee': '✅ Réalisée',
        'annulee': '❌ Annulée'
    };
    
    // ✅ FORMATER LA DATE CORRECTEMENT
    const dateParts = dateStr.split('-');
    const dateObj = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
    const formattedDate = dateObj.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric' 
    });
    
    let message = `📅 Interventions du ${formattedDate}:\n\n`;
    events.forEach(e => {
        message += `- ${e.ticket_number} - ${e.ticket_title}\n`;
        message += `  ${statusLabels[e.status] || e.status}\n`;
        message += `  Responsable: ${e.technician_name}\n`;
        message += `  ${e.planned_time || ''} - ${e.duration || 0} min\n`;
        if (e.status === 'en_cours') {
            message += `  🤖 Géré automatiquement\n`;
        }
        message += `\n`;
    });
    alert(message);
}

// ============================================
// BOUTONS DATES RAPIDES
// ============================================
document.querySelectorAll('.quick-date-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.quick-date-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const days = parseInt(this.dataset.days);
        const date = new Date();
        date.setDate(date.getDate() + days);
        
        const dateStr = date.toISOString().split('T')[0];
        document.getElementById('date_from').value = dateStr;
        document.getElementById('date_to').value = dateStr;
    });
});

// ============================================
// BASCULE VUE LISTE / CALENDRIER
// ============================================
let currentView = 'list';

function toggleView() {
    const listView = document.getElementById('listView');
    const calendarView = document.getElementById('calendarView');
    const toggleLabel = document.getElementById('viewToggleLabel');
    
    if (currentView === 'list') {
        listView.style.display = 'none';
        calendarView.style.display = 'block';
        toggleLabel.textContent = 'Vue liste';
        currentView = 'calendar';
        renderCalendar();
    } else {
        listView.style.display = 'block';
        calendarView.style.display = 'none';
        toggleLabel.textContent = 'Vue calendrier';
        currentView = 'list';
    }
}

// ============================================
// HORLOGE
// ============================================
function updateClock() {
    const now = new Date();
    const dateStr = now.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    const timeStr = now.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('currentDateTime').textContent = dateStr + ' ' + timeStr;
}
setInterval(updateClock, 1000);

// ============================================
// POLLING POUR METTRE À JOUR LE PLANNING
// ============================================
function checkPlanningStatus() {
    fetch('index.php?page=planning&action=check_status', {
        credentials: 'include',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.updated) {
            showPlanningToast(data.message || '🔄 Une intervention a changé de statut automatiquement');
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(err => console.log('Erreur polling:', err));
}

function showPlanningToast(message) {
    const existing = document.querySelector('.planning-toast');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = 'planning-toast fixed top-4 right-4 z-50 bg-indigo-600 text-white px-5 py-3.5 rounded-xl shadow-xl flex items-center gap-3 max-w-sm animate-slide-in';
    toast.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="fas fa-robot text-white text-lg"></i>
            <div>
                <p class="font-medium text-sm">🤖 Automatisation</p>
                <p class="text-xs text-indigo-100">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white/70 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.transition = 'opacity 0.5s, transform 0.5s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            setTimeout(() => toast.remove(), 500);
        }
    }, 5000);
}

// ============================================
// INITIALISATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    
    // Vérifier le statut du planning toutes les 20 secondes
    setInterval(checkPlanningStatus, 20000);
    
    // Vérifier immédiatement après 5 secondes
    setTimeout(checkPlanningStatus, 5000);
});
</script>

<!-- ============================================ -->
<!-- STYLES -->
<!-- ============================================ -->
<style>
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

/* ============================================ */
/* STYLES PLANNING */
/* ============================================ */

.planning-container {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.filter-card {
    transition: all 0.3s ease;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 16px 20px;
}

.filter-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.quick-date-btn {
    transition: all 0.2s ease;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    cursor: pointer;
}

.quick-date-btn:hover {
    border-color: #4F46E5;
    color: #4F46E5;
    background: #eef2ff;
    transform: translateY(-1px);
}

.quick-date-btn.active {
    border-color: #4F46E5;
    background: #4F46E5;
    color: white;
}

.intervention-card {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-left: 4px solid transparent;
    cursor: default;
}

.intervention-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.intervention-card.status-planifiee { border-left-color: #3B82F6; }
.intervention-card.status-en_cours { border-left-color: #F59E0B; }
.intervention-card.status-realisee { border-left-color: #10B981; }
.intervention-card.status-annulee { border-left-color: #EF4444; }

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.status-badge.planifiee { background: #dbeafe; color: #1e40af; }
.status-badge.en_cours { background: #fef3c7; color: #92400e; }
.status-badge.realisee { background: #d1fae5; color: #065f46; }
.status-badge.annulee { background: #fee2e2; color: #991b1b; }

.auto-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #dbeafe;
    color: #1e40af;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 9px;
    font-weight: 600;
}

/* ============================================ */
/* CALENDRIER - VERSION CORRIGÉE */
/* ============================================ */

.calendar-wrapper {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.calendar-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.calendar-header .month-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.calendar-header .month-title .month-badge {
    font-size: 11px;
    font-weight: 500;
    color: white;
    background: #4F46E5;
    padding: 2px 12px;
    border-radius: 20px;
}

.calendar-nav {
    display: flex;
    gap: 6px;
    align-items: center;
}

.calendar-nav button {
    padding: 6px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
    font-size: 13px;
    color: #475569;
}

.calendar-nav button:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: scale(1.02);
}

.calendar-nav button.today-btn {
    background: #4F46E5;
    color: white;
    border-color: #4F46E5;
}

.calendar-nav button.today-btn:hover {
    background: #4338CA;
    transform: scale(1.02);
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 3px;
    padding: 12px;
    background: #f8fafc;
}

.calendar-day {
    background: white;
    padding: 8px 4px;
    text-align: center;
    border-radius: 10px;
    font-size: 13px;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: default;
    min-height: 85px;
    border: 1px solid #f1f5f9;
    position: relative;
    overflow: hidden;
}

.calendar-day.header {
    background: transparent;
    font-weight: 600;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 8px 4px;
    min-height: auto;
    border: none;
}

.calendar-day.empty {
    background: transparent;
    border: none;
    min-height: auto;
}

/* ✅ STYLE POUR AUJOURD'HUI - CORRIGÉ */
.calendar-day.today {
    background: #eef2ff !important;
    border-color: #4F46E5 !important;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15) !important;
    transform: scale(1.02);
}

.calendar-day.weekend {
    background: #fafbfc;
}

.calendar-day.has-event {
    cursor: pointer;
}

.calendar-day.has-event:hover {
    transform: scale(1.04) translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    z-index: 5;
    border-color: #4F46E5;
}

.calendar-day .day-number {
    font-weight: 600;
    font-size: 15px;
    color: #0f172a;
    display: block;
    transition: all 0.3s ease;
}

.calendar-day.today .day-number {
    color: #4F46E5 !important;
    font-size: 17px;
}

.day-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-top: 4px;
}

.day-event-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin: 1px;
}

.day-event-dot.planifiee { background: #3B82F6; }
.day-event-dot.en_cours { background: #F59E0B; }
.day-event-dot.realisee { background: #10B981; }
.day-event-dot.annulee { background: #EF4444; }

.day-event-label {
    font-size: 8px;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 1px 6px;
    border-radius: 4px;
    margin-top: 1px;
}

.day-event-label.planifiee { background: #dbeafe; color: #1e40af; }
.day-event-label.en_cours { background: #fef3c7; color: #92400e; }
.day-event-label.realisee { background: #d1fae5; color: #065f46; }
.day-event-label.annulee { background: #fee2e2; color: #991b1b; }

.day-event-count {
    font-size: 9px;
    font-weight: 700;
    color: white;
    background: #4F46E5;
    padding: 1px 8px;
    border-radius: 12px;
    display: inline-block;
    margin-top: 2px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 8px;
    padding: 12px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.stats-grid .stat-item {
    text-align: center;
    padding: 6px 0;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.stats-grid .stat-item:hover {
    background: white;
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.stats-grid .stat-item .stat-number {
    font-size: 18px;
    font-weight: 700;
    display: block;
}

.stats-grid .stat-item .stat-label {
    font-size: 10px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stats-grid .stat-item .stat-number.blue { color: #3B82F6; }
.stats-grid .stat-item .stat-number.yellow { color: #F59E0B; }
.stats-grid .stat-item .stat-number.green { color: #10B981; }
.stats-grid .stat-item .stat-number.red { color: #EF4444; }

/* ============================================ */
/* RESPONSIVE */
/* ============================================ */
@media (max-width: 768px) {
    .calendar-grid { gap: 2px; padding: 8px; }
    .calendar-day { min-height: 60px; padding: 6px 3px; font-size: 11px; }
    .calendar-day .day-number { font-size: 13px; }
    .calendar-day .day-event-label { font-size: 7px; padding: 1px 4px; }
    .calendar-header { flex-direction: column; text-align: center; }
    .calendar-header .month-title { font-size: 16px; }
    .calendar-nav button { padding: 4px 10px; font-size: 12px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .filter-card { padding: 12px; }
    .quick-date-btn { font-size: 10px; padding: 2px 8px; }
}

@media (max-width: 480px) {
    .calendar-day { min-height: 50px; padding: 4px 2px; font-size: 10px; }
    .calendar-day .day-number { font-size: 11px; }
    .calendar-day .day-event-label { display: none; }
    .calendar-day .day-event-dot { width: 6px; height: 6px; }
    .intervention-card { padding: 10px !important; }
}

/* ============================================ */
/* TOAST AUTOMATISATION */
/* ============================================ */
@keyframes slideIn {
    from { opacity: 0; transform: translateX(40px) scale(0.95); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}
.animate-slide-in {
    animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>