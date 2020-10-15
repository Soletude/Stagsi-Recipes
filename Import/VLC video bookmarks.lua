-- `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
-- Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/QL`@

-- To enable this extension, copy it to VLC's program folder, for example:
-- C:\Program Files (x86)\VideoLAN\lua\extensions\
-- ...then (every time VLC starts) enable via the View menu.
--
-- Warning: io.open() doesn't work with UTF-8 but with system-specific locale.
-- Yet VLC returns playing file path as UTF-8 and has no means to convert it
-- to CP1251 (or other) but even if it had the conversion would not be correct
-- for all strings. Therefore paths with non-Latin symbols will be mangled on
-- save and all modes will behave as OVERWRITE (this includes video file names
-- and ..._path options). This doesn't affect Stagsi import much.

local MY_NAME     = 'Stagsi Playlist Maker'

local NONE        = 0
local ADD_NEW     = 1
local APPEND      = 2
local OVERWRITE   = 3

-- After changing options either restart VLC or go to Tools >
-- Plugins and extensions, Active Extensions tab and hit Reload extensions,
-- then re-enable it via the View menu.
local options = {
  -- Leave empty to use the video's directory. In any case, the file is prefixed
  -- by the video's base name (without extension) plus a unique numerical suffix.
  playlist_path   = '',   -- `! +REPLACEME='.*'
  -- Warning: make sure to double backslashes!
  --playlist_path   = 'C:\\Stuff\\Save_here',

  -- Like above but for video screenshots.
  snapshot_path   = '',   -- `! +REPLACEME='.*'

  -- NONE doesn't write playlist files.
  --
  -- ADD_NEW creates new M3U file.
  --
  -- APPEND adds new lines to the first (existing) M3U file (if no file exists,
  -- it's created as with ADD_NEW). This allows multiple bookmarks per one
  -- playlist, which can be viewed and navigated in VLC's View > Playlist
  -- (Ctrl+L) menu.
  --
  -- OVERWRITE wipes the file if it exists, replacing by the current (last)
  -- position.
  playlist_mode   = ADD_NEW,    -- `! +REPLACEME=A\w+?

  -- Similar as above but not APPEND (works like ADD_NEW).
  snapshot_mode   = OVERWRITE,  -- `! +REPLACEME=O\w+?

  -- Allowed VLC values - png, jpg, tiff.
  snapshot_format = 'png',      -- `! +REPLACEME='.*'
}

function descriptor()
  return {
    title = MY_NAME,
    version = '1.0',
    license = 'CC0',
    author = 'Soletude',
    description = [[
Whenever a video is paused (often with Space hotkey), saves an M3U playlist (with current playback position) and/or current video snapshot for later import into Stagsi.

Open this script in a text editor to configure.
    ]],
    url = 'https://stagsi.com',
    capabilities = {'playing-listener', 'input-listener'},
  }
end

-- Without these two VLC won't recognize the extension.
function activate()
end

function deactivate()
end

-- From lua\modules\common.lua.
local function snapshot()
  local vout = vlc.object.vout()
  if vout then
    -- Saves image to the location and in format specified in VLC preferences.
    vlc.var.set(vout, 'video-snapshot', nil)
  end
end

-- C:\foo\bar.txt -> C:\foo\
local function split_path(path)
  return path:gsub('[^\\/]+$', ''), path:match('[^\\/]+$')
end

-- path - without format (extension). A prefix will be added by vlc.
-- format - without '.'.
local function snapshot_to(path, format)
  local old = {
    path = vlc.config.get('snapshot-path'),
    prefix = vlc.config.get('snapshot-prefix'),
    format = vlc.config.get('snapshot-format'),
    seq = vlc.config.get('snapshot-sequential'),
  }
  local dir, file = split_path(path)
  -- VLC fails if a path has trailing \, but it also fails if it's a drive's
  -- root without \. So either C:\... or C:\ but not C:\...\ or C:.
  dir = dir:gsub('[\\/]*$', '')
  if not dir:find('[\\/]') then dir = dir..'\\' end
  vlc.config.set('snapshot-path', dir)
  vlc.config.set('snapshot-prefix', file)
  vlc.config.set('snapshot-format', format)
  -- vlcsnap-00001.png.
  vlc.config.set('snapshot-sequential', true)
  pcall(snapshot)
  vlc.config.set('snapshot-path', old.path or '')
  vlc.config.set('snapshot-prefix', old.prefix or '')
  vlc.config.set('snapshot-format', old.format or '')
  vlc.config.set('snapshot-sequential', old.seq or false)
end

local function last_file(base, suffix)
  local function exists(file)
    local f = io.open(file)
    return f and (f:close() or true)
  end
  -- VLC's snapshot suffixes are incremental and don't reset to 1 for new
  -- videos. So we see what's the last suffix that exists.
  for i = 1000, 1, -1 do
    local file = ('%s%05d%s'):format(base, i, suffix)
    vlc.msg.dbg(('%s: last_file(%s)'):format(MY_NAME, file))
    if exists(file) then
      return i
    end
  end
end

local function handle_hotkey()
  vlc.msg.info(('%s: handling hotkey'):format(MY_NAME))

  -- file:///C:/%20...
  local video_uri = vlc.input.item():uri()
  local video_file = vlc.strings.decode_uri(video_uri)
    :gsub('^[^/]*/*', '')
    :gsub('/', '\\')

  local video_dir, video_file = split_path(video_file)
  local video_base = video_file:gsub('%.[^.]+$', '')

  vlc.msg.dbg(('%s: video dir %s, file %s, base %s'):format(MY_NAME,
    video_dir, video_file, video_base))

  if options.playlist_mode ~= NONE then
    local base = options.playlist_path:gsub('[\\/]*$', '')
    base = (base == '' and video_dir or base..'\\')..video_base..'-'
    local suffix = last_file(base, '.m3u')
    vlc.msg.dbg(('%s: playlist base %s, suffix %s'):format(MY_NAME, base, suffix or ''))
    if options.playlist_mode == OVERWRITE then
      if suffix then
        os.remove(('%s%05d.m3u'):format(base, suffix))
      end
    elseif options.playlist_mode == ADD_NEW and suffix then
      suffix = suffix + 1
    end
    -- Convert microseconds to seconds.
    local play_time = vlc.var.get(vlc.object.input(), 'time') / 1000000
    local file = ('%s%05d.m3u'):format(base, suffix or 1)
    local block = '#EXTVLCOPT:start-time=%d\n'..
                  '%s\n'..
                  '\n'
    block = block:format(play_time, video_uri)
    local f = io.open(file, options.playlist_mode == APPEND and 'a' or 'w')
    f:write(block)
    f:close()
  end

  if options.snapshot_mode ~= NONE then
    local base = options.snapshot_path:gsub('[\\/]*$', '')
    base = (base == '' and video_dir or base..'\\')..video_base..'-'
    local suffix = last_file(base, '.'..options.snapshot_format)
    vlc.msg.dbg(('%s: snapshot base %s, suffix %s'):format(MY_NAME, base, suffix or ''))
    if options.snapshot_mode == OVERWRITE then
      if suffix then
        os.remove(('%s%05d.%s'):format(base, suffix, options.snapshot_format))
      end
    elseif suffix then
      suffix = suffix + 1
    end
    snapshot_to(base, options.snapshot_format)
  end
end

function playing_changed()
  if vlc.playlist.status() == 'paused' then
    local ok, msg = pcall(handle_hotkey)
    if not ok then
      vlc.msg.err(('%s: error: %s'):format(MY_NAME, msg))
    end
  end
end
