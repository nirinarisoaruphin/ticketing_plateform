<?php
// controllers/ExportController.php - VERSION CSV ET PDF UNIQUEMENT
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../includes/functions.php';

class ExportController {
    private $ticketModel;
    
    public function __construct() {
        $this->ticketModel = new Ticket();
    }
    
    /**
     * Page d'exportation
     */
    public function index() {
        global $pageTitle;
        $pageTitle = 'Exporter les tickets';
        
        if (!canExportData()) {
            setFlash('danger', 'Accès non autorisé. Vous n\'avez pas la permission d\'exporter les données.');
            redirect('index.php?page=dashboard');
        }
        
        require_once __DIR__ . '/../views/export/index.php';
    }
    
    /**
     * Exporter les tickets selon le format choisi
     */
    public function export() {
        if (!canExportData()) {
            setFlash('danger', 'Accès non autorisé. Vous n\'avez pas la permission d\'exporter les données.');
            redirect('index.php?page=dashboard');
        }
        
        $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
        
        // Récupérer les filtres
        $filters = [];
        if (isset($_GET['status']) && $_GET['status']) $filters['status'] = $_GET['status'];
        if (isset($_GET['priority']) && $_GET['priority']) $filters['priority'] = $_GET['priority'];
        if (isset($_GET['category']) && $_GET['category']) $filters['category'] = $_GET['category'];
        if (isset($_GET['assigned_to']) && $_GET['assigned_to']) $filters['assigned_to'] = $_GET['assigned_to'];
        if (isset($_GET['date_from']) && $_GET['date_from']) $filters['date_from'] = $_GET['date_from'];
        if (isset($_GET['date_to']) && $_GET['date_to']) $filters['date_to'] = $_GET['date_to'];
        
        // Récupérer les tickets
        $tickets = $this->getTicketsWithFilters($filters);
        
        if (empty($tickets)) {
            setFlash('warning', 'Aucun ticket à exporter avec ces filtres.');
            redirect('index.php?page=export');
        }
        
        // Exporter selon le format (CSV ou PDF uniquement)
        switch ($format) {
            case 'csv':
                $this->exportCSV($tickets);
                break;
            case 'pdf':
                $this->exportPDF($tickets);
                break;
            default:
                setFlash('danger', 'Format d\'exportation non reconnu. Formats disponibles : CSV, PDF.');
                redirect('index.php?page=export');
        }
    }
    
    /**
     * Récupérer les tickets avec filtres
     */
    private function getTicketsWithFilters($filters) {
        $db = Database::getInstance();
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        $sql = "SELECT t.*, 
                       u1.full_name as created_by_name,
                       u1.email as created_by_email,
                       u2.full_name as assigned_to_name,
                       u2.email as assigned_to_email,
                       u2.phone as assigned_to_phone,
                       (SELECT GROUP_CONCAT(u3.full_name SEPARATOR ', ') 
                        FROM ticket_assignments ta 
                        INNER JOIN users u3 ON ta.user_id = u3.id 
                        WHERE ta.ticket_id = t.id AND ta.is_active = 1) as assigned_users_names
                FROM tickets t
                LEFT JOIN users u1 ON t.created_by = u1.id
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE 1=1";
        
        $params = [];
        
        // Règles de visibilité
        if ($role === 'commercial') {
            $sql .= " AND t.created_by = ?";
            $params[] = $userId;
        } elseif ($role === 'responsable_support_technique') {
            $sql .= " AND t.category IN ('support_technique', 'bureau_etude')";
        } elseif ($role === 'responsable_sav') {
            $sql .= " AND t.category = 'sav'";
        } elseif ($role === 'responsable_travaux') {
            $sql .= " AND t.category = 'travaux'";
        } elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            $sql .= " AND t.assigned_to = ? AND t.category IN ('support_technique', 'bureau_etude')";
            $params[] = $userId;
        }
        
