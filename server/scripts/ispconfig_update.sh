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
    n=$(readlink -f ${0})
    if [ "$(basename ${0})" == "ispconfig_update.sh" ]
    then
        cp -p ${n} ${n}.exec
        chmod +x ${n}.exec
        exec ${n}.exec
    else
        # clean up tmp .exec file
        if [ "$(basename ${0})" == "ispconfig_update.sh.exec" ]; then
            rm -f ${0}
        fi

        exec php -q \
            -d disable_classes= \
            -d disable_functions= \
            -d open_basedir= \
            /usr/local/ispconfig/server/scripts/ispconfig_update.php

    fi
fi
}

