<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/TU`@

$data = json_decode(strstr(file_get_contents(getenv('SELECTION')), '{'));
$cl = ['C:/Program Files/winamp/winamp.exe'];   // `! +REPLACEME='.*'
foreach ($data->Objects as $object) {
  $cl[] = $object->FilePath;
}
system(join(' ', array_map('escapeshellarg', $cl)));
