rem `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
rem Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/QA`@

rem `! +REPLACEME=\w+?\.sqlite|-separator\ \S+?
sqlite3.exe -csv -separator ";" Stagsi.sqlite "SELECT o.Title, o.Hash, o.FileSize, o.Format, GROUP_CONCAT(t.Title, '"""') FROM Objects o JOIN ObjectTags ot ON ot.ObjectRowId = o.RowId JOIN Tags t ON t.RowId = ot.TagRowId GROUP BY o.RowId"
