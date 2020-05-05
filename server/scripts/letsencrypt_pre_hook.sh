#!/bin/bash

### BEGIN INIT INFO
# Provides: LETSENCRYPT PRE HOOK SCRIPT
# Required-Start:  $local_fs $network
# Required-Stop:  $local_fs
# Default-Start:  2 3 4 5
# Default-Stop:  0 1 6
# Short-Description: LETSENCRYPT PRE HOOK SCRIPT
# Description:  To force open http port 80 to be used by letsencrypt client standlone command
### END INIT INFO

# You can add support to other firewall

# For RHEL, Centos or derivatives
if which yum &> /dev/null 2>&1 ; then
    # If using firewalld
    if [ rpm -q firewalld ] && [ `firewall-cmd --state` = running ]; then
        firewall-cmd --zone=public --permanent --add-service=http
        firewall-cmd --reload
    # If using UFW
    elif rpm -q ufw; then
        ufw --force enable && ufw allow http
    else
    fi
# For Debian, Ubuntu or derivatives
elif apt-get -v >/dev/null 2>&1 ; then
    # If using UFW
    if [ $(dpkg-query -W -f='${Status}' ufw 2>/dev/null | grep -c "ok installed") -eq 1 ]; then
        ufw --force enable && ufw allow http
    fi
# Try iptables as a final attempt
else
    iptables -I INPUT  -p tcp  --dport 80    -j ACCEPT
    service iptables save
fi
