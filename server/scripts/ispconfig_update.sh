#!/bin/bash

php -q \
    -d disable_classes= \
    -d disable_functions= \
    -d open_basedir= \
    /usr/local/ispconfig/server/scripts/ispconfig_update.php
