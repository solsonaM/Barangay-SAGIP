#requires -Version 5.1
$ErrorActionPreference = "Stop"

$ProjectRoot = $PSScriptRoot
$LaravelRoot = Join-Path $ProjectRoot "barangay-sagip-web"
$FastApiRoot = Join-Path $ProjectRoot "tokenization-service"
$VenvPython = Join-Path $FastApiRoot ".venv\Scripts\python.exe"

function Fail($Message) {
    Write-Host "`n[ERROR] $Message`n" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $LaravelRoot)) { Fail "Laravel project not found: $LaravelRoot" }
if (-not (Test-Path $FastApiRoot)) { Fail "FastAPI project not found: $FastApiRoot" }
if (-not (Test-Path $VenvPython)) { Fail "Python virtual environment not found: $VenvPython" }

Write-Host "`n==============================================" -ForegroundColor Cyan
Write-Host "       BARANGAY SAGIP - LOCAL STARTUP" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan

$mysqlService = Get-Service -Name "MySQL84" -ErrorAction SilentlyContinue
if ($null -eq $mysqlService) { Fail "MySQL84 service was not found. Install MySQL 8.4 or adjust the service name in this script." }

if ($mysqlService.Status -ne "Running") {
    Write-Host "[1/4] Starting MySQL 8.4..." -ForegroundColor Yellow
    Start-Service -Name "MySQL84"
    Start-Sleep -Seconds 2
} else {
    Write-Host "[1/4] MySQL 8.4 is already running." -ForegroundColor Green
}

Write-Host "[2/4] Starting Vite..." -ForegroundColor Yellow
$viteCommand = "Set-Location -LiteralPath '$LaravelRoot'; npm run dev"
Start-Process powershell.exe -ArgumentList @("-NoProfile","-NoExit","-Command",$viteCommand) -WindowStyle Normal

Write-Host "[3/4] Starting FastAPI..." -ForegroundColor Yellow
$fastApiCommand = "`$env:TOKENIZATION_SERVICE_KEY='sagip-local-service-key'; Set-Location -LiteralPath '$FastApiRoot'; & '$VenvPython' -m uvicorn main:app --host 127.0.0.1 --port 8001"
Start-Process powershell.exe -ArgumentList @("-NoProfile","-NoExit","-Command",$fastApiCommand) -WindowStyle Normal

Write-Host "[4/4] Starting Laravel queue..." -ForegroundColor Yellow
$queueCommand = "Set-Location -LiteralPath '$LaravelRoot'; & php artisan queue:work database --sleep=3 --tries=3 --timeout=90"
Start-Process powershell.exe -ArgumentList @("-NoProfile","-NoExit","-Command",$queueCommand) -WindowStyle Normal

Write-Host "`n==============================================" -ForegroundColor Green
Write-Host "       BARANGAY SAGIP IS STARTING" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host "Application : http://barangay-sagip.test"
Write-Host "Staff Login : http://barangay-sagip.test/admin/login"
Write-Host "FastAPI     : http://127.0.0.1:8001"
Write-Host "FastAPI Docs: http://127.0.0.1:8001/docs"
Write-Host "`nThree service terminals have been opened."
Write-Host "This startup window is no longer needed."
Write-Host ""
Start-Process "http://barangay-sagip.test"
exit 0
