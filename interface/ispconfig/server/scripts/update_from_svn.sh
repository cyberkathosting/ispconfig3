#!/bin/bash

cd /tmp
svn export svn://svn.ispconfig.org/ispconfig3/trunk/
cd trunk/install
php -q update.php
cd /tmp
rm -rf /tmp/trunk

exit 0