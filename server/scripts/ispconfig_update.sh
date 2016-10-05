#!/bin/bash

_UPD=1

##################################################
##################################################
##################################################
##################################################
##################################################
##################################################

# padding handles script being overwritten during updates
# see https://git.ispconfig.org/ispconfig/ispconfig3/issues/4227

{
if [ -n "${_UPD}" ]
then
    exec php -q \
        -d disable_classes= \
        -d disable_functions= \
        -d open_basedir= \
        /usr/local/ispconfig/server/scripts/ispconfig_update.php
fi
}

