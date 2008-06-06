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
jk_init -f -k -j $CHROOT_HOMEDIR $CHROOT_APP_SECTIONS

## Create the temp directory
mkdir $CHROOT_HOMEDIR/tmp
chmod a+rwx $CHROOT_HOMEDIR/tmp


# mysql needs the socket in the chrooted environment
mkdir $CHROOT_HOMEDIR/var
mkdir $CHROOT_HOMEDIR/var/run
mkdir $CHROOT_HOMEDIR/var/run/mysqld
ln /var/run/mysqld/mysqld.sock $CHROOT_HOMEDIR/var/run/mysqld/mysqld.sock
