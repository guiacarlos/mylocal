@echo off
REM AxiDB CLI - wrapper Windows.
REM Delega a php cli\main.php con los argumentos originales.
php "%~dp0..\cli\main.php" %*
