<?php
// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/YS`@

set_error_handler(function ($severity, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $severity, $file, $line);
}, -1);

$meta = processDir('C:/music');   // `! +REPLACEME='.*'
file_put_contents('c:/tags.json', json_encode($meta));  // `! +REPLACEME='.*'

function nameInfo(array $frames) {
  $res = [];

  $idToTag = ['TCOM' => 'Artist', 'TALB' => 'Album', 'GRP1' => 'Grouping',
              'COMM' => 'Comment'];

  foreach ($idToTag as $id => $tag) {
    if (!empty($frames[$id])) {
      foreach ($frames[$id] as $s) {
        foreach (preg_split('/\s*,\s*/u', $s) as $s) {
          $res[] = ['Tag' => [$tag, $s]];
          // This script assigns all tags the default weight of 0.
          // You can assign custom weight like so:
          //$res[] = ['Tag' => [$tag, $s], 'Weight' => 20];
        }
      }
    }
  }

  if (!empty($frames['TCON'])) {
    foreach ($frames['TCON'] as $s) {
      $res[] = ['Tag' => ['Genre', $s]];
    }
  }

  if (!empty($frames['TBPM'])) {
    foreach ($frames['TBPM'] as $s) {
      $res[] = ['Tag' => ['BPM', $s]];
    }
  }

  return $res;
}

function processDir($path) {
  $res = [];
  $path = rtrim($path, '\\/').'/';
  foreach (scandir($path) as $file) {
    $full = "$path/$file";
    if (is_file($full)) {
      try {
        $frames = parseFileID3v2($full);
      } catch (NoID3Tag $e) {
        echo "no ID3v2 tag, skipping", PHP_EOL;
        continue;
      } catch (\Throwable $e) {
        echo '!! ', $e->getMessage(), PHP_EOL;
        continue;
      }
      $tags = nameInfo($frames);
      $tags and $res[] = ['File' => $full, 'Tags' => $tags];
    } elseif (is_dir($full) and $file[0] !== '.') {
      $res = array_merge($res, processDir($full));
    }
  }
  return $res;
}

// https://id3.org/id3v2.4.0-structure
// https://id3.org/id3v2.4.0-frames
function parseFileID3v2($file) {
  echo "== ", preg_replace('![/\\\\]+!u', '\\', $file), " ==", PHP_EOL;

  $f = fopen($file, 'rb');
  $header = unpack('a3magic/nversion/Cflags/Nlength', fread($f, 3+2+1+4));

  if ($header['magic'] !== 'ID3') {
    throw new NoID3Tag("No ID3v2 tag present.");
  } elseif ($header['version'] > 4<<8) {
    throw new Exception("Invalid ID3v2 tag version ($header[version]).");
  }

  assertNoFlags($header['flags'], 'tag');

  $header['length'] = unsynchInt( $header['length'] );
  echo "ID3v2 tag length: $header[length] bytes", PHP_EOL;

  $framesBuf = fread($f, $header['length']);
  fclose($f);

  if ($header['version'] < 3<<8) {
    $frames = splitID3v22Frames($framesBuf);
  } else {
    $frames = splitID3v2Frames($framesBuf);
  }

  echo "Found ", count($frames), " frames: ", join(' ', array_map(function ($frame) { return "$frame[id]-".strlen($frame['data']); }, $frames)), PHP_EOL;
  echo PHP_EOL;

  $usefulFrames = [];

  foreach ($frames as $frame) {
    // Frame types found in iTunes-tagged MP3s:
    //
    // -- 2.x --
    // COM - Comments; iTunes GUI: "comments"
    // TAL - Album/Movie/Show title; iTunes GUI: "album"
    // TBP - BPM (Beats Per Minute); iTunes GUI: "bpm"
    // TCO - Content type; iTunes GUI: "genre"
    // TP1 - Lead artist(s)/Lead performer(s)/Soloist(s)/Performing group; iTunes GUI: "artist"
    // TP2 - Band/Orchestra/Accompaniment
    // TS2 - ???
    // TSA - ???
    // TT2 - Title/Songname/Content description; iTunes GUI: "song"
    // TYE - Year; iTunes GUI: "year"
    //
    // -- 3.x/4.x --
    // APIC - Attached picture
    // COMM - Comments; iTunes GUI: "comments"
    // GRP1 - ???; iTunes GUI: "grouping"
    // MCDI - Music CD identifier
    // TALB - Album/Movie/Show title; iTunes GUI: "album"
    // TBPM - BPM (beats per minute); iTunes GUI: "bpm"
    // TCOM - Composer; iTunes GUI: "artist"
    // TCON - Content type; iTunes GUI: "genre"
    // TCOP - Copyright message
    // TENC - Encoded by
    // TIT2 - Title/songname/content description
    // TLEN - Length
    // TMED - Media type
    // TPE1 - Lead performer(s)/Soloist(s)
    // TPE2 - Band/orchestra/accompaniment
    // TPOS - Part of a set
    // TRCK - Track number/Position in set
    // TSSE - Software/Hardware and settings used for encoding
    // TXXX - User defined text information frame
    // TYER - ???
    // WXXX - User defined URL link frame
    $id2to34 = ['COM' => 'COMM', 'TAL' => 'TALB', 'TBP' => 'TBPM', 'TCO' => 'TCON', 'TP1' => 'TCOM'];
    switch ($id = $frame['id']) {
      // 2.x
      default:
        if (!isset($id2to34[$id])) { break; }
        $id = $id2to34[$id];
      // 3.x/4.x
      case 'COMM':
      case 'GRP1':
      case 'TALB':
      case 'TBPM':
      case 'TCOM':
      case 'TCON':
        //echo "> $frame[id]: ", bin2hex($frame['data']), PHP_EOL; break;
        echo "> $frame[id] ($id):  ";
        assertNoFlags($frame['flags'], 'frame');
        echo join('|', $strings = decodeStrings($frame['data']));
        echo PHP_EOL;
        foreach ($strings as &$ref) {
          $ref = preg_replace('/^\s*|\s*$/u', '', $ref);
        }
        $usefulFrames[$id] = $strings;
        break;
    }
  }

  return $usefulFrames;
}

