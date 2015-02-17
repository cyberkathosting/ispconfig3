#!/bin/bash

IFS=":"
AUTH_OK=1
AUTH_FAILED=0
LOGFILE="/var/log/metronome/auth.log"
USELOG=true

while read ACTION USER HOST PASS ; do

    [ $USELOG == true ] && { echo "Date: $(date) Action: $ACTION User: $USER Host: $HOST" >> $LOGFILE; }

    case $ACTION in
        "auth")
            if [ `/usr/bin/php /usr/lib/metronome/spicy-modules/mod_auth_external/authenticate_isp.php $USER $HOST $PASS` == 1 ] ; then
                echo $AUTH_OK
                [ $USELOG == true ] && { echo "AUTH OK" >> $LOGFILE; }
            else
                echo $AUTH_FAILED
                [ $USELOG == true ] && { echo "AUTH FAILED" >> $LOGFILE; }
            fi
        ;;
        "isuser")
             if [ `/usr/bin/php /usr/lib/metronome/spicy-modules/mod_auth_external/isuser_isp.php $USER $HOST` == 1 ] ; then
                echo $AUTH_OK
                [ $USELOG == true ] && { echo "AUTH OK" >> $LOGFILE; }
            else
                echo $AUTH_FAILED
                [ $USELOG == true ] && { echo "AUTH FAILED" >> $LOGFILE; }
            fi
        ;;
        *)
            echo $AUTH_FAILED
            [ $USELOG == true ] && { echo "NO ACTION GIVEN" >> $LOGFILE; }
        ;;
    esac

done
