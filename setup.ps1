# ============================================================
# PRISM Laravel Project Setup
# Run after cloning:
#
#   .\setup.ps1
#
# ============================================================

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "=========================================="
Write-Host " PRISM Project Setup"
Write-Host "=========================================="
Write-Host ""

# ============================================================
# 1. CHECK PHP
# ============================================================

Write-Host "[1/8] Checking PHP..."

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host ""
    Write-Host "ERROR: PHP was not found."
    Write-Host "Make sure C:\xampp\php is added to Windows PATH."
    exit 1
}

$phpPath = (Get-Command php).Source

Write-Host "PHP found:"
Write-Host $phpPath

if ($phpPath -notlike "C:\xampp\php\*") {
    Write-Host ""
    Write-Host "WARNING: PRISM expects XAMPP PHP."
    Write-Host "Current PHP:"
    Write-Host $phpPath
    Write-Host ""
    Write-Host "Recommended:"
    Write-Host "C:\xampp\php\php.exe"
    exit 1
}

# ============================================================
# 2. CHECK REQUIRED PHP EXTENSIONS
# ============================================================

Write-Host ""
Write-Host "[2/8] Checking PHP extensions..."

$phpModules = php -m

$requiredExtensions = @(
    "gd",
    "intl",
    "pdo_mysql",
    "openssl",
    "mbstring",
    "fileinfo"
)

$missingExtensions = @()

foreach ($extension in $requiredExtensions) {

    if ($phpModules -notcontains $extension) {
        $missingExtensions += $extension
    }

}

if ($missingExtensions.Count -gt 0) {

    Write-Host ""
    Write-Host "ERROR: Missing PHP extensions:"
    
    foreach ($extension in $missingExtensions) {
        Write-Host "  $extension"
    }

    Write-Host ""
    Write-Host "Enable them inside:"
    Write-Host "C:\xampp\php\php.ini"

    exit 1
}

Write-Host "Required PHP extensions are enabled."

# ============================================================
# 3. CHECK .ENV
# ============================================================

Write-Host ""
Write-Host "[3/8] Checking .env..."

if (-not (Test-Path ".env")) {

    if (Test-Path ".env.example") {

        Write-Host ".env not found."
        Write-Host "Creating it from .env.example..."

        Copy-Item ".env.example" ".env"

    }
    else {

        Write-Host "ERROR: .env and .env.example are missing."
        exit 1
    }

}
else {

    Write-Host ".env found."

}

# ============================================================
# 4. CREATE LARAVEL STORAGE DIRECTORIES
# ============================================================

Write-Host ""
Write-Host "[4/8] Preparing Laravel storage..."

$directories = @(
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\framework\cache\data",
    "storage\logs",
    "storage\certs"
)

foreach ($directory in $directories) {

    if (-not (Test-Path $directory)) {

        New-Item -ItemType Directory -Force $directory | Out-Null
        Write-Host "Created: $directory"

    }

}

Write-Host "Laravel storage directories ready."

# ============================================================
# 5. CHECK AIVEN SSL CERTIFICATE
# ============================================================

Write-Host ""
Write-Host "[5/8] Checking Aiven SSL certificate..."

if (-not (Test-Path "storage\certs\ca.pem")) {

    Write-Host ""
    Write-Host "WARNING: Aiven ca.pem is missing."
    Write-Host ""
    Write-Host "Download the CA certificate from Aiven and place it here:"
    Write-Host ""
    Write-Host "storage\certs\ca.pem"
    Write-Host ""
    Write-Host "Setup cannot test the database until this file exists."

}
else {

    Write-Host "Aiven CA certificate found."

}

# ============================================================
# 6. INSTALL COMPOSER DEPENDENCIES
# ============================================================

Write-Host ""
Write-Host "[6/8] Installing Composer dependencies..."

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {

    Write-Host "ERROR: Composer was not found."
    exit 1

}

composer install

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: composer install failed."
    exit 1
}

# ============================================================
# 7. INSTALL AND BUILD FRONTEND
# ============================================================

Write-Host ""
Write-Host "[7/8] Installing frontend dependencies..."

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {

    Write-Host "ERROR: npm was not found."
    Write-Host "Install Node.js first."
    exit 1

}

npm install

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: npm install failed."
    exit 1
}

Write-Host ""
Write-Host "Building Vite assets..."

npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Vite build failed."
    exit 1
}

# ============================================================
# 8. PREPARE LARAVEL
# ============================================================

Write-Host ""
Write-Host "[8/8] Preparing Laravel..."

php artisan config:clear

if (-not (Select-String -Path ".env" -Pattern "^APP_KEY=base64:" -Quiet)) {

    Write-Host "Generating Laravel application key..."
    php artisan key:generate

}

php artisan optimize:clear

Write-Host ""
Write-Host "=========================================="
Write-Host " PRISM setup completed"
Write-Host "=========================================="
Write-Host ""
Write-Host "Start PRISM with:"
Write-Host ""
Write-Host "php artisan serve"
Write-Host ""