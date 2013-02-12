#!/bin/sh
sed -r -e 's/(^[^#]\S+\s+).+$/\1local/' $1 > /var/lib/mailman/data/transport-mailman
/usr/sbin/postmap /var/lib/mailman/data/transport-mailman