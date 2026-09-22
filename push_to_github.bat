@echo off
echo ========================================================
echo  Python4Physics v2.0 - GitHub Push Automation
echo ========================================================
echo.

echo Pushing repository to https://github.com/anirban85/python4physics_v2.git...
git remote remove origin 2>nul
git remote add origin https://github.com/anirban85/python4physics_v2.git
git branch -M master
git push -u origin master

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo  [SUCCESS] Code successfully pushed to GitHub!
    echo ========================================================
) else (
    echo.
    echo [ERROR] Push failed. Please check your credentials / repository permissions.
)

pause
