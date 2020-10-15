<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/GJ`@

$stagsiDataPath = 'C:/Stagsi Demo/db';   // `! +REPLACEME='.*'

function nodeToWHERE(Stags\Query\Node $node, PDO $db) {
  if ($node instanceof Stags\Query\ListNode) {
    $res = [];
    foreach ($node->children as $child) {
      $res[] = '('.nodeToWHERE($child, $db).')';
    }
    return join($node->isAnd ? ' AND ' : ' OR ', $res);
  } else if ($node instanceof Stags\Query\IdNode) {
    return 'o.RowId '.($node->isNegative ? '<>' : '=').' '.((int) $node->id);
  } else if ($node instanceof Stags\Query\TagNode) {
    if ($node->isRecursive) {
      throw new Exception("Recursive (~) tag search is not supported.");
    }
    return 'LOWER(t.Title) '.
            ($node->isNegative ? '<>' : '=').' '.
            strtolower($db->quote(end($node->path)));
  } else {
    throw new Exception("Unknown query node type.");
  }
}

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new \ErrorException($msg, 0, $severity, $file, $line);
}, -1);

extract($_REQUEST + [
  'query'   => '',
  'sort'    => 'rowid',
  'desc'    => true,
  'view'    => 'thumbs',
  'thumb'   => '',
  'page'    => 1,
], EXTR_PREFIX_ALL, 'r');

if ($r_thumb = (int) $r_thumb) {
  chdir($stagsiDataPath);
  // `! +REPLACEME=1000
  $path = floor($r_thumb / 1000)."/[cut]$r_thumb.*";
  $files = glob($path);
  $files = array_combine(array_map(function ($f) { return basename($f)[0]; }, $files), $files);
  $files += ['c' => '', 'u' => '', 't' => ''];
  $file = $files['c'] ?: ($files['u'] ?: $files['t']);
  $info = getimagesize($file);
  header("Content-Type: $info[mime]");
  readfile($file);
  exit;
}

$db = new PDO("sqlite:$stagsiDataPath/db.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtain from https://github.com/Soletude/Stags-Query-Parser
require_once 'Stags-Query-Parser/Stags.Query.php';    // `! +REPLACEME='.*'
$where = nodeToWHERE((new Stags\Query\Parser($r_query))->parse(), $db) ?: '1';

ltrim($r_sort, 'a..z') === '' or $r_sort = 'rowid';
$sort = "o.$r_sort";
$desc = $r_desc ? 'DESC' : 'ASC';

$sql = <<<SQL
SELECT o.RowId, o.Title, GROUP_CONCAT(t.Title, '"') TagTitles
  FROM Object o
  JOIN dataobjecttags ot
    ON ot.dataobject_rowid = o.RowId
  JOIN Tags t
    ON t.RowId = ot.tag_rowid
 WHERE $where
 GROUP BY o.RowId
 ORDER BY $sort $desc
 LIMIT :start, :count
SQL;

$stmt = $db->prepare($sql);
$stmt->bindValue('start', ($r_page - 1) * 100);
$stmt->bindValue('count', 100);
$stmt->execute();

$qs = '?'.$_SERVER['QUERY_STRING'].'&';
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Simple Stagsi Web Viewer</title>
    <style>
      .group { margin-right: 1em; }
      .results.thumbs img { height: 100px; }
      .results.list { text-align: center; }
    </style>
  </head>
  <body>
    <h1><?=htmlspecialchars($stagsiDataPath)?></h1>

    <form action="">
      <label class="group">
        <b>Search query:</b>
        <input value="<?=htmlspecialchars($r_query)?>" name="query">
      </label>

      <span class="group">
        <b>Sort by:</b>
        <select name="sort">
          <option value="rowid" <?=$r_sort == 'rowid' ? 'selected' : ''?>>Object ID</option>
          <option value="random" <?=$r_sort == 'random' ? 'selected' : ''?>>Random</option>
          <option value="title" <?=$r_sort == 'title' ? 'selected' : ''?>>Title</option>
          <option value="filesize" <?=$r_sort == 'filesize' ? 'selected' : ''?>>File size</option>
          <option value="format" <?=$r_sort == 'format' ? 'selected' : ''?>>Format</option>
        </select>

        <label>
          <input type="hidden" name="desc" value="0">
          <input type="checkbox" name="desc" value="1" <?=$r_desc ? 'checked' : ''?>>
          Descending
        </label>
      </span>

      <span class="group">
        <b>View as:</b>
        <label>
          <input type="radio" name="view" value="thumbs" <?=$r_view === 'thumbs' ? 'checked' : ''?>>
          Thumbnails
        </label>
        <label>
          <input type="radio" name="view" value="list" <?=$r_view === 'list' ? 'checked' : ''?>>
          List
        </label>
      </span>

      <button type="submit">Apply</button>
    </form>

    <p>
      <a href="<?=htmlspecialchars("$qs&page=".($r_page + 1))?>">Next page</a>
      |
      <?php if ($r_page > 1) {?>
        <a href="<?=htmlspecialchars("$qs&page=".($r_page - 1))?>">Previous page</a>
      <?php }?>
      |
      <a href="?">Reset all</a>
    </p>

    <div class="results <?=htmlspecialchars($r_view)?>">
      <?php while ($row = $stmt->fetchObject()) {?>
        <?php if ($r_view === 'list') {?>
          <hr>
          <a href="<?=htmlspecialchars("?thumb=$row->RowId")?>">
            <img src="<?=htmlspecialchars("?thumb=$row->RowId")?>">
          </a>
          <p><?=htmlspecialchars($row->Title)?></p>
          <p>
            <?php foreach (explode('"', $row->TagTitles) as $tag) {?>
              [ <a href="<?=htmlspecialchars("$qs&query=\"$tag\"")?>">
                <?=htmlspecialchars($tag)?></a> ]
            <?php }?>
          </p>
        <?php } else {?>
          <a href="<?=htmlspecialchars("?thumb=$row->RowId")?>">
            <img src="<?=htmlspecialchars("?thumb=$row->RowId")?>"
                 title="<?=htmlspecialchars($row->Title)?>">
          </a>
        <?php }?>
      <?php }?>
    </div>
  </body>
</html>
