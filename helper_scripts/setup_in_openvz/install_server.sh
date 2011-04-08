#!/bin/bash
# Script to configuring an ispconfig3 server in a Debian VPS
# by calocen [at] gmail [dot] com

# getting some enviromment values
myhostname=`hostname -f`
mydomain=`hostname -d`
myip=`hostname -i`
[ ! -x /usr/bin/geoiplookup ] && apt-get --assume-yes install geoip-bin
mycountry=`geoiplookup $myip | cut -f4 -d" " | cut -f1 -d","`
myprovince=`geoiplookup $myip | cut -f5 -d" "`

# reconfiguring webalizer, postfix
# could be cool to modify here webalizer values
dpkg-reconfigure -u webalizer
postconf -e "myhostname =  $myhostname"
postconf -e "mydestination =  $myhostname, localhost"
echo $myhostname > /etc/mailname
dpkg-reconfigure -u postfix

# request new password
oldpwd=`grep password /root/.my.cnf | tr "\t" " " | tr -s " " | cut -f3 -d" "`
read -p "mysql password: [$oldpwd] " mysqlpwd
[ -z $mysqlpwd ] && mysqlpwd=$oldpwd
echo $mysqlpwd
#read -p "Are you sure? (y/n) " sure
## who said fear ##
set -x
mysqladmin -u root -p$oldpwd password $mysqlpwd
mysqladmin -u root -p$mysqlpwd -h localhost password $mysqlpwd
cat << EOF > /root/.my.cnf
[client]
password	= $mysqlpwd
EOF
chmod 600 /root/.my.cnf

# changing mydns password
mysql -e "SET PASSWORD FOR 'mydns'@'%' = PASSWORD( '$mysqlpwd' )"
mysql -e "SET PASSWORD FOR 'mydns'@'localhost' = PASSWORD( '$mysqlpwd' )"
cp -ax /etc/mydns.conf /etc/mydns.conf~
sed s/$oldpwd/$mysqlpwd/g < /etc/mydns.conf~ > /etc/mydns.conf

# enabling mydns
mydns --create-tables > /tmp/mydns.sql
mysql -e "CREATE DATABASE IF NOT EXISTS mydns ; USE mydns ; SOURCE /tmp/mydns.sql;"
rm /tmp/mydns.*
invoke-rc.d mydns restart

# preparing server installation
mv /etc/ssl/openssl.cnf /etc/ssl/openssl.cnf~
sed s/"YOURHOSTNAME"/"$myhostname"/g < /usr/local/bin/openssl.cnf |
sed s/"YOURDOMAIN"/"$mydomain"/g | \
sed s/"YOURCOUNTRY"/"$mycountry"/g | \
sed s/"YOURPROVINCE"/"$myprovince"/g > /etc/ssl/openssl.cnf

tar xfz /root/downloads/ISPConfig-3.0.0.7-beta.tar.gz -C /usr/local/src
# here would be some stuff to update from SVN
cd /usr/local/src/ispconfig3_install/install/
php -q install.php


