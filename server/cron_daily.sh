#!/bin/sh

PATH=/sbin:/usr/sbin:/bin:/usr/bin:/usr/local/sbin:/usr/local/bin:/usr/X11R6/bin

/usr/bin/php -q /usr/local/ispconfig/server/cron_daily.php