        // Filtres supplémentaires
        if (isset($filters['status']) && $filters['status']) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        if (isset($filters['priority']) && $filters['priority']) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }
        if (isset($filters['category']) && $filters['category']) {
            $sql .= " AND t.category = ?";
            $params[] = $filters['category'];
        }
        if (isset($filters['assigned_to']) && $filters['assigned_to']) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        if (isset($filters['date_from']) && $filters['date_from']) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * EXPORT CSV
     */
    private function exportCSV($tickets) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tickets_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        fputcsv($output, [
            'Numero',
            'Titre',
            'Categorie',
            'Type demande',
            'Priorite',
            'Statut',
            'Statut Validation',
            'Cree par (Nom)',
            'Cree par (Email)',
            'Assigne a (Nom)',
            'Assigne a (Email)',
            'Assigne a (Telephone)',
            'Charges d\'etude assignes',
            'Commercial dedie',
            'Client (Nom)',
            'Client (Adresse)',
            'Interlocuteur',
            'Contact technique',
            'Lieu de visite',
            'Date de visite',
            'Heure de visite',
            'Moyen de transport',
            'Cree le',
            'Resolu le',
            'Description',
            'Elements complementaires',
            'Piece jointe'
        ]);
        
        foreach ($tickets as $ticket) {
            fputcsv($output, [
                $ticket['ticket_number'],
                $ticket['title'],
                getCategoryLabel($ticket['category']),
                getTypeDemandeLabel($ticket['type_demande'] ?? 'etude'),
                getPriorityLabel($ticket['priority']),
                getStatusLabel($ticket['status']),
                ucfirst($ticket['validation_status'] ?? 'en_attente'),
                $ticket['created_by_name'] ?? '-',
                $ticket['created_by_email'] ?? '-',
                $ticket['assigned_to_name'] ?? '-',
                $ticket['assigned_to_email'] ?? '-',
                $ticket['assigned_to_phone'] ?? '-',
                $ticket['assigned_users_names'] ?? '-',
                $ticket['commercial_dedie'] ?? '-',
                $ticket['client_name'] ?? '-',
                $ticket['adresse_client'] ?? '-',
                $ticket['interlocuteur'] ?? '-',
                $ticket['contact_technique'] ?? '-',
                $ticket['lieu_visite'] ?? '-',
                $ticket['visite_date'] ?? '-',
                $ticket['visite_heure'] ?? '-',
                $ticket['moyen_transport'] ?? '-',
                formatDate($ticket['created_at']),
                $ticket['resolved_at'] ? formatDate($ticket['resolved_at']) : '-',
                strip_tags($ticket['description'] ?? ''),
                $ticket['elements_complement'] ?? '-',
                $ticket['attachment'] ?? '-'
            ]);
        }
        fclose($output);
        exit;
    }
    
    /**
     * EXPORT PDF
     */
    private function exportPDF($tickets) {
        $html = $this->generatePDFHTML($tickets);
        
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename=tickets_' . date('Y-m-d') . '.html');
        echo $html;
        exit;
    }
    
    /**
     * Générer le HTML pour le PDF - Tableau unique
     */
    private function generatePDFHTML($tickets) {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Export des tickets</title>
            <style>
                @page {
                    size: A4 landscape;
                    margin: 10mm;
                }
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                    background: #ffffff;
                    color: #1e293b;
                    font-size: 9px;
                    padding: 5px;
                }
                
                .header {
                    text-align: center;
                    padding: 8px 0 12px 0;
                    border-bottom: 2px solid #1a237e;
                    margin-bottom: 12px;
                }
                
                .header h1 {
                    color: #1a237e;
                    font-size: 18px;
                    font-weight: 700;
                    margin: 0;
                }
                
                .header .subtitle {
                    color: #64748b;
                    font-size: 10px;
                    margin-top: 2px;
                }
                
                .header .count {
                    display: inline-block;
                    background: #1a237e;
                    color: white;
                    padding: 1px 12px;
                    border-radius: 20px;
                    font-size: 10px;
                    margin-top: 3px;
                }
                
                .header .meta {
                    font-size: 9px;
                    color: #94a3b8;
                    margin-top: 3px;
                }
                
                .summary {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px 16px;
                    background: #f8fafc;
                    padding: 6px 14px;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                    margin-bottom: 12px;
                }
                
                .summary .item {
                    display: flex;
                    align-items: center;
                    gap: 3px;
                }
                
                .summary .item .number {
                    font-weight: 700;
                    color: #0f172a;
                    font-size: 12px;
                }
                
                .summary .item .label {
                    color: #94a3b8;
                    font-size: 9px;
                }
                
                .table-wrapper {
                    overflow: hidden;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 8px;
                }
                
                thead {
                    background: #1a237e;
                    color: #ffffff;
                }
                
                thead th {
                    padding: 5px 4px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 7.5px;
                    text-transform: uppercase;
                    letter-spacing: 0.2px;
                    border: 1px solid #2a3a8e;
                    white-space: nowrap;
                }
                
                tbody td {
                    padding: 4px 4px;
                    border: 1px solid #e2e8f0;
                    vertical-align: middle;
                }
                
                tbody tr:nth-child(even) {
                    background: #fafbfc;
                }
                
                .badge {
                    display: inline-block;
                    padding: 1px 8px;
                    border-radius: 20px;
                    font-size: 7px;
                    font-weight: 600;
                    white-space: nowrap;
                }
                
                .badge-nouveau { background: #dbeafe; color: #1e40af; }
                .badge-assigne { background: #ede9fe; color: #5b21b6; }
                .badge-en_cours { background: #fef3c7; color: #92400e; }
                .badge-en_attente { background: #fed7aa; color: #9a3412; }
                .badge-resolu { background: #d1fae5; color: #065f46; }
                .badge-cloture { background: #f1f5f9; color: #475569; }
                
                .badge-basse { background: #f1f5f9; color: #475569; }
                .badge-moyenne { background: #dbeafe; color: #1e40af; }
                .badge-haute { background: #fee2e2; color: #991b1b; }
                .badge-critique { background: #fecaca; color: #7f1d1d; }
                
                .badge-support_technique { background: #e0e7ff; color: #3730a3; }
                .badge-bureau_etude { background: #e0e7ff; color: #3730a3; }
                .badge-sav { background: #fce7f3; color: #9d174d; }
                .badge-travaux { background: #fef3c7; color: #92400e; }
                
                .ticket-num {
                    font-weight: 700;
                    color: #1a237e;
                    font-size: 9px;
                }
                
                .footer {
                    text-align: center;
                    padding: 10px 0 5px 0;
                    border-top: 1px solid #e2e8f0;
                    margin-top: 12px;
                    color: #94a3b8;
                    font-size: 8px;
                }
                
                @media print {
                    body { padding: 0; }
                    .table-wrapper { overflow: visible; }
                    tbody tr { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            
            <div class="header">
                <h1>Export des tickets</h1>
                <div class="subtitle">Exporte le ' . date('d/m/Y a H:i') . '</div>
                <div class="count">' . count($tickets) . ' ticket(s)</div>
                <div class="meta">Exporte par : ' . htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') . '</div>
            </div>
            
            <div class="summary">';
        
        $total = count($tickets);
        $statusCounts = [];
        $priorityCounts = [];
        
        foreach ($tickets as $t) {
            $status = $t['status'] ?? 'nouveau';
            $priority = $t['priority'] ?? 'moyenne';
            
            if (!isset($statusCounts[$status])) $statusCounts[$status] = 0;
            if (!isset($priorityCounts[$priority])) $priorityCounts[$priority] = 0;
            
            $statusCounts[$status]++;
            $priorityCounts[$priority]++;
        }
        
        $html .= '
            <div class="item"><span class="number">' . $total . '</span> <span class="label">Total</span></div>
            <div class="item"><span class="number">' . ($statusCounts['nouveau'] ?? 0) . '</span> <span class="label">Nouveaux</span></div>
            <div class="item"><span class="number">' . (($statusCounts['en_cours'] ?? 0) + ($statusCounts['en_attente'] ?? 0)) . '</span> <span class="label">En cours</span></div>
            <div class="item"><span class="number">' . ($statusCounts['resolu'] ?? 0) . '</span> <span class="label">Resolus</span></div>
            <div class="item"><span class="number">' . ($priorityCounts['critique'] ?? 0) . '</span> <span class="label">Critiques</span></div>
        ';
        
        $html .= '
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;width:25px;">#</th>
                            <th style="width:70px;">Numero</th>
                            <th style="width:100px;">Titre</th>
                            <th style="width:60px;">Categorie</th>
                            <th style="width:55px;">Type</th>
                            <th style="width:50px;">Priorite</th>
                            <th style="width:50px;">Statut</th>
                            <th style="width:70px;">Cree par</th>
                            <th style="width:90px;">Email createur</th>
                            <th style="width:70px;">Assigne a</th>
                            <th style="width:90px;">Email assigne</th>
                            <th style="width:60px;">Tel. assigne</th>
                            <th style="width:80px;">Charges d\'etude</th>
                            <th style="width:70px;">Commercial</th>
                            <th style="width:70px;">Client</th>
                            <th style="width:100px;">Adresse client</th>
                            <th style="width:70px;">Interlocuteur</th>
                            <th style="width:70px;">Contact tech.</th>
                            <th style="width:70px;">Lieu visite</th>
                            <th style="width:65px;">Date visite</th>
                            <th style="width:50px;">Heure visite</th>
                            <th style="width:65px;">Moyen transport</th>
                            <th style="width:65px;">Cree le</th>
                            <th style="width:65px;">Resolu le</th>
                            <th style="width:120px;">Description</th>
                            <th style="width:70px;">Elements compl.</th>
                            <th style="width:60px;">Piece jointe</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $i = 1;
        foreach ($tickets as $ticket) {
            $statusClass = 'badge-' . ($ticket['status'] ?? 'nouveau');
            $priorityClass = 'badge-' . ($ticket['priority'] ?? 'moyenne');
            $categoryClass = 'badge-' . ($ticket['category'] ?? 'general');
            
            $html .= '
                        <tr>
                            <td style="text-align:center;font-weight:600;color:#94a3b8;">' . $i . '</td>
                            <td><span class="ticket-num">#' . htmlspecialchars($ticket['ticket_number']) . '</span></td>
                            <td style="font-weight:500;">' . htmlspecialchars($ticket['title']) . '</td>
                            <td><span class="badge ' . $categoryClass . '">' . getCategoryLabel($ticket['category']) . '</span></td>
                            <td>' . getTypeDemandeLabel($ticket['type_demande'] ?? 'etude') . '</td>
                            <td><span class="badge ' . $priorityClass . '">' . getPriorityLabel($ticket['priority']) . '</span></td>
                            <td><span class="badge ' . $statusClass . '">' . getStatusLabel($ticket['status']) . '</span></td>
                            <td>' . htmlspecialchars($ticket['created_by_name'] ?? '-') . '</td>
                            <td style="color:#2563eb;font-size:8px;">' . htmlspecialchars($ticket['created_by_email'] ?? '-') . '</td>
                            <td>' . htmlspecialchars($ticket['assigned_to_name'] ?? '-') . '</td>
                            <td style="color:#2563eb;font-size:8px;">' . htmlspecialchars($ticket['assigned_to_email'] ?? '-') . '</td>
                            <td>' . htmlspecialchars($ticket['assigned_to_phone'] ?? '-') . '</td>
                            <td style="font-size:8px;">' . htmlspecialchars($ticket['assigned_users_names'] ?? '-') . '</td>
                            <td>' . htmlspecialchars($ticket['commercial_dedie'] ?? '-') . '</td>
                            <td>' . htmlspecialchars($ticket['client_name'] ?? '-') . '</td>
                            <td style="font-size:8px;">' . htmlspecialchars($ticket['adresse_client'] ?? '-') . '</td>
                            <td>' . htmlspecialchars($ticket['interlocuteur'] ?? '-') . '</td>
                            <td>' . htmlspecialchars($ticket['contact_technique'] ?? '-') . '</td>
                            <td>' . htmlspecialchars($ticket['lieu_visite'] ?? '-') . '</td>
                            <td>' . ($ticket['visite_date'] ? date('d/m/Y', strtotime($ticket['visite_date'])) : '-') . '</td>
                            <td>' . ($ticket['visite_heure'] ? substr($ticket['visite_heure'], 0, 5) : '-') . '</td>
                            <td>' . htmlspecialchars($ticket['moyen_transport'] ?? '-') . '</td>
                            <td style="font-size:8px;">' . formatDate($ticket['created_at']) . '</td>
                            <td style="font-size:8px;">' . ($ticket['resolved_at'] ? formatDate($ticket['resolved_at']) : '-') . '</td>
                            <td style="font-size:8px;max-width:150px;word-break:break-word;">' . nl2br(htmlspecialchars(substr($ticket['description'] ?? '', 0, 120))) . (strlen($ticket['description'] ?? '') > 120 ? '...' : '') . '</td>
                            <td style="font-size:8px;">' . htmlspecialchars($ticket['elements_complement'] ?? '-') . '</td>
                            <td style="font-size:8px;">' . ($ticket['attachment'] ? htmlspecialchars(basename($ticket['attachment'])) : '-') . '</td>
                        </tr>';
            $i++;
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
            
            <div class="footer">
                <p>' . date('Y') . ' Plateforme de Ticketing - Spider Madagascar</p>
                <p>Ce document est genere automatiquement. Les donnees sont confidentielles.</p>
            </div>
            
        </body>
        </html>';
        
        return $html;
    }
}
?>