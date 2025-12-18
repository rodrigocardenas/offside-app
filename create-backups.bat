@echo off
echo Creando backups de las vistas...
copy "resources\views\groups\show.blade.php" "resources\views\groups\show.blade.php.backup" /Y
echo.
echo Listando backups creados:
dir "resources\views\groups\*.backup" /B
echo.
echo Backups creados exitosamente!
pause
