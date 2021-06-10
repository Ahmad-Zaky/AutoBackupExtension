set AUTOBACKUP_ROOTDIR="path\to\autbackup\cron\"
set PHP_EXE="path\to\php\php.exe"

cd /D %AUTOBACKUP_ROOTDIR%

%PHP_EXE% -f cron.php 