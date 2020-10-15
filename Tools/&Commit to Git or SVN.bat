rem `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$

@echo off

rem Extensions are needed to open the associated program on %tempfile%
rem without specyfing any EXE to run.
setlocal ENABLEEXTENSIONS ENABLEDELAYEDEXPANSION

set tgit=no
set git=no
set tsvn=no
set svn=no
set tempfile=%temp%\%~n0-%random%.txt

rem Try locating TortoiseGit and other systems we support in %PATH%
rem (installers of TortoiseXXX add bin\ to there by default) and in typical
rem installation paths.

rem TortoiseGit requires console Git binaries but doesn't package them.
rem TortoiseSVN doesn't require console SVN tools - it does package them
rem but by default they are not installed. If installed, they're found in
rem the directory of TortoiseSVN\bin (unlike with TotroiseGit).

:find_tgit
rem Not checking for TortoiseGitProc.exe because it shows a window on any
rem command; tgittouch.exe comes with the distribution and returns
rem -1 on no arguments, 0 on success (9009 is returned by cmd.exe
rem if the program was not found).
for %%i in (
  ""
  "%ProgramFiles%\TortoiseGit\bin\"
  "%ProgramFiles(x86)%\TortoiseGit\bin\"
  "%ProgramW6432%\TortoiseGit\bin\"
) do (
  %%itgittouch 2>NUL
  if !errorlevel! == -1 (
    set tgit=%%i
    goto find_git
  )
)


:find_git
for %%i in (
  ""
  "%ProgramFiles%\git\bin\"
  "%ProgramFiles(x86)%\git\bin\"
  "%ProgramW6432%\git\bin\"
) do (
  rem Can't just git | find because if git is not found this script's execution
  rem is stopped.
  %%igit --version >"%tempfile%" 2>NUL
  find "git version" <"%tempfile%" >NUL
  if !errorlevel! == 0 (
    set git=%%i
    goto find_tsvn
  )
)


:find_tsvn
for %%i in (
  ""
  "%ProgramFiles%\TortoiseSVN\bin\"
  "%ProgramFiles(x86)%\TortoiseSVN\bin\"
  "%ProgramW6432%\TortoiseSVN\bin\"
) do (
  %%iTSVNCache 2>NUL
  if !errorlevel! == 0 (
    set tsvn=%%i
    goto find_svn
  )
)


:find_svn
for %%i in (
  ""
  "%ProgramFiles%\TortoiseSVN\bin\"
  "%ProgramFiles(x86)%\TortoiseSVN\bin\"
  "%ProgramW6432%\TortoiseSVN\bin\"
) do (
  %%isvn --version >"%tempfile%" 2>NUL
  find "Apache" <"%tempfile%" >NUL
  if !errorlevel! == 0 (
    set svn=%%i
    goto found
  )
)


:found

if %git% == no if %svn% == no (
  echo This Tool only supports Git and SVN systems. It found none installed.
  echo If it's an error or you want to add support for Hg or something -
  echo feel free to contribute: https://stagsi.com
  pause
  goto end
)

(
  echo Enter your commit message here. Save and exit when done.
  echo To abort the commit either do not change this text or erase all of it.
  echo.
  echo All changed files will be committed:
) >"%tempfile%"

%git%git status --short >>"%tempfile%" 2>NUL
if %errorlevel% == 0 (
  for %%f in ("%tempfile%") do set tempsize=%%~zf
  if %tgit% == no (
    "%tempfile%"
    if !errorlevel! == 0 for %%f in ("%tempfile%") do (
      if %%~zf == 0 goto end
      if %%~zf == !tempsize! goto end
      %git%git add -A .
      %git%git commit -F "%tempfile%" --cleanup strip
    )
  ) else (
    start "" %tgit%TortoiseGitProc /command:commit
  )
  goto end
)

%svn%svn status >>"%tempfile%" 2>&1
find "W155007:" <"%tempfile%" >NUL
if !errorlevel! == 1 (
rem -- only differs from the git block above in %tsvn% below --
  for %%f in ("%tempfile%") do set tempsize=%%~zf
  if %tsvn% == no (
    "%tempfile%"
    if !errorlevel! == 0 for %%f in ("%tempfile%") do (
      if %%~zf == 0 goto end
      if %%~zf == !tempsize! goto end
rem -- snip --
      %svn%svn add --force .
      %svn%svn update
      %svn%svn commit -F "%tempfile%"
    )
  ) else (
    start "" %tsvn%TortoiseProc /path:. /command:commit
  )
  goto end
)

echo This database is not part of any Git or SVN repository.
echo Create a repository here or clone/checkout an existing one,
echo then run this Tool again.
pause

:end
del "%tempfile%"
