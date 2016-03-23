#!/bin/bash

{
    umask 0077 \
    && tmpdir=`mktemp -dt "$(basename $0).XXXXXXXXXX"` \
    && test -d "${tmpdir}" \
    && cd "${tmpdir}"
} || {
    echo 'mktemp failed'
    exit 1
}

wget -O ispconfig3-dev.tar.gz "https://git.ispconfig.org/ispconfig/ispconfig3/repository/archive.tar.gz?ref=master"
tar xzf ispconfig3-dev.tar.gz

echo -n "Latest git version:  "
ls -1d ispconfig3-master*
cd ispconfig3-master*/install

php -q \
    -d disable_classes= \
    -d disable_functions= \
    -d open_basedir= \
    update.php

cd /tmp
rm -rf "${tmpdir}"

exit 0
