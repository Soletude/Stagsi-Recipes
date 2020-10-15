// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/RA`@

var file = require('fs').mkdtempSync(os.tmpdir() + '/') + '/clip.txt'
require('child_process').execSync('nircmdc64 clipboard writefile "' + file + '"')
var clipboard = require('fs').readFileSync(file, {encoding: 'utf8'})
var [, data] = clipboard.match(/^Stagsi JSON \d+ (\{.+)$/s)
var sum = JSON.parse(data).Objects.reduce((cur, obj) => cur + obj.FileSize, 0)
console.log(`Total size of all objects: ${sum} bytes`)
