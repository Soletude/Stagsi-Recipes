<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/JV`@

$stagsiDataPath = "C:/My/db";   // `! +REPLACEME=".*"
$db = new PDO("sqlite:$stagsiDataPath/Stagsi.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// `! +REPLACEME=\(\d.*\)
$getStmt = $db->prepare("SELECT * FROM Objects WHERE RowId IN (1, 2, 3)");
$getStmt->execute();
$rows = $getStmt->fetchAll(PDO::FETCH_OBJ);

$setStmt = $db->prepare("UPDATE Objects SET Hash = ? WHERE RowId = ?");

foreach ($rows as $row) {
  // `! +REPLACEME=1000
  $base = "$stagsiDataPath/".floor($row->RowId / 1000)."/$row->RowId";
  $file = is_file($base) ? file_get_contents($base) : "$base.$row->Format";

  $setStmt->bindValue(1, md5_file($file));
  $setStmt->bindValue(2, $row->RowId);
  $setStmt->execute();
}
