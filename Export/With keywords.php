<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/MS`@

$outputPath = "Desktop\\Exported-with-keywords";  // `! +REPLACEME=".*"
$stagsiDataPath = "Database\\";                   // `! +REPLACEME=".*"
$db = new PDO("sqlite:$stagsiDataPath/Stagsi.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$getTags = $db->prepare("
  SELECT Title
    FROM ObjectTags ot
    JOIN Tags t
      ON ot.TagRowId = t.RowId
   WHERE ot.ObjectRowId = ?
");

// `! +REPLACEME='.*'
$stmt = $db->prepare("SELECT RowId FROM Tags WHERE Title = 'Author'");
$stmt->execute();
list($authorParentId) = $stmt->fetch();

$stmt = $db->prepare("SELECT RowId FROM Objects WHERE Format = 'jpg'");
$stmt->execute();

while ($row = $stmt->fetch()) {
  list($rowid) = $row;

  $getTags->bindValue(1, $rowid);
  $getTags->execute();

  $base = "$stagsiDataPath/".floor($rowid / 1000)."/$rowid";
  $file = is_file($base) ? file_get_contents($base) : "$base.jpg";
  $output = "$outputPath/$rowid.jpg";
  copy($file, $output);

  $iptc = '';

  while ($tag = $getTags->fetch()) {
    list($title, $parent) = $tag;
    // 0x02 is record ID, 0x50 and 0x19 are dataset IDs (a byline, a keyword).
    $prefix = $authorParentId == $parent ? '1C0250' : '1C0219';
    $iptc .= pack('H*n', $prefix, strlen($title)).$title;
  }

  file_put_contents($output, iptcembed($iptc, $output));
}