// 2.x.
// https://id3.org/id3v2-00
function splitID3v22Frames($buf) {
  $frames = [];

  while (strlen($buf)) {
    if ($buf[0] === "\0") { break; }    // padding.
    $header = unpack('a3id/C3length', substr($buf, 0, $hl = 3+3));
    $len = ($header['length1'] << 16) + ($header['length2'] << 8) + $header['length3'];
    //$len = unsynchInt($len);
    if (strlen($buf) < $hl + $len) {
      throw new Exception("Not enough frame buffer.");
    }
    $frames[] = [
      'id' => $header['id'],
      'flags' => null,
      'data' => substr($buf, $hl, $len),
    ];
    $buf = substr($buf, $hl + $len);
  }

  return $frames;
}

// 3.x and 4.x.
function splitID3v2Frames($buf) {
  $frames = [];

  while (strlen($buf)) {
    if ($buf[0] === "\0") { break; }    // padding.
    $header = unpack('a4id/Nlength/nflags', substr($buf, 0, $hl = 4+4+2));
    // Contrary to the spec, frame size doesn't seem to be synchsafe integer,
    // at least in tags produced by iTunes.
    //$len = unsynchInt($header['length']);
    $len = $header['length'];
    if (strlen($buf) < $hl + $len) {
      throw new Exception("Not enough frame buffer.");
    }
    $frames[] = [
      'id' => $header['id'],
      'flags' => $header['flags'],
      'data' => substr($buf, $hl, $len),
    ];
    $buf = substr($buf, $hl + $len);
  }

  return $frames;
}

function unsynchInt($n) {
  $octets = str_split(str_pad(decbin($n), 32, 0, STR_PAD_LEFT), 8);

  foreach ($octets as &$octet) {
    if ($octet[0]) {
      throw new Exception("Synchsafe integer ($n) expected to have no 8th bits set ($octet).");
    }
    $octet = substr($octet, 1);
  }

  return bindec(join($octets));
}

function decodeStrings($s) {
  $res = [];

  while (strlen($s)) {
    list($string, $tail) = decodeString($s);
    $res[] = $string;
    $s = strlen($tail) ? $s[0].$tail : '';
  }

  return $res;
}

function decodeString($s) {
  switch ($s[0]) {
    case "\0":
    case "\3":
      $tail = strpos($s, "\0", 1);
      $tail === false and $tail = strlen($s);
      $string = substr($s, 1, $tail - 1);
      $s[0] === "\0" and $string = utf8_encode($string);
      return [$string, substr($s, $tail + 1)];
    case "\1":
    case "\2":
      // It seems NULL byte(s) (for all string encoding types) are optional and only used if the data does
      // contain several strings, as a separator. For example, found in COMM only.
      // However, instead of using explode() we still parse strings one by one
      // because there must be some cases when NULLs are used as terminator,
      // and then this function would be useful.
      $tail = strlen($s);
      for ($i = 1; isset($s[$i + 1]); $i += 2) {
        if ($s[$i] === "\0" and $s[$i + 1] === "\0") {
          $tail = $i;
          break;
        }
      }
      $enc = $s[0] === "\1" ? 'UTF-16' : 'UTF-16BE';
      return [iconv($enc, 'utf-8', substr($s, 1, $tail - 1)), substr($s, $tail + 2)];
    default:
      throw new Exception("Unknown string encoding scheme ($s[0]).");
  }
}

function assertNoFlags($n, $type) {
  if ($n) {
    throw new Exception("ID3v2 $type flags not supported ($n).");
  }
}

class NoID3Tag extends Exception { }
