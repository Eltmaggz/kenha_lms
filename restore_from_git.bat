@echo off
echo Pulling latest DB from Git...
git pull origin PROMAIN

echo Importing SQL to MySQL...
mysql -u root kenha_lms < kenha_lms.sql

echo ✅ Restore complete.
pause
