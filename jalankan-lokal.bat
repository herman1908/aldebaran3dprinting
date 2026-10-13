@echo off
setlocal
cd /d "%~dp0"

set "PHP="
where php >nul 2>nul && set "PHP=php"
if not defined PHP if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"
if not defined PHP if exist "C:\php\php.exe" set "PHP=C:\php\php.exe"
if not defined PHP for /d %%D in ("C:\laragon\bin\php\php-*") do if exist "%%D\php.exe" set "PHP=%%D\php.exe"
if not defined PHP for /d %%D in ("D:\laragon\bin\php\php-*") do if exist "%%D\php.exe" set "PHP=%%D\php.exe"

if not defined PHP goto tidak_ada

echo Memakai PHP: %PHP%
echo.
echo Buka http://localhost:8000 di browser. Tekan Ctrl+C untuk berhenti.
echo.
"%PHP%" -S localhost:8000 -t public api/index.php
pause
exit /b 0

:tidak_ada
echo PHP belum ditemukan di komputer ini.
echo.
echo Pilih salah satu cara:
echo   1. Pasang XAMPP dari https://www.apachefriends.org lalu jalankan file ini lagi.
echo   2. Atau unduh PHP dari https://windows.php.net/download, ekstrak ke C:\php, lalu jalankan file ini lagi.
echo.
pause
exit /b 1
