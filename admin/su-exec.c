// su-exec: setuid root wrapper for web admin commands
// Compile: gcc -o /usr/local/bin/su-exec su-exec.c
// Install: chown root:www-data /usr/local/bin/su-exec && chmod 4510 /usr/local/bin/su-exec
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
int main(int argc, char *argv[]) {
    setuid(0); setgid(0);
    if(argc < 2) return 1;
    char cmd[4096] = {0};
    for(int i=1; i<argc; i++) {
        strcat(cmd, argv[i]);
        if(i < argc-1) strcat(cmd, " ");
    }
    return system(cmd) >> 8;
}
