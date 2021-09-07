#!/bin/bash

_UPD=1

# padding handles script being overwritten during updates
# see https://git.ispconfig.org/ispconfig/ispconfig3/issues/4227

##################################################
##################################################
##################################################
##################################################
##################################################
##################################################
##################################################
##################################################
##################################################
##################################################
##################################################
##################################################

SOURCE=$1
URL=""

if [[ "$SOURCE" == "stable" ]] ; then
	URL="https://www.ispconfig.org/downloads/ISPConfig-3-stable.tar.gz"
elif [[ "$SOURCE" == "nightly" ]] ; then
	URL="https://www.ispconfig.org/downloads/ISPConfig-3-nightly.tar.gz"
elif [[ "$SOURCE" == "git-develop" ]] ; then
	URL="https://git.ispconfig.org/ispconfig/ispconfig3/-/archive/develop/ispconfig3-develop.tar.gz"
else 
	echo "Please choose an installation source (stable, nightly, git-develop)"
	exit 1
fi

CURDIR=$PWD

cd /tmp

{
if [ -n "${_UPD}" ]
then
    {
        save_umask=`umask`
        umask 0077 \
        && tmpdir=`mktemp -dt "$(basename $0).XXXXXXXXXX"` \
        && test -d "${tmpdir}" \
        && cd "${tmpdir}"
        umask $save_umask
    } || {
        echo 'mktemp failed'
        exit 1
    }

    echo "Downloading ISPConfig update."
    wget -q -O ISPConfig-3.tar.gz "${URL}"
    if [ -f ISPConfig-3.tar.gz ]
    then
        echo "Unpacking ISPConfig update."
        tar xzf ISPConfig-3.tar.gz --strip-components=1
        cd install/
        php -q \
            -d disable_classes= \
            -d disable_functions= \
            -d open_basedir= \
            update.php
        cd /tmp
        rm -rf "${tmpdir}"
    else
        echo "Unable to download the update."
		cd "$CURDIR"
        exit 1
    fi

fi

cd "$CURDIR"
exit 0
}
