#!/bin/bash

cd /tmp
rm -f ispconfig3-dev.tar.gz
wget -O ispconfig3-dev.tar.gz "http://git.ispconfig.org/ispconfig/ispconfig3/repository/archive.tar.gz?ref=master"
rm -rf ispconfig3-master*
tar xzf ispconfig3-dev.tar.gz
cd ispconfig3-master*/install
php -q \
    -d disable_classes= \
    -d disable_functions= \
    -d open_basedir= \
    update.php
cd /tmp
rm -rf /tmp/ispconfig3-master* /tmp/ispconfig3-dev.tar.gz

exit 0
