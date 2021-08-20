#!/bin/bash

### BEGIN INIT INFO
# Provides: LETSENCRYPT RENEW HOOK SCRIPT
# Required-Start:  $local_fs $network
# Required-Stop:  $local_fs
# Default-Start:  2 3 4 5
# Default-Stop:  0 1 6
# Short-Description: LETSENCRYPT RENEW HOOK SCRIPT
# Description:  Taken from LE4ISPC code. To be used to update ispserver.pem automatically after ISPConfig LE SSL certs are renewed and to reload / restart important ISPConfig server services
### END INIT INFO

## If you need a custom hook file, create a file with the same name in
## /usr/local/ispconfig/server/conf-custom/scripts/
##
## End the file with 'return 124' to signal that this script should not terminate.
if [ -e "/usr/local/ispconfig/server/conf-custom/scripts/letsencrypt_renew_hook.sh" ] ; then
        . /usr/local/ispconfig/server/conf-custom/scripts/letsencrypt_renew_hook.sh
        ret=$?
        if [ $ret != 124 ]; then exit $ret; fi
fi

hostname=$(hostname -f)
if [ -d "/usr/local/ispconfig/server/scripts/${hostname}" ] ; then
	lelive="/usr/local/ispconfig/server/scripts/${hostname}" ;
elif [ -d "/root/.acme.sh/${hostname}" ] ; then
	lelive="/root/.acme.sh/${hostname}" ;
else
	lelive="/etc/letsencrypt/live/${hostname}" ;
fi

if [ -d "$lelive" ]; then
    cd /usr/local/ispconfig/interface/ssl; ibak=ispserver.*.bak; ipem=ispserver.pem; icrt=ispserver.crt; ikey=ispserver.key
    if ls $ibak 1> /dev/null 2>&1; then rm $ibak; fi
    if [ -e "$ipem" ]; then mv $ipem $ipem-$(date +"%y%m%d%H%M%S").bak; cat $ikey $icrt > $ipem; chmod 600 $ipem; fi
    pureftpdpem=/etc/ssl/private/pure-ftpd.pem; if [ -e "$pureftpdpem" ]; then chmod 600 $pureftpdpem; fi
    # For Red Hat, Centos or derivatives
    if which yum &> /dev/null 2>&1 ; then
        if [ rpm -q pure-ftpd ]; then service pure-ftpd restart; fi
        if [ rpm -q monit ]; then service monit restart; fi
        if [ rpm -q postfix ]; then service postfix restart; fi
        if [ rpm -q dovecot ]; then service dovecot restart; fi
        if [ rpm -q mysql-server ]; then service mysqld restart; fi
        if [ rpm -q mariadb-server ]; then service mariadb restart; fi
        if [ rpm -q MariaDB-server ]; then service mysql restart; fi
        if [ rpm -q nginx ]; then service nginx restart; fi
        if [ rpm -q httpd ]; then service httpd restart; fi
    # For Debian, Ubuntu or derivatives
    elif apt-get -v >/dev/null 2>&1 ; then
        if [ $(dpkg-query -W -f='${Status}' pure-ftpd-mysql 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service pure-ftpd-mysql restart; fi
        if [ $(dpkg-query -W -f='${Status}' monit 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service monit restart; fi
        if [ $(dpkg-query -W -f='${Status}' postfix 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service postfix restart; fi
        if [ $(dpkg-query -W -f='${Status}' dovecot-imapd 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service dovecot restart; fi
        if [ $(dpkg-query -W -f='${Status}' mysql 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service mysql restart; fi
        if [ $(dpkg-query -W -f='${Status}' mariadb 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service mysql restart; fi
        if [ $(dpkg-query -W -f='${Status}' nginx 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service nginx restart; fi
        if [ $(dpkg-query -W -f='${Status}' apache2 2>/dev/null | grep -c "ok installed") -eq 1 ]; then service apache2 restart; fi
    fi
else echo `/bin/date` "Your Lets Encrypt SSL certs path for your ISPConfig server FQDN is missing.$line" >> /var/log/ispconfig/ispconfig.log; fi
