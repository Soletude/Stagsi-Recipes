-- `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
-- Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/LJ`@

local temp = os.getenv('TEMP')..'\\stagsi-fceux'
local last = 0

while true do
  -- `! +REPLACEME=Z|1
  if input.get().Z and os.difftime(os.time(), last) >= 1 then
    gui.savescreenshotas(temp)
    emu.frameadvance()
    -- `! +REPLACEME=".*"
    os.execute('"C:\\Program Files\\Stagsi\\Stagsi.exe" /import '..temp..'')
    os.remove(temp)
    last = os.time()
  else
    emu.frameadvance()
  end
end
