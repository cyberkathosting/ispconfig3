#!/bin/bash
PATH=/sbin:/usr/sbin:/bin:/usr/bin:/usr/local/sbin:/usr/local/bin:/usr/X11R6/bin
FILES=/var/log/ispconfig/httpd/*
for f in $FILES
do
  mv $f/access.log $f/`date --date='yesterday' "+%Y%m%d"`-access.log &> /dev/null
  touch $f/access.log &> /dev/null
done
pkill -USR1 -u root nginx &> /dev/null