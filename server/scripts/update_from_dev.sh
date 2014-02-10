#!/bin/bash

cd /tmp
wget -O ispconfig3-dev.tar.gz "http://git.ispconfig.org/ispconfig/ispconfig3/repository/archive.tar.gz?ref=master"
tar xzf ispconfig3-dev.tar.gz
cd ispconfig3.git/install
php -q update.php
cd /tmp
rm -rf /tmp/ispconfig3.git /tmp/ispconfig3-dev.tar.gz

exit 0