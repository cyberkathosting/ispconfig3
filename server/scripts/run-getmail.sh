#!/bin/sh
set -e
cd /etc/getmail
rcfiles=""
for file in *.conf ; do
rcfiles="$rcfiles -r $file"
done
#echo $rcfiles
exec /usr/bin/getmail -n -v -g /etc/getmail $rcfiles