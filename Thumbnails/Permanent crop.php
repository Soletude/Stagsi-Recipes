<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/CA`@

$rowidsFile = 'c:/rowids.txt';  // `! +REPLACEME='.*'
$dataPath = 'c:/stagsi/db';     // `! +REPLACEME='.*'

$ids = [];

foreach (file($rowidsFile) as $id) {
  if ($id = trim($id)) {
    $path = $dataPath.'/'.floor($id / 1000).'/';    // `! +REPLACEME=1000
    rename("$path/c$id.png", "$path/u$id.png");
    $ids[] = $id;
  }
}

echo 'UPDATE Objects SET
ThumbCropX = 0.0,
ThumbCropY = 0.0,
ThumbCropWidth = 0.0,
ThumbCropHeight = 0.0
WHERE RowId IN (', join(',', $ids), ')';
