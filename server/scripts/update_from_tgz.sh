#!/bin/bash

cd /tmp
wget http://www.ispconfig.org/downloads/ISPConfig-3-stable.tar.gz
cd ispconfig3_install/install/
php -q update.php
rm -rf /tmp/ispconfig3_install/install