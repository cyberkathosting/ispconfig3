#!/bin/bash

cd /tmp

if [ -f ISPConfig-3-stable.tar.gz ]
then
rm -f ISPConfig-3-stable.tar.gz
fi

wget http://www.ispconfig.org/downloads/ISPConfig-3-stable.tar.gz
tar xvfz ISPConfig-3-stable.tar.gz
cd ispconfig3_install/install/
php -q update.php
rm -rf /tmp/ispconfig3_install/install
rm -f ISPConfig-3-stable.tar.gz