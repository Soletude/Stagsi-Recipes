<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/YD`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

if (!class_exists('IntlChar')) {
  die('php_intl module is required.');
}

// `! +REPLACEME='C:.*'
copy('C:/Program Files/Stagsi/Skeleton/Stagsi.sqlite', 'Stagsi.sqlite');
// `! +REPLACEME='C:.*'
copy('C:/Program Files/Stagsi/Skeleton/Settings.json', 'Settings.json');
$settings = json_decode(file_get_contents('Settings.json'));
$settings->BitmapScalingMode = 'Fant';
$settings->ThumbFormat = 'png';
$perDir = $settings->FolderSize;
file_put_contents('Settings.json', json_encode($settings, JSON_PRETTY_PRINT));

$bom = "\xEF\xBB\xBF";
$rootTagID = 1;

$assoc = [
  [
    "CallMode" => 1,
    "ExecutableList" => [
      // `! +REPLACEME=nir\w*?
      "nircmd clipboard readfile \"%1\"",
    ],
    "Extension" => ".unicode"
  ],
];

file_put_contents('Associations.json', json_encode($assoc, JSON_PRETTY_PRINT));

$db = new PDO('sqlite:Stagsi.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$selectTag = $db->prepare('SELECT RowId, * FROM Tags WHERE Title = :title AND ParentRowId = :parent');
$selectRootTag = $db->prepare("SELECT RowId, * FROM Tags WHERE Title = :title AND ParentRowId = $rootTagID");
$insertTag = $db->prepare("INSERT INTO Tags (Title, CreationTime, \"Order\", ParentRowId, IconPosition) VALUES (:title, STRFTIME('%s','NOW'), 0, :parent, 0)");
$insertObject = $db->prepare("INSERT INTO Objects (CreationTime, Random, Hash, Title, Format, ThumbCropX, ThumbCropY, ThumbCropWidth, ThumbCropHeight, FileSize, Codepoint) VALUES (STRFTIME('%s','NOW'), RANDOM() % 2147483647, :hash, :title, :format, 0, 0, 0, 0, :size, :codepoint)");
$insertObjectTag = $db->prepare('INSERT INTO ObjectTags (DataObject_RowId, Tag_RowId) VALUES (:object, :tag)');

$ver = join('.', IntlChar::getUnicodeVersion());
$db->exec("INSERT OR REPLACE INTO Stags SET Key = 'Unicode', Value = '$ver'");

try {
  $db->exec("ALTER TABLE Objects ADD COLUMN Codepoint INT NOT NULL DEFAULT 0");
} catch (PDOException $e) {
  // Already added, ignore.
}

list($formatTagID) = createTag(['Unicode File']);
$db->exec("UPDATE Tags SET System = '_unicode' WHERE RowId = $formatTagID");

$codepoints = [];

// Generation of 0x0000..0xFFFF (55346 codepoints) takes 30 minutes.
IntlChar::enumCharNames(0x0000, 0x0FFF, function ($codepoint) use (&$codepoints) {
  $codepoints[] = $codepoint;
});

$count = 0;

foreach ($codepoints as $codepoint) {
  if (++$count % 500 == 0) {
    printf('%s / %s (%.1f%%)...%s', $count, count($codepoints),
      $count / count($codepoints) * 100,
      PHP_EOL);
  }

  $text = IntlChar::chr($codepoint);

  $insertObject->bindValue(':hash', md5($bom.$text));
  $title = IntlChar::charName($codepoint, IntlChar::UNICODE_CHAR_NAME);
  $insertObject->bindValue(':title', $title);
  $insertObject->bindValue(':format', 'unicode');
  $insertObject->bindValue(':size', $bom.$text);
  $insertObject->bindValue(':codepoint', $codepoint);
  $insertObject->execute();
  $objectID = $db->lastInsertId();
  $objectGroup = floor($objectID / $perDir);

  linkTagsTo($objectID, $formatTagID);

  is_dir($objectGroup) or mkdir($objectGroup);
  file_put_contents("$objectGroup/$objectID.unicode", $bom.$text);
  drawText("$objectGroup/u$objectID.png", $text);

  $age = IntlChar::charAge($codepoint);
  $age[count($age) - 1] = 'Unicode v'.join('.', $age);
  createTagFor($objectID, array_merge(['Age'], $age));

  $digit = IntlChar::charDigitValue($codepoint);
  $digit < 0 or createTagFor($objectID, ['Digit', $digit]);

  $num = IntlChar::getNumericValue($codepoint);
  $num === ((float) -123456789) or createTagFor($objectID, ['Numeric', $num]);

  $dir = IntlChar::charDirection($codepoint);
  createTagFor($objectID, ['Direction', prettyConst($dir, 'CHAR_DIRECTION_')]);

  $names = [
    'Name' => IntlChar::UNICODE_CHAR_NAME,
    'Name/Alias' => IntlChar::CHAR_NAME_ALIAS,
    'Name/Choice Count' => IntlChar::CHAR_NAME_CHOICE_COUNT,
  ];

  foreach ($names as $tag => $const) {
    $name = IntlChar::charName($codepoint, $const);
    if ($name) {
      createTagFor($objectID, array_merge(explode('/', $tag), [$name]));
    }
  }

  $name = IntlChar::charName($codepoint, IntlChar::EXTENDED_CHAR_NAME);
  if ($name !== IntlChar::charName($codepoint)) {
    createTagFor($objectID, ['Name', 'Extended', $name]);
  }

  $cat = IntlChar::charType($codepoint);
  createTagFor($objectID, ['Category', prettyConst($cat, 'CHAR_CATEGORY_')]);

  $pair = IntlChar::getBidiPairedBracket($codepoint);
  $pair === $codepoint and $pair = false;
  $lower = IntlChar::tolower($codepoint);
  $lower === $codepoint and $lower = false;
  $upper = IntlChar::toupper($codepoint);
  $upper === $codepoint and $upper = false;
  if ($pair !== false or $lower !== false or $upper !== false) {
    $apath = "Attachments/$objectID";
    is_dir($apath) or mkdir($apath, 0750, true);
    $pair === false or file_put_contents("$apath/Pair.unicode", $bom.IntlChar::chr($pair));
    $lower === false or file_put_contents("$apath/Lower.unicode", $bom.IntlChar::chr($lower));
    $upper === false or file_put_contents("$apath/Upper.unicode", $bom.IntlChar::chr($upper));
  }

  $block = IntlChar::getBlockCode($codepoint);
  // E.g. block ID 279 has no constant.
  createTagFor($objectID, ['Block', prettyConst($block, 'BLOCK_CODE_') ?: "#$block"]);

  $class = IntlChar::getCombiningClass($codepoint);
  $class and createTagFor($objectID, ['Combining Class', $class]);

  $closure = IntlChar::getFC_NFKC_Closure($codepoint);
  strlen($closure) and createTagFor($objectID, ['FC NFKC Closure', $closure]);
}

echo "Wrote $count codepoint objects.", PHP_EOL;

function createTagFor($objectID, array $path) {
  $ids = createTag($path);
  linkTagsTo($objectID, end($ids));
}

function createTag(array $path) {
  global $rootTagID, $db, $selectTag, $selectRootTag, $insertTag;
  $ids = [];

  foreach ($path as $i => $title) {
    if (!$i) {
      $selectRootTag->bindValue(':title', $title);
      $selectRootTag->execute();
      $row = $selectRootTag->fetch();
    } else {
      $selectTag->bindValue(':title', $title);
      $selectTag->bindValue(':parent', end($ids));
      $selectTag->execute();
      $row = $selectTag->fetch();
    }
    if ($row) {
      $ids[] = $row['RowId'];
    } else {
      $insertTag->bindValue(':title', $title);
      $insertTag->bindValue(':parent', end($ids) ?: $rootTagID);
      $insertTag->execute();
      $ids[] = $db->lastInsertId();
    }
  }

  return $ids;
}

function linkTagsTo($objectID, $tagIDs) {
  global $insertObjectTag;

  foreach ((array) $tagIDs as $tagID) {
    $insertObjectTag->bindValue(':object', $objectID);
    $insertObjectTag->bindValue(':tag', $tagID);
    $insertObjectTag->execute();
  }
}

function prettyConst($value, $prefix) {
  static $consts;
  if (!$consts) {
    $consts = (new ReflectionClass(IntlChar::class))->getConstants();
  }
  foreach ($consts as $cname => $cvalue) {
    if (!strncmp($cname, $prefix, strlen($prefix)) and $cvalue === $value) {
      return ucwords(strtolower(strtr(substr($cname, strlen($prefix)), '_', ' ')));
    }
  }
}

function drawText($file, $text) {
  $im = imagecreatetruecolor($w = 128, $h = 128);
  imagesavealpha($im, true);
  // Windows Explorer would show 100% transparent pixels as fully opaque.
  // E.g. if 127 is changed to 126, the image would appear truly transparent.
  // But it's an Explorer's quirk - Photoshop does show proper 100% transparency.
  imagefill($im, 0, 0, imagecolorallocatealpha($im, 255, 255, 255, 127));
  $font = 'c:/windows/fonts/ARIALUNI.TTF';
  $size = 48;
  $box = imagettfbbox($size, 0, $font, $text);
  // 64x64
  //   1:7  ---  32:7
  //    |   '@'   |
  //   1:37 ---  32:37
  $x = round(($w - $box[2] + $box[0]) / 2);
  $y = $h - round(($h - $box[1] + $box[7]) / 2);
//if ($text==='@') {
//  foreach ($box as $i => &$ref) { $i % 2 and $ref += 30; }
//var_dump($text,$box,$x,$y);
//imagerectangle($im, $box[6], $box[7], $box[2], $box[3], imagecolorallocate($im, 255, 0, 0));
//}
  imagettftext($im, $size, 0, $x, $y, imagecolorallocate($im, 0, 0, 0), $font, $text);
  imagepng($im, $file);
}
