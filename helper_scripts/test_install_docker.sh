#!/bin/sh

# This script is used from .gitlab-ci.yml to do an automated installation inside a docker container for testing.

if [ -f /usr/local/ispconfig/interface/lib/config.inc.php ]; then
	echo "Found an existing configfile, bailing out!"
  exit 1
fi

mysql_install_db
service mysql start \
&& echo "UPDATE mysql.user SET Password = PASSWORD('pass') WHERE User = 'root';" | mysql -u root \
&& echo "UPDATE mysql.user SET plugin='mysql_native_password' where user='root';" | mysql -u root \
&& echo "DELETE FROM mysql.user WHERE User='';" | mysql -u root \
&& echo "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" | mysql -u root \
&& echo "DROP DATABASE IF EXISTS test;" | mysql -u root \
&& echo "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';" | mysql -u root \
&& echo "FLUSH PRIVILEGES;" | mysql -u root
sed -i "s/^hostname=server1.example.com$/hostname=$HOSTNAME/g" /root/ispconfig3_install/install/autoinstall.ini

service mysql start && php -q $CI_PROJECT_DIR/install/install.php --autoinstall=/root/ispconfig3_install/install/autoinstall.ini
