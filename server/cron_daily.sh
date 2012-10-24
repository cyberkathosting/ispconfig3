#!/bin/sh

PATH=/sbin:/usr/sbin:/bin:/usr/bin:/usr/local/sbin:/usr/local/bin:/usr/X11R6/bin

if [ -f /usr/local/ispconfig/server/lib/php.ini ]; then
        PHPINIOWNER=`stat -c %U /usr/local/ispconfig/server/lib/php.ini`
        if [ $PHPINIOWNER == 'root' ] || [ $PHPINIOWNER == 'ispconfig'  ]; then
                export PHPRC=/usr/local/ispconfig/server/lib
        fi
fi

/usr/bin/php -q /usr/local/ispconfig/server/cron_daily.php

if [ -f /usr/local/ispconfig/interface/web/billing/cron/create_recurring_invoices_cron.php ]; then
        /usr/bin/php -q /usr/local/ispconfig/interface/web/billing/cron/create_recurring_invoices_cron.php
fi
