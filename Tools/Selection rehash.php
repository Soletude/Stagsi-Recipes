<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/LZ`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

$selection = json_decode(strstr(file_get_contents(getenv('selection')), '{'));
system('taskkill /pid '.escapeshellarg(getenv('pid')));

for ($i = 0; $i < 50; ++$i) {
  $line = system('tasklist /fi "pid eq '.escapeshellarg(getenv('pid')).'" /fo csv');
  if (strpos($line, '","') === false) {
    $i = -1;
    break;
  }
  usleep(100000);
}

if ($i >= 0) {
  echo "cannot terminate Stagsi", PHP_EOL;
  exit;
}

$db = new PDO("sqlite:Stagsi.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$setStmt = $db->prepare("UPDATE Objects SET Hash = ? WHERE rowid = ?");

foreach ($selection->Objects as $obj) {
  $id = (int) $obj->RowId;

  if (is_file($obj->FilePath)) {
    $setStmt->bindValue(1, $hash = md5_file($obj->FilePath));
    $setStmt->bindValue(2, $id);
    $setStmt->execute();
  }
}

system('start "Stagsi" '.escapeshellarg(getenv('stagsi')));
