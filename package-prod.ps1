# package-prod.ps1
# Script to package e-petraschool1 for production hosting.

$ErrorActionPreference = "Stop"

$buildDir = Join-Path $PSScriptRoot "node_modules/prod_build"
$zipFile = Join-Path $PSScriptRoot "e-petraschool1-ready-to-host.zip"

# Helper function to remove items safely with retries (avoids IDE/antivirus file locks)
function Safe-RemoveItem {
    param (
        [string]$Path
    )
    if (Test-Path $Path) {
        for ($i = 1; $i -le 5; $i++) {
            try {
                Remove-Item $Path -Recurse -Force -ErrorAction Stop
                return
            } catch {
                Write-Host "Warning: File lock detected, retrying deletion in 1s (attempt $i of 5)..." -ForegroundColor Yellow
                Start-Sleep -Seconds 1
            }
        }
        # Final attempt
        Remove-Item $Path -Recurse -Force
    }
}

Write-Host "Starting production packaging..." -ForegroundColor Cyan

# Remove existing zip/build if any
if (Test-Path $zipFile) { 
    Write-Host "Removing existing ZIP file..."
    Remove-Item $zipFile -Force 
}
Safe-RemoveItem $buildDir

# Create build directory
New-Item -ItemType Directory -Path $buildDir | Out-Null

# Copy asset folders recursively
Write-Host "Copying assets (CSS, JS, Images)..."
if (Test-Path (Join-Path $PSScriptRoot "assets/css")) {
    Copy-Item -Path (Join-Path $PSScriptRoot "assets/css") -Destination (Join-Path $buildDir "assets") -Recurse
}
if (Test-Path (Join-Path $PSScriptRoot "assets/js")) {
    Copy-Item -Path (Join-Path $PSScriptRoot "assets/js") -Destination (Join-Path $buildDir "assets") -Recurse
}
if (Test-Path (Join-Path $PSScriptRoot "assets/images")) {
    Copy-Item -Path (Join-Path $PSScriptRoot "assets/images") -Destination (Join-Path $buildDir "assets") -Recurse
}

# Create empty upload directories for production
Write-Host "Creating empty uploads folders..."
New-Item -ItemType Directory -Path (Join-Path $buildDir "assets/uploads") | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildDir "assets/uploads/guru") | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildDir "assets/uploads/inventaris") | Out-Null
New-Item -ItemType Directory -Path (Join-Path $buildDir "assets/uploads/foto_profil") | Out-Null

# Copy config folder (excluding scratch if any, but scratch is at root)
Write-Host "Copying config..."
if (Test-Path (Join-Path $PSScriptRoot "config")) {
    Copy-Item -Path (Join-Path $PSScriptRoot "config") -Destination $buildDir -Recurse
}

# Copy includes folder
Write-Host "Copying includes..."
if (Test-Path (Join-Path $PSScriptRoot "includes")) {
    Copy-Item -Path (Join-Path $PSScriptRoot "includes") -Destination $buildDir -Recurse
}

# Copy modules folder
Write-Host "Copying modules..."
if (Test-Path (Join-Path $PSScriptRoot "modules")) {
    Copy-Item -Path (Join-Path $PSScriptRoot "modules") -Destination $buildDir -Recurse
}

# Copy database folder but ONLY .htaccess (no SQL, no migration files)
Write-Host "Copying database configuration (.htaccess only)..."
New-Item -ItemType Directory -Path (Join-Path $buildDir "database") | Out-Null
if (Test-Path (Join-Path $PSScriptRoot "database/.htaccess")) {
    Copy-Item -Path (Join-Path $PSScriptRoot "database/.htaccess") -Destination (Join-Path $buildDir "database/.htaccess")
}

# Copy root files
Write-Host "Copying root scripts..."
$rootFiles = @(
    ".htaccess",
    "index.php",
    "login.php",
    "logout.php",
    "forgot-password.php",
    "backup.php"
)

foreach ($file in $rootFiles) {
    $filePath = Join-Path $PSScriptRoot $file
    if (Test-Path $filePath) {
        Copy-Item -Path $filePath -Destination $buildDir
    }
}

# Give indexers/antivirus a brief moment to release locks on newly created build files
Start-Sleep -Seconds 1

# Create the ZIP archive
Write-Host "Creating ZIP archive: $zipFile..."
Compress-Archive -Path (Join-Path $buildDir "*") -DestinationPath $zipFile -Force

# Clean up build directory
Write-Host "Cleaning up temporary build folder..."
Safe-RemoveItem $buildDir

Write-Host "=============================================" -ForegroundColor Green
Write-Host "Success: Production bundle created at:" -ForegroundColor Green
Write-Host "  $zipFile" -ForegroundColor Yellow
Write-Host "=============================================" -ForegroundColor Green
