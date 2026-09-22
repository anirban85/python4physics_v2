@echo off
echo ========================================================
echo  Python4Physics v2.0 - GitHub Push Automation
echo ========================================================
echo.

set /p REPO_URL="Enter your GitHub Repository URL (e.g. https://github.com/anirban85/python4physics_v2.git): "

if "%REPO_URL%"=="" (
    echo [ERROR] No repository URL provided. Exiting.
    pause
    exit /b 1
)

echo.
echo [1/3] Setting remote origin to %REPO_URL%...
git remote remove origin 2>nul
git remote add origin %REPO_URL%

echo [2/3] Ensuring branch is master...
git branch -M master

echo [3/3] Pushing to GitHub...
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
