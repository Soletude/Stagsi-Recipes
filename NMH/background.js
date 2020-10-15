// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/FH`@

function exec(args) {
  chrome.runtime.sendNativeMessage(
    'ca.soletude.stagsi.my-addon',    // `! +REPLACEME=my-\w+?
    {do: "spawn", args},
    function (resp) {
      if (chrome.runtime.lastError || !resp || resp.error) {
        alert('Unable to contact Stagsi: ' + chrome.runtime.lastError)
      }
    }
  )
}

chrome.browserAction.onClicked.addListener(function () {
  chrome.tabs.query(
    {
      active: true,
      windowId: chrome.windows.WINDOW_ID_CURRENT
    },
    function (tab) {
      chrome.tabs.sendMessage(tab[0].id, {}, function (resp) {
        var args = resp ? ['/search', resp] : []
        exec({args})
      })
    }
  )
})

chrome.omnibox.onInputEntered.addListener(function (text) {
  exec({args: ['/search', text]})
})
