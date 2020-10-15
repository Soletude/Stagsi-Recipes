<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/RN`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

$hasAttachmentsTagRowid = 12;                 // `! +REPLACEME=\d+?
$stagsiDataPath = "C:/foo/stagsi_debug/db";   // `! +REPLACEME=".*"

$pid = escapeshellarg(getenv('PID'));
exec('taskkill /pid '.$pid);
do {
  exec('tasklist /fi "pid eq '.$pid.'" | find "INFO"', $o, $c);
  usleep(100000);
} while ($c);

$db = new PDO("sqlite:$stagsiDataPath/Stagsi.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("DELETE FROM ObjectTags WHERE TagRowId = $hasAttachmentsTagRowid");

$stmt = $db->prepare("INSERT INTO ObjectTags (ObjectRowId, TagRowId) VALUES (?, $hasAttachmentsTagRowid)");

foreach (scandir("$stagsiDataPath/Attachments") as $group) {
  if (ltrim($group, "0..9") !== "") { continue; }

  foreach (scandir("$stagsiDataPath/Attachments/$group") as $id) {
    if (ltrim($id, "0..9") !== "") { continue; }

    if (count( scandir("$stagsiDataPath/Attachments/$group/$id") ) > 2) {
      $stmt->bindValue(1, $id);
      $stmt->execute();
    }
  }
}
