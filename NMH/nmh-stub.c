// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/MA`@

#define _CRT_SECURE_NO_WARNINGS
#include <stdio.h>
#include <stdlib.h>
#include <windows.h>

int main(void) {
  FILE *debug = fopen("nmh.log", "w");    // `! +REPLACEME="..+"
  fprintf(debug, "CL: %s\n", GetCommandLine());
  setbuf(debug, NULL);
  setbuf(stdin, NULL);
  setbuf(stdout, NULL);
  while (1) {
    DWORD len;
    if (fread(&len, 1, 4, stdin) != 4) { return 1; }
    fprintf(debug, "in: %u\n", len);
    char *buf = (char*) malloc(len + 1);
    if (fread(buf, 1, len, stdin) != len) { return 2; }
    buf[len] = 0;
    fprintf(debug, "  %s\n", buf);
    fwrite(&len, 1, 4, stdout);
    fwrite(buf, 1, len, stdout);
  }
}
