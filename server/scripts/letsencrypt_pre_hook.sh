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

## If you need a custom hook file, create a file with the same name in
## /usr/local/ispconfig/server/conf-custom/scripts/
##
## End the file with 'return 124' to signal that this script should not terminate.
##
## Eg. you can override the ispc_letsencrypt_firewall_enable() function then 'return 124'
## to customize the firewall setup.
if [ -e "/usr/local/ispconfig/server/conf-custom/scripts/letsencrypt_pre_hook.sh" ] ; then
	. /usr/local/ispconfig/server/conf-custom/scripts/letsencrypt_pre_hook.sh
	ret=$?
	if [ $ret != 124 ]; then exit $ret; fi
fi

declare -F ispc_letsencrypt_firewall_enable &>/dev/null || ispc_letsencrypt_firewall_enable() {
	# create 'ispc-letsencrypt' chain with ACCEPT policy and send port 80 there
	iptables -N ispc-letsencrypt
	iptables -I ispc-letsencrypt -p tcp --dport 80 -j ACCEPT
	iptables -A ispc-letsencrypt -j RETURN
	iptables -I INPUT -p tcp --dport 80 -j ispc-letsencrypt
}

ispc_letsencrypt_firewall_enable

# For RHEL, Centos or derivatives
if which yum &> /dev/null 2>&1 ; then
    # Check if web server software is installed, stop it if any
    if [ rpm -q nginx ]; then service nginx stop; fi
    if [ rpm -q httpd ]; then service httpd stop; fi
#    # If using firewalld
#    if [ rpm -q firewalld ] && [ `firewall-cmd --state` = running ]; then
#        firewall-cmd --zone=public --permanent --add-service=http
#        firewall-cmd --reload
#    fi
#    # If using UFW
#    if [ rpm -q ufw ]; then ufw --force enable && ufw allow http; fi

# For Debian, Ubuntu or derivatives
elif apt-get -v >/dev/null 2>&1 ; then
    # Check if web server software is installed, stop it if any
    if [ $(dpkg-query -W -f='${Status}' nginx 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service nginx stop; fi
    if [ $(dpkg-query -W -f='${Status}' apache2 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service apache2 stop; fi
#    # If using UFW
#    if [ $(dpkg-query -W -f='${Status}' ufw 2>/dev/null | grep -c "ok installed") -eq 1 ]; then ufw --force enable && ufw allow http; fi
    
## Try iptables as a final attempt
#else
#    iptables -I INPUT  -p tcp  --dport 80    -j ACCEPT
#    service iptables save
fi
