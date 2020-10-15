<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/LQ`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

mb_internal_encoding('UTF-8');

list($self, $file) = $argv + ['', ''];

if (!is_file($file)) {
  echo "Usage: php $self MyAnimeList.xml";
  exit(1);
}

$xml = new DOMDocument;
$xml->load($file);
$xpath = new DOMXPath($xml);
$objects = $info = [];

$childToTag = [
  'series_type'     => 'Type',
  'series_episodes' => 'Episodes',
  'my_score'        => 'Score',
  'my_status'       => 'Status',
];

foreach ($xpath->evaluate('//anime') as $anime) {
  $object = [];
  foreach ($anime->childNodes as $child) {
    $ref = &$childToTag[$child->nodeName];
    if ($ref) {
      $object[$ref] = $child->textContent;
    } elseif ($child->nodeName === 'series_title') {
      $info[count($objects)]['title'] = $child->textContent;
    } elseif ($child->nodeName === 'series_animedb_id') {
      $info[count($objects)]['id'] = $child->textContent;
    }
  }
  $objects[] = $object;
}

$json = ['Objects' => [], 'Tags' => [], 'ObjectTags' => []];
$temps = [];

foreach ($objects as $id => $object) {
  $temps[] = $temp = tempnam(sys_get_temp_dir(), '').'.url';
  file_put_contents($temp, "[InternetShortcut]\nURL=https://myanimelist.net/anime/".$info[$id]['id']);

  $json['Objects'][] = [
    'CreationTime' => time(),
    'FilePath' => $temp,
    'Random' => mt_rand(),
    'RowId' => $id + 1,
    'Title' => $info[$id]['title'],
  ];

  foreach ($object as $tag => $value) {
    $tag = findCreateTag($json['Tags'], [$tag, $value]);
    $json['ObjectTags'][] = [
      'ObjectRowId' => $id + 1,
      'TagRowId' => $tag['RowId'],
      'Weight' => $tag === 'Score' ? $value : 0,
    ];
  }
}

echo 'Stagsi JSON 1 '.json_encode($json, /*JSON_PRETTY_PRINT+*/ 0);

function findTag(array $tags, $title, $parent = null) {
  foreach ($tags as $ttag) {
    if ($ttag['ParentRowId'] === $parent and
        mb_strtolower($ttag['Title']) === mb_strtolower($title)) {
      return $ttag;
    }
  }
}

function findCreateTag(array &$tags, array $path) {
  static $nextID = 1;
  $parent = null;

  foreach ($path as $ptag) {
    $ttag = findTag($tags, $ptag, $parent);
    if (!$ttag) {
      $tags[] = $ttag = [
        'CreationTime' => time(),
        'ParentRowId' => $parent,
        'RowId' => $nextID++,
        'Title' => (string) $ptag,
      ];
    }
    $parent = $ttag['RowId'];
  }

  return $ttag;
}
