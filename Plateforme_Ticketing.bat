@echo off
title 🎟️ Plateforme de Ticketing - SPIDER Madagascar
color 0A

:: ============================================================
:: LANCEUR RÉSEAU - PLATEFORME DE TICKETING
:: SPIDER Madagascar - Ankorondrano
:: ============================================================

:: ✅ CONFIGURATION - ADRESSE IP DU SERVEUR
set SERVER_IP=192.168.90.83
set APP_PATH=/ticketing_plateform/index.php?page=login
set APP_URL=http://%SERVER_IP%%APP_PATH%

echo.
echo ============================================================
echo   🎟️  PLATEFORME DE TICKETING - SPIDER MADAGASCAR
echo   📍 Ankorondrano - Service Technique
echo   🌐 Serveur : %SERVER_IP%
echo ============================================================
echo.

:: Vérifier la connexion au serveur
echo [🔍] Vérification de la connexion au serveur...
ping -n 2 %SERVER_IP% >nul
if errorlevel 1 (
    echo.
    echo ❌ ERREUR : Impossible de joindre le serveur !
    echo.
    echo Veuillez vérifier que :
    echo   1. Le serveur est allumé
    echo   2. Vous êtes connecté au même réseau
    echo   3. L'adresse IP est correcte : %SERVER_IP%
    echo.
    pause
    exit /b 1
)
echo [✅] Serveur accessible

:: Ouvrir la plateforme
echo.
echo [🌐] Ouverture de la plateforme...
start %APP_URL%

echo.
echo [✅] Plateforme lancée avec succès !
echo.
echo 📌 Adresse : %APP_URL%
echo.
echo Appuyez sur une touche pour fermer...
pause >nul