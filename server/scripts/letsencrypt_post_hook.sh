#!/bin/bash

### BEGIN INIT INFO
# Provides: LETSENCRYPT POST HOOK SCRIPT
# Required-Start:  $local_fs $network
# Required-Stop:  $local_fs
# Default-Start:  2 3 4 5
# Default-Stop:  0 1 6
# Short-Description: LETSENCRYPT POST HOOK SCRIPT
# Description:  To force close http port 80 if it is by default closed, to be used by letsencrypt client standlone command
### END INIT INFO

## If you need a custom hook file, create a file with the same name in
## /usr/local/ispconfig/server/conf-custom/scripts/
if [ -e "/usr/local/ispconfig/server/conf-custom/scripts/letsencrypt_post_hook.sh" ] ; then
	. /usr/local/ispconfig/server/conf-custom/scripts/letsencrypt_post_hook.sh && exit 0 || exit 1;
fi

# You can add support to other firewall

# For RHEL, Centos or derivatives
if which yum &> /dev/null 2>&1 ; then
    # Check if web server software is installed, start it if any
    if [ rpm -q nginx ]; then service nginx start
    elif [ rpm -q httpd ]; then service httpd start
    # If using firewalld
    elif [ rpm -q firewalld ] && [ `firewall-cmd --state` = running ]; then
        firewall-cmd --zone=public --permanent --remove-service=http
        firewall-cmd --reload
    # If using UFW
    else; if [ rpm -q ufw ]; then ufw --force enable && ufw deny http; fi
    fi
# For Debian, Ubuntu or derivatives
elif apt-get -v >/dev/null 2>&1 ; then
    # Check if web server software is installed, stop it if any
    if [ $(dpkg-query -W -f='${Status}' nginx 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service nginx start
    elif [ $(dpkg-query -W -f='${Status}' apache2 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service apache2 start
    # If using UFW
    else; if [ $(dpkg-query -W -f='${Status}' ufw 2>/dev/null | grep -c "ok installed") -eq 1 ]; then ufw --force enable && ufw deny http; fi
    fi
# Try iptables as a final attempt
else
    iptables -D INPUT  -p tcp  --dport 80    -j ACCEPT
    service iptables save
fi