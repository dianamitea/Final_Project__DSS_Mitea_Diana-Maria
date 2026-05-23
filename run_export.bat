@echo off
echo Petals ^& Bloom - Excel Export
echo ================================
echo Installing dependencies...
pip install pymysql pandas openpyxl --quiet
echo.
echo Running export...
python export_project_data_to_excel.py
echo.
pause
