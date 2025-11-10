@echo off
echo ============================================
echo MediArchive Database Import Script
echo ============================================
echo.
echo This script will:
echo 1. Drop existing mediarchive database (if exists)
echo 2. Create fresh mediarchive database
echo 3. Import complete schema and sample data
echo.
echo Sample data includes:
echo - 5 Doctors with different specializations
echo - 10 Patients with complete profiles
echo - 10 Medical certificates
echo - 8 Appointments
echo.
pause

echo.
echo Importing database...
echo.

REM Change to MySQL bin directory
cd /d C:\xampp\mysql\bin

REM Import database (adjust password if needed)
mysql.exe -u root -p115320 < "C:\xampp\htdocs\SYSTEMINTEG\database.sql"

if %errorlevel% equ 0 (
    echo.
    echo ============================================
    echo Database imported successfully!
    echo ============================================
    echo.
    echo You can now access MediArchive at:
    echo http://localhost/SYSTEMINTEG
    echo.
    echo Login Credentials:
    echo.
    echo DOCTORS:
    echo   dr.smith / password - General Medicine
    echo   dr.garcia / password - Internal Medicine
    echo   dr.chen / password - Cardiology
    echo   dr.patel / password - Pediatrics
    echo   dr.johnson / password - Orthopedics
    echo.
    echo PATIENTS:
    echo   alice.j / password
    echo   bob.w / password
    echo   (and 8 more patients)
    echo.
    echo WEB ADMIN:
    echo   webadmin / password
    echo.
) else (
    echo.
    echo ============================================
    echo ERROR: Database import failed!
    echo ============================================
    echo.
    echo Please check:
    echo 1. XAMPP MySQL is running
    echo 2. MySQL root password is blank (default)
    echo 3. File path is correct
    echo.
    echo If you have a MySQL root password, edit this file
    echo and add: -p after -u root
    echo.
)

pause
