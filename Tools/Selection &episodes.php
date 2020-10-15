<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/QZ`@

$selection = json_decode(strstr(file_get_contents(getenv('selection')), '{'));
$seriesTagRowId = null;
$tags = [];

foreach ($selection->Tags as $tag) {
  $tags[$tag->RowId] = $tag;
  // `! +REPLACEME='.*'
  if (!$tag->ParentRowId and $tag->Title === 'Series') {
    $seriesTagRowId = $tag->RowId;
  }
}

$queries = [];

foreach ($selection->ObjectTags as $obj) {
  if ($tags[$obj->TagRowId]->ParentRowId === $seriesTagRowId) {
    $queries[$obj->TagRowId] =
      "\"{$tags[$seriesTagRowId]->Title}\"/\"{$tags[$obj->TagRowId]->Title}\"";
  }
}

// `! +REPLACEME='\).*'
$query = '('.join(' | ', $queries).') Video';
exec(escapeshellarg(getenv('stagsi')).' /search '.esc($query));

// escapeshellarg() simply drops "s.
function esc($s) {
  return '"'.preg_replace('/"/u', '"""', $s).'"';
}
