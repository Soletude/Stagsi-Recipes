<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/RB`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

$attPath = 'C:/Stagsi DB/Attachments';    // `! +REPLACEME='.*'

$db = new PDO("sqlite:$attPath/../Stagsi.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$getStmt = $db->prepare("SELECT RowId FROM Objects WHERE Hash = ?");

$walk = function ($path, $basename) use ($getStmt, $db, $attPath) {
  foreach (scandir($path) as $icon) {
    if (is_file("$path/$icon")) {
      $orig = "F:/Icons/FatCow/FatCow_Icons32x32/$icon";  // `! +REPLACEME=".*"

      $getStmt->bindValue(1, md5_file($orig));
      $getStmt->execute();
      $id = $getStmt->fetchObject()->RowId;
      $getStmt->closeCursor();

      $atts = "$attPath/".floor($id / 1000)."/$id";
      is_dir($atts) or mkdir($atts, 0755, true);
      copy("$path/$icon", "$atts/$basename.png");
    }
  }
};

// This script assumes that Stagsi database has 32x32 PNGs imported and you
// want to add (attach) 16x16, gray 16x16 and gray 32x32 as variants.

// `! +REPLACEME=".*"
$walk("F:/Icons/FatCow/FatCow_Icons16x16", '16x16');
// `! +REPLACEME=".*"
$walk("F:/Icons/FatCow/FatCow_Icons16x16_Grey", 'Disabled 16x16');
// `! +REPLACEME=".*"
$walk("F:/Icons/FatCow/FatCow_Icons32x32_Grey", 'Disabled 32x32');
