@echo off
:loop
setlocal enabledelayedexpansion
set "currentTime=%time%"
if "!currentTime:~0,5!" geq "16:30" (
    php "C:\xampp\htdocs\Student-Attendance-System02\ClassTeacher\insert_timetable_days_off.php"
    exit /b
) else (
    timeout /t 60 /nobreak > NUL
    goto loop
)
