<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/QU`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

$rootTagRowId = 1234;   // `! +REPLACEME=\d+?

$db = new PDO("sqlite:C:/Stagsi DB/Stagsi.sqlite");   // `! +REPLACEME=C:.+(?=")
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$updateStmt = $db->prepare("UPDATE ObjectTags SET TagRowId = ? WHERE TagRowId = ?");

$tags = $db->query("SELECT RowId, * FROM Tags WHERE ParentRowId = $rootTagRowId")
  ->fetchAll(PDO::FETCH_OBJ);

$dupTags = [];

foreach ($tags as $tag) {
  $title = preg_replace('/^:+/u', '', $tag->Title);   // `! +REPLACEME=:
  $ref = &$dupTags[mb_strtolower($title, 'utf-8')];
  $ref[] = $tag;
}

foreach ($dupTags as $tags) {
  if (count($tags) > 1) {
    $main = $tags[0];

    foreach ($tags as $tag) {
      if ($tag !== $main) {
        echo "merge $tag->RowId into $main->RowId", PHP_EOL;
        $updateStmt->bindValue(1, $main->RowId);
        $updateStmt->bindValue(2, $tag->RowId);
        $updateStmt->execute();
      }
    }
  }
}
