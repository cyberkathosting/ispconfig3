#!/bin/bash

# Add specified programs and their libraries to the jailkit chroot

#
# Usage: ./create_jailkit_programs /path/to/chroot '/usr/bin/program'
#

# Sanity check

if [ "$1" = "" ]; then
        echo "    Usage: ./create_jailkit_programs /path/to/chroot '/usr/bin/program'"
        exit
fi


CHROOT_HOMEDIR=$1
CHROOT_APP_PROGRAMS=$2

jk_cp -k $CHROOT_HOMEDIR $CHROOT_APP_PROGRAMS
