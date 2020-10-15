// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/EO`@
// Compile with: cl /TP this.cpp_or_.c /link gdiplus.lib shell32.lib

#include <windows.h>
#include <strsafe.h>
#include <Gdiplus.h>

bool has_thumb(wchar_t *data_path, wchar_t *dir, wchar_t *prefix, long rowid) {
    wchar_t mask[MAX_PATH];
    StringCchPrintfW(mask, MAX_PATH, L"%s\\%s\\%s%ld.*", data_path, dir, prefix, rowid);
    WIN32_FIND_DATAW find = {0};
    HANDLE handle = FindFirstFileW(mask, &find);
    if (handle == INVALID_HANDLE_VALUE) {
        return false;
    }
    FindClose(handle);
    return true;
}

// Procedural-style GDI+. Close to black magic.
// https://forum.antichat.ru/threads/268293/
bool hicon_to_png(wchar_t *dest, HICON icon) {
    // http://www.masm32.com/board/index.php?topic=5782.0
    // Type: image/bmp,   GUID: {557CF400-1A04-11D3-9A73-0000F81EF32E}
    // Type: image/jpeg,  GUID: {557CF401-1A04-11D3-9A73-0000F81EF32E}
    // Type: image/gif,   GUID: {557CF402-1A04-11D3-9A73-0000F81EF32E}
    // Type: image/tiff,  GUID: {557CF405-1A04-11D3-9A73-0000F81EF32E}
    // Type: image/png,   GUID: {557CF406-1A04-11D3-9A73-0000F81EF32E}
    GUID png_format = {0x557CF406, 0x1A04, 0x11D3, 0x9A, 0x73, 0x00, 0x00, 0xF8, 0x1E, 0xF3, 0x2E};
    Gdiplus::GdiplusStartupInput si = {0};
    si.GdiplusVersion = 1;
    ULONG_PTR token;
    int res = Gdiplus::GdiplusStartup(&token, &si, NULL);
    if (!res) {
        Gdiplus::GpBitmap *bmp;
        res = Gdiplus::DllExports::GdipCreateBitmapFromHICON(icon, &bmp);
        if (!res) {
            res = Gdiplus::DllExports::GdipSaveImageToFile(bmp, dest, &png_format, NULL);
            Gdiplus::DllExports::GdipDisposeImage(bmp);
        }
        Gdiplus::GdiplusShutdown(token);
    }
    return !res;
}

int wmain(int argc, wchar_t** argv) {
    wchar_t *data_path = argv[1];

    wchar_t dir_mask[MAX_PATH];
    HRESULT o = StringCchPrintfW(dir_mask, MAX_PATH, L"%s\\*", data_path);
    WIN32_FIND_DATAW dir = {0};
    HANDLE dir_handle = FindFirstFileW(dir_mask, &dir);

    if (dir_handle == INVALID_HANDLE_VALUE) {
        return 1;
    }

    do {
        wchar_t *end = dir.cFileName;
        while (*end && *end >= L'0' && *end <= L'9') { end++; }
        if (*end) { continue; }

        wchar_t file_mask[MAX_PATH];
        StringCchPrintfW(file_mask, MAX_PATH, L"%s\\%s\\*", data_path, dir.cFileName);
        WIN32_FIND_DATAW file = {0};
        HANDLE file_handle = FindFirstFileW(file_mask, &file);

        if (file_handle == INVALID_HANDLE_VALUE) {
            continue;
        }

        do {
            // strtol()/wcstol() allow leading whitespace and sign.
            if (*file.cFileName < L'0' || *file.cFileName > L'9') {
                continue;
            }

            wchar_t *end;
            long rowid = wcstol(file.cFileName, &end, 10);
            wchar_t data_file[MAX_PATH];
            StringCchPrintfW(data_file, MAX_PATH, L"%s\\%s\\%s", data_path, dir.cFileName, file.cFileName);
            if (*end == L'.') {
                // Not a link file.
            } else if (!*end) {
                HANDLE link_handle = CreateFileW(data_file, GENERIC_READ, 0, NULL, OPEN_EXISTING, 0, NULL);
                DWORD link_read;
                char buf[MAX_PATH];
                // MultiByteToWideChar() won't write '\0'.
                memset(data_file, 0, sizeof(data_file));
                if (link_handle == INVALID_HANDLE_VALUE ||
                    !ReadFile(link_handle, buf, sizeof(buf) - 1, &link_read, NULL) ||
                    !link_read ||
                    !MultiByteToWideChar(CP_UTF8, 0, buf, link_read, data_file, MAX_PATH)) {
                    continue;
                }
                CloseHandle(link_handle);
            } else {
                continue;
            }

            if (has_thumb(data_path, dir.cFileName, L"t", rowid) ||
                has_thumb(data_path, dir.cFileName, L"u", rowid)) {
                continue;
            }

            SHFILEINFOW info = {0};
            SHGetFileInfoW(data_file, 0, &info, sizeof(info), SHGFI_ICON | SHGFI_LARGEICON);

            wchar_t thumb_file[MAX_PATH];
            StringCchPrintfW(thumb_file, MAX_PATH, L"%s\\%s\\u%ld.png", data_path, dir.cFileName, rowid);

            hicon_to_png(thumb_file, info.hIcon);
        } while (FindNextFileW(file_handle, &file));

        FindClose(file_handle);
    } while (FindNextFileW(dir_handle, &dir));

    FindClose(dir_handle);
}
