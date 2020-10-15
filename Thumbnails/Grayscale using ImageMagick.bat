rem `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
rem Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/NX`@

setlocal ENABLEDELAYEDEXPANSION
rem `! +REPLACEME=C:.*?
cd /D C:\...\Database
for /R %%i in (t*.*) do (
set F=%%i
convert "%%i" -set colorspace Gray -separate -average "u!F:~1!"
)
