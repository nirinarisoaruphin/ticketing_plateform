<?php
// includes/permissions.php - GESTION DES PERMISSIONS
require_once __DIR__ . '/functions.php';

/**
 * Vérifier si l'utilisateur peut effectuer une action
 */
function hasPermission($action, $resource = null, $resourceId = null) {
    if (!isLoggedIn()) return false;
    
    $role = $_SESSION['user_role'] ?? 'commercial';
    $userId = $_SESSION['user_id'] ?? 0;
    
    // ✅ ADMIN : Accès total
    if ($role === 'admin') return true;
    
    // ============================================
    // PERMISSIONS PAR RÔLE
    // ============================================
    
    switch ($role) {
        // ===== COMMERCIAL =====
        case 'commercial':
            if ($resource === 'ticket') {
                if (in_array($action, ['create', 'view', 'list', 'comment'])) {
                    return true;
                }
                if (in_array($action, ['edit', 'delete', 'assign', 'validate', 'process'])) {
                    return false;
                }
            }
            if ($resource === 'planning') {
                return false;
            }
            return false;
            
        // ===== CHARGÉS D'ÉTUDE =====
        case 'charge_etude_climatisation':
        case 'charge_etude_courant_faible':
        case 'charge_etude_electricite':
            if ($resource === 'ticket') {
                return true; // CRUD complet
            }
            if ($resource === 'planning') {
                return true; // Peut voir le planning
            }
            return false;
            
        // ===== RESPONSABLES =====
        case 'responsable_support_technique':
        case 'responsable_maintenance_sav':
        case 'responsable_travaux':
            if ($resource === 'ticket') {
                return true; // CRUD complet
            }
            if ($resource === 'planning') {
                return true; // Peut planifier
            }
            return false;
            
        // ===== TECHNICIENS =====
        case 'technicien_sav_1':
        case 'technicien_sav_2':
        case 'technicien_travaux_1':
        case 'technicien_travaux_2':
            if ($resource === 'ticket') {
                if (in_array($action, ['view', 'list', 'comment', 'process'])) {
                    return true;
                }
                if (in_array($action, ['edit', 'delete', 'assign', 'validate'])) {
                    return false;
                }
            }
            if ($resource === 'planning') {
                return true;
            }
            return false;
            
        default:
            return false;
    }
}

// ============================================
// ⚠️ SUPPRIMER TOUTES LES FONCTIONS DOUBLONNES
// Elles sont déjà dans functions.php
// ============================================

/*
// ❌ SUPPRIMER ces fonctions car elles sont déjà dans functions.php
function canValidateTicket() { ... }
function canProcessTicket() { ... }
function canReturnToCommercial() { ... }
function canManageUsers() { ... }
function canExportData() { ... }
function canViewPlanning() { ... }
function canCreateTicket() { ... }
function canViewHistorique() { ... }
*/

// ============================================
// ✅ GARDER UNIQUEMENT hasPermission() ici
// Toutes les autres fonctions sont dans functions.php
// ============================================

?>