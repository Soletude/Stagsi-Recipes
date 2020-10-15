# `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
# Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/CQ`@

# `! +REPLACEME=".*"
$db = "F:\Stagsi\DB\Stagsi.sqlite"
$day = (Get-Date).DayOfWeek.Value__
# First, reset order of all objects.
sqlite3 "$db" "UPDATE Objects SET Random = 0 WHERE RowId IN (
  SELECT o.RowId
    FROM Objects o
    JOIN ObjectTags ot
      ON ot.ObjectRowId = o.RowId
   WHERE ot.TagRowId IN (
      SELECT RowId
        FROM Tags
       WHERE ParentRowId = (SELECT RowId FROM Tags WHERE Title = 'Weekdays')
    )
)"
# Then, bump objects with the matching weekday.
sqlite3 "$db" "UPDATE Objects SET Random = -100 WHERE RowId IN (
  SELECT o.RowId
    FROM Objects o
    JOIN ObjectTags ot
      ON ot.ObjectRowId = o.RowId
   WHERE ot.TagRowId IN (
      SELECT RowId
        FROM Tags
       WHERE ParentRowId = (SELECT RowId FROM Tags WHERE Title = 'Weekdays')
         AND Title LIKE '$day%'
    )
)"
