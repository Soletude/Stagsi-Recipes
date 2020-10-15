// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/RD`@

chrome.extension.onMessage.addListener(function (req, sender, send) {
  send(window.getSelection().toString())
})
