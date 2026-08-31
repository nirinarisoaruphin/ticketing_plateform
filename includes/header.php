<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Plateforme de Ticketing' ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="assets/css/app.css">
    
    <!-- ✅ STYLES DES NOTIFICATIONS EN TEMPS RÉEL -->
    <style>
        /* ============================================ */
        /* NOTIFICATIONS EN TEMPS RÉEL - STYLE MODERNE */
        /* ============================================ */

        /* ===== TOAST NOTIFICATION ===== */
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            min-width: 320px;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.04);
            padding: 16px 20px;
            animation: notifSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-origin: top right;
            border-left: 5px solid #4F46E5;
            transition: all 0.3s ease;
        }

        .notification-toast .notif-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .notification-toast .notif-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-top: 2px;
        }

        .notification-toast .notif-icon.ticket { background: #eef2ff; color: #4F46E5; }
        .notification-toast .notif-icon.comment { background: #dbeafe; color: #2563EB; }
        .notification-toast .notif-icon.status { background: #fef3c7; color: #D97706; }
        .notification-toast .notif-icon.action { background: #ede9fe; color: #7C3AED; }
        .notification-toast .notif-icon.message { background: #d1fae5; color: #059669; }
        .notification-toast .notif-icon.validation { background: #d1fae5; color: #059669; }
        .notification-toast .notif-icon.assignation { background: #cffafe; color: #0891B2; }
        .notification-toast .notif-icon.planning { background: #fce7f3; color: #BE185D; }
        .notification-toast .notif-icon.general { background: #f3f4f6; color: #6B7280; }

        .notification-toast .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notification-toast .notif-title {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-toast .notif-title .notif-badge {
            font-size: 9px;
            font-weight: 700;
            color: #4F46E5;
            background: #eef2ff;
            padding: 1px 10px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .notification-toast .notif-message {
            font-size: 13px;
            color: #334155;
            line-height: 1.5;
            margin-bottom: 2px;
        }

        .notification-toast .notif-time {
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
        }

        .notification-toast .notif-close {
            flex-shrink: 0;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.2s ease;
            margin-top: -2px;
        }

        .notification-toast .notif-close:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .notification-toast .notif-action {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }

        .notification-toast .notif-action a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #4F46E5;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .notification-toast .notif-action a:hover {
            color: #4338CA;
            gap: 10px;
        }

        /* ===== TYPES DE NOTIFICATIONS ===== */
        .notification-toast.notif-ticket { border-left-color: #4F46E5; }
        .notification-toast.notif-comment { border-left-color: #2563EB; }
        .notification-toast.notif-status { border-left-color: #D97706; }
        .notification-toast.notif-action { border-left-color: #7C3AED; }
        .notification-toast.notif-message { border-left-color: #059669; }
        .notification-toast.notif-validation { border-left-color: #059669; }
        .notification-toast.notif-assignation { border-left-color: #0891B2; }
        .notification-toast.notif-planning { border-left-color: #BE185D; }
        .notification-toast.notif-general { border-left-color: #6B7280; }

        /* ===== ANIMATIONS ===== */
        @keyframes notifSlideIn {
            from {
                opacity: 0;
                transform: translateX(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes notifSlideOut {
            from {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateX(40px) scale(0.95);
            }
        }

        .notification-toast.removing {
            animation: notifSlideOut 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* ===== INDICATEUR SONORE ===== */
        .notification-sound-indicator {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 99999;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            color: white;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .notification-sound-indicator.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* ===== BADGE DE NOTIFICATION ===== */
        .navbar-notif-badge {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #64748b;
        }

        .navbar-notif-badge:hover {
            background: #f1f5f9;
            color: #4F46E5;
        }

        .navbar-notif-badge .badge-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #EF4444;
            border-radius: 50%;
            border: 2px solid white;
            animation: dotPulse 2s infinite;
        }

        @keyframes dotPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .notification-toast {
                top: 10px;
                right: 10px;
                left: 10px;
                min-width: unset;
                max-width: unset;
                padding: 14px 16px;
            }
        }
    </style>
    
    <style>
        .font-inter { font-family: 'Inter', sans-serif; }
        
        /* Animation d'entrée des pages */
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
        
        /* Scrollbar personnalisée */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="font-inter bg-gray-50 text-gray-900 min-h-screen flex flex-col">