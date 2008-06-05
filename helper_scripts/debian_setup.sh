#!/bin/sh

apt-get install postfix postfix-mysql postfix-doc mysql-client mysql-server courier-authdaemon courier-authlib-mysql courier-pop courier-pop-ssl courier-imap courier-imap-ssl postfix-tls libsasl2-2 libsasl2-modules libsasl2-modules-sql sasl2-bin libpam-mysql openssl courier-maildrop getmail4

apt-get install amavisd-new spamassassin clamav clamav-daemon zoo unzip bzip2 arj nomarch lzop cabextract apt-listchanges libnet-ldap-perl libauthen-sasl-perl clamav-docs daemon libio-string-perl libio-socket-ssl-perl libnet-ident-perl zip libnet-dns-perl

modprobe capability
echo 'capability' >> /etc/modules

apt-get install pure-ftpd-common pure-ftpd-mysql quota quotatool

echo 'yes' > /etc/pure-ftpd/conf/DontResolve

apt-get install mydns-mysql

apt-get install vlogger webalizer

php -q ../install/install.php
