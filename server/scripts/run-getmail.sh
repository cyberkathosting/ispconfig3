#!/bin/sh
set -e
cd /etc/getmail
rcfiles=""
for file in *.conf ; do
if [ $file != "*.conf" ]; then
rcfiles="$rcfiles -r $file"
fi
done
#echo $rcfiles
if [ "$rcfiles" != "" ]; then
exec /usr/bin/getmail -n -v -g /etc/getmail $rcfiles
fi