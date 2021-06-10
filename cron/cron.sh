export AUTOBACKUP_ROOTDIR=`dirname "$0"`
export USE_PHP=php

cd $AUTOBACKUP_ROOTDIR

# TO RUN AUTOBACKUP CORN
$USE_PHP -f cron.php 
