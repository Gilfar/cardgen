@echo off
cls
echo.
cd %0\..\..

set "location=%CD:\=\\%\\"

echo Card generator detected:
echo %CD%
echo.

echo Updating registry entries...
echo Windows Registry Editor Version 5.00 > "%temp%\shell.reg"

echo Entry: Generate Pages...
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Pages] >> "%temp%\shell.reg"
echo @="Generate &Pages" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Pages\Command] >> "%temp%\shell.reg"
echo @="\"%location%generatePages.bat\" \"%%1\"" >> "%temp%\shell.reg"

echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Pages] >> "%temp%\shell.reg"
echo @="Generate &Pages" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Pages\Command] >> "%temp%\shell.reg"
echo @="\"%location%generatePages.bat\" \"%%1\"" >> "%temp%\shell.reg"

echo Entry: Generate Decklist Pages...
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Decklist Pages] >> "%temp%\shell.reg"
echo @="Generate &Decklist Pages" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Decklist Pages\Command] >> "%temp%\shell.reg"
echo @="\"%location%generatePages-decklists.bat\" \"%%1\"" >> "%temp%\shell.reg"

echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Decklist Pages] >> "%temp%\shell.reg"
echo @="Generate &Decklist Pages" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Decklist Pages\Command] >> "%temp%\shell.reg"
echo @="\"%location%generatePages-decklists.bat\" \"%%1\"" >> "%temp%\shell.reg"

echo Entry: Generate Cards...
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Cards] >> "%temp%\shell.reg"
echo @="Generate &Cards" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Cards\Command] >> "%temp%\shell.reg"
echo @="\"%location%generateCards.bat\" \"%%1\"" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"

echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Cards] >> "%temp%\shell.reg"
echo @="Generate &Cards" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Cards\Command] >> "%temp%\shell.reg"
echo @="\"%location%generateCards.bat\" \"%%1\"" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"

echo Entry: Generate Decklist Cards...
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Decklist Cards] >> "%temp%\shell.reg"
echo @="Generate Decklist Ca&rds" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Generate Decklist Cards\Command] >> "%temp%\shell.reg"
echo @="\"%location%generateCards-decklists.bat\" \"%%1\"" >> "%temp%\shell.reg"

echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Decklist Cards] >> "%temp%\shell.reg"
echo @="Generate Decklist Ca&rds" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\Folder\Shell\MTG Generate Decklist Cards\Command] >> "%temp%\shell.reg"
echo @="\"%location%generateCards-decklists.bat\" \"%%1\"" >> "%temp%\shell.reg"

echo Entry: Diff Decklists...
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Diff Decklists] >> "%temp%\shell.reg"
echo @="Di&ff Decklists" >> "%temp%\shell.reg"
echo "extended"="" >> "%temp%\shell.reg"
echo [HKEY_CLASSES_ROOT\*\Shell\MTG Diff Decklists\Command] >> "%temp%\shell.reg"
echo @="\"%location%misc\\diffDecklists.bat\" \"%%1\"" >> "%temp%\shell.reg"

regedit /s "%temp%\shell.reg"
del "%temp%\shell.reg"

echo Complete.
echo.
echo To use this feature, hold down the Shift key when right clicking any file.

echo.
pause
