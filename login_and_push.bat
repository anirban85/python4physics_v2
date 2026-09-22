@echo off
title Python4Physics - GitHub Authorization and Push
echo ========================================================
echo  Python4Physics v2.0 - GitHub Push Tool
echo ========================================================
echo.

echo [Step 1/2] Connecting to your GitHub account (anirban85)...
echo A browser window may open asking to authorize Git Credential Manager.
echo Please click "Authorize" in your browser.
echo.
"C:\Program Files\Git\mingw64\bin\git-credential-manager.exe" github login

echo.
echo [Step 2/2] Pushing project code to https://github.com/anirban85/python4physics_v2.git...
git remote remove origin 2>nul
git remote add origin https://github.com/anirban85/python4physics_v2.git
git branch -M master
git push -u origin master

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo  [SUCCESS] Code successfully pushed to GitHub!
    echo ========================================================
    echo Now you can go to cPanel and click "Create" in Git Version Control.
) else (
    echo.
    echo ========================================================
    echo  [FAILED] Could not push. See error above.
    echo ========================================================
)

echo.
pause
