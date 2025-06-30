@echo off
set DB_NAME=kenha_lms
set DB_USER=root
set FILE_NAME=kenha_lms.db

echo Pulling latest changes from Git...
git pull

echo Restoring MySQL database from %FILE_NAME%...
mysql -u %DB_USER% %DB_NAME% < %FILE_NAME%

echo ✅ Restored successfully from %FILE_NAME%.
pause
