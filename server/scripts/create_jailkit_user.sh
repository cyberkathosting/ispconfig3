#!/bin/bash

# Add user to the jailkit chroot

#
# Usage: ./create_jailkit_user username /path/to/chroot/ /home/webuser /bin/bash web2 /home/web2
#

# Sanity check

if [ "$1" = "" ]; then
        echo "    Usage: ./create_jailkit_user username /path/to/chroot/ /home/webuser /bin/bash"
        exit
fi


CHROOT_USERNAME=$1
CHROOT_HOMEDIR=$2
CHROOT_USERHOMEDIR=$3
CHROOT_SHELL=$4
CHROOT_P_USER=$5
CHROOT_P_USER_HOMEDIR=$6

### Check if USERHOMEDIR already exists ###
if [ ! -d $CHROOT_HOMEDIR/.$CHROOT_USERHOMEDIR ]; then
 mkdir -p $CHROOT_HOMEDIR/.$CHROOT_USERHOMEDIR
 chown -R $CHROOT_USERNAME $CHROOT_HOMEDIR/.$CHROOT_USERHOMEDIR
fi

### Reconfigure the chroot home directory for the user ###
usermod --home=$CHROOT_HOMEDIR/.$CHROOT_USERHOMEDIR $CHROOT_USERNAME 2>/dev/null

### Add the chroot user ###
jk_jailuser -n -s $CHROOT_SHELL -j $CHROOT_HOMEDIR $CHROOT_USERNAME

### We have to reconfigure the chroot home directory for the parent user ###
if [ "$CHROOT_P_USER" != "" ]; then
  usermod --home=$CHROOT_HOMEDIR/.$CHROOT_P_USER_HOMEDIR $CHROOT_P_USER 2>/dev/null
fi