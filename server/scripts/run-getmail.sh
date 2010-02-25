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
if [ -f /tmp/.getmail_lock ]; then
  echo 'Found getmail lock file /tmp/.getmail_lock, we quit here.'
else
  touch /tmp/.getmail_lock
  if [ "$rcfiles" != "" ]; then
    exec /usr/bin/getmail -n -v -g /etc/getmail $rcfiles
  fi
  rm -f /tmp/.getmail_lock
fi