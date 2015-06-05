#!/bin/bash
PATH=/sbin:/usr/sbin:/bin:/usr/bin:/usr/local/sbin:/usr/local/bin:/usr/X11R6/bin
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
    /usr/bin/getmail -v -g /etc/getmail $rcfiles || true
  fi
  rm -f /tmp/.getmail_lock
fi
