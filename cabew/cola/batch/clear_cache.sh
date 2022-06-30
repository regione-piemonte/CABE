#!/bin/sh

PATH_LOG=/data/www/html/cola/app/log
PATH_CAUTILS=/data/www/html/cola/support/bin

$PATH_CAUTILS/caUtils clear-caches

chmod -R 777 /data/www/html/cola/app/tmp/

exit
