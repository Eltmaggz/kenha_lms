@echo off
set DB_NAME=kenha_lms
set DB_USER=root
set FILE_NAME=kenha_lms.db

echo Exporting MySQL database to %FILE_NAME%...
mysqldump -u %DB_USER% %DB_NAME% > %FILE_NAME%

echo Committing database dump to Git...
git add %FILE_NAME%
git commit -m "🔄 Auto-sync kenha_lms.db"
git push

echo ✅ Exported and pushed successfully.
pause
