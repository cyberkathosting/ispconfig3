#!/bin/bash

# Create the jailkit chroot

#
# Usage: ./create_jailkit_chroot username 'basicshell editors'
#


# Sanity check

if [ "$1" = "" ]; then
        echo "    Usage: ./create_jailkit_chroot username 'basicshell editors'"
        exit
fi

CHROOT_HOMEDIR=$1
CHROOT_APP_SECTIONS=$2

## Change ownership of the chroot directory to root
chown root:root $CHROOT_HOMEDIR

## Initialize the chroot into the specified directory with the specified applications
jk_init -f -k -c /etc/jailkit/jk_init.ini -j $CHROOT_HOMEDIR $CHROOT_APP_SECTIONS

## Create the temp directory
if [ ! -d "$CHROOT_HOMEDIR/tmp" ]
then
  mkdir $CHROOT_HOMEDIR/tmp
fi
chmod a+rwx $CHROOT_HOMEDIR/tmp

## Fix permissions of the root firectory
chmod g-w $CHROOT_HOMEDIR/bin


# mysql needs the socket in the chrooted environment
mkdir $CHROOT_HOMEDIR/var
mkdir $CHROOT_HOMEDIR/var/run
mkdir $CHROOT_HOMEDIR/var/run/mysqld

# ln /var/run/mysqld/mysqld.sock $CHROOT_HOMEDIR/var/run/mysqld/mysqld.sock
if [ -e "/var/run/mysqld/mysqld.sock" ]
then
  ln /var/run/mysqld/mysqld.sock $CHROOT_HOMEDIR/var/run/mysqld/mysqld.sock
fi
