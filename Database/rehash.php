<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/XU`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

$self = array_shift($argv);
$dbPath = array_shift($argv);
$ids = $argv;

if (!is_file($dbPath) or !$ids) {
  echo "Usage: php ", basename($self), " stagsi.sqlite id [id id ...]", PHP_EOL;
  exit(1);
}

chdir(dirname($dbPath));
$settings = json_decode(file_get_contents('Settings.json'));
$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$getStmt = $db->prepare("SELECT * FROM Objects WHERE rowid = ?");
$setStmt = $db->prepare("UPDATE Objects SET Hash = ? WHERE rowid = ?");

foreach ($ids as $id) {
  $id = (int) $id;

  $getStmt->bindValue(1, $id);
  $getStmt->execute();
  $row = $getStmt->fetchObject();

  if (!$row) {
    echo "$id: no such row", PHP_EOL;
    continue;
  }

  $base = floor($id / $settings->FolderSize)."/$id";
  $file = is_file($base) ? file_get_contents($base) : "$base.$row->Format";

  if (!is_file($file)) {
    echo "$id: no data file: $file", PHP_EOL;
    continue;
  }

  $setStmt->bindValue(1, $hash = md5_file($file));
  $setStmt->bindValue(2, $id);
  $setStmt->execute();

  echo $hash === $row->Hash ? "$id: unchanged" : "$id: updated", PHP_EOL;
}
