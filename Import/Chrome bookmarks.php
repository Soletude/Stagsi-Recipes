<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/OV`@

$file = 'bookmarks_1_2_20.html';  // `! +REPLACEME='.*'
$path = 'output/';                // `! +REPLACEME='.*'

is_dir($path) or mkdir($path, 0777, true);

preg_match_all('/<DT><A HREF="([^"]+)[^>]*>([^<]+)/ui',
               file_get_contents($file), $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
  list(, $url, $title) = $match;
  $title = preg_replace('/[^\pL\pN ]+/u', ' ', $title);
  $title = preg_replace('/^\s*|\s*$/u', '', $title);
  $file = rtrim($path, '\\/')."/$title.url";
  file_put_contents($file, "[InternetShortcut]\r\nURL=$url");
}
