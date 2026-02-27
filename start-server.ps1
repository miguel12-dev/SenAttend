# Script para iniciar el servidor de desarrollo
# Incluye soporte completo para PWA

Write-Host "Iniciando servidor de desarrollo SENAttend..." -ForegroundColor Green
Write-Host "URL: http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "Presiona Ctrl+C para detener el servidor" -ForegroundColor Yellow
Write-Host ""

php -S localhost:8000 -t public router.php
