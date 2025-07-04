@echo off
echo Exporting MySQL database to kenha_lms.sql...

REM Export DB to .sql file
mysqldump -u root kenha_lms > kenha_lms.sql

echo Committing database dump to Git...
git add kenha_lms.sql
git commit -m "🔄 Auto-sync kenha_lms.sql"
git push origin PROMAIN

echo ✅ Exported and pushed successfully.
pause
