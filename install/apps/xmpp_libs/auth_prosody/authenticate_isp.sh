#!/bin/bash

IFS=":"
AUTH_OK=1
AUTH_FAILED=0
LOGFILE="/var/log/prosody/auth.log"
USELOG=true

while read ACTION USER HOST PASS ; do

    [ $USELOG == true ] && { echo "Date: $(date) Action: $ACTION User: $USER Host: $HOST" >> $LOGFILE; }

    case $ACTION in
        "auth")
            if [ `/usr/bin/php /usr/local/lib/prosody/auth/db_auth.php $USER $HOST $PASS 2>/dev/null` == 1 ] ; then
                echo $AUTH_OK
                [ $USELOG == true ] && { echo "AUTH OK" >> $LOGFILE; }
            else
                echo $AUTH_FAILED
                [ $USELOG == true ] && { echo "AUTH FAILED" >> $LOGFILE; }
            fi
        ;;
        "isuser")
             if [ `/usr/bin/php /usr/local/lib/prosody/auth/db_isuser.php $USER $HOST 2>/dev/null` == 1 ] ; then
                echo $AUTH_OK
                [ $USELOG == true ] && { echo "ISUSER OK" >> $LOGFILE; }
            else
                echo $AUTH_FAILED
                [ $USELOG == true ] && { echo "ISUSER FAILED" >> $LOGFILE; }
            fi
        ;;
        *)
            echo $AUTH_FAILED
            [ $USELOG == true ] && { echo "UNKNOWN ACTION GIVEN: $ACTION" >> $LOGFILE; }
        ;;
    esac

done
