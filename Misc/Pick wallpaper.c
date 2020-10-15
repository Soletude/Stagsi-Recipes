// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/PO`@

#include <stdio.h>
#include <windows.h>

int main(int argc, char** argv) {
  // Attention: CreateProcessW (even if it's not used in this example)
  // requires lpCommandLine to be writeable.
  char cl[MAX_PATH];
  snprintf(cl, MAX_PATH,
    // `! +REPLACEME=(?<="\\")%s
    "\"%s\" /pick /search \"%s\" /force \"%s\" /count 1 1",
    // `! +REPLACEME=".*"
    "C:\\Program Files\\Stagsi\\Stagsi.exe",
    "Wallpapers",
    "(PNG | JPG | GIF | BMP)");

  STARTUPINFO si = {0};
  PROCESS_INFORMATION pi = {0};
  BOOL processOK = CreateProcess(NULL, cl, NULL, NULL, FALSE, 0, NULL, NULL, &si, &pi);
  WaitForSingleObject(pi.hProcess, INFINITE);

  DWORD code;
  if (!GetExitCodeProcess(pi.hProcess, &code)) {
    code = (DWORD) -1;
  }

  if (code == 0) {
    // Successfully picked an image.
    OpenClipboard(NULL);

    HANDLE drop = GetClipboardData(CF_HDROP);
    wchar_t fn[MAX_PATH];

    if (drop != NULL && DragQueryFileW(drop, 0, fn, MAX_PATH - 1)) {
      BOOL wallpaperOK = SystemParametersInfoW(SPI_SETDESKWALLPAPER, 0, fn,
        SPIF_UPDATEINIFILE | SPIF_SENDCHANGE);
      if (wallpaperOK) {
        CloseClipboard();
        return 0;
      }
    }

    CloseClipboard();
  }

  return 1;
}
