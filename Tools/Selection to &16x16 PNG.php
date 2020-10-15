<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/VA`@

$size = 16;   // `! +REPLACEME=\d+?
$imagick = "C:\\Program Files\\ImageMagick\\convert.exe";   // `! +REPLACEME=".*"

strtok(file_get_contents(getenv('SELECTION')), '{');
$data = json_decode('{'.strtok(null));

foreach ($data->Objects as $obj) {
  system(escapeshellarg($imagick).
         " -size {$size}x$size -background none -trim".
         ' '.escapeshellarg($obj->FilePath).' '.
         ' '.escapeshellarg(getenv('USERPROFILE').'\\Desktop\\'.$obj->Title." {$size}x$size.png"));
}
