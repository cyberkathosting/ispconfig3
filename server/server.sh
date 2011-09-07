#!/bin/sh

PATH=/sbin:/usr/sbin:/bin:/usr/bin:/usr/local/sbin:/usr/local/bin:/usr/X11R6/bin

. /etc/profile

if [ -f /tmp/ispconfig3_install/install/autoupdate ]; then
	#
	# there is a auto-update waiting for update, so let's do it
	#
	cd /tmp/ispconfig3_install/install
	/usr/bin/php -q autoupdate.php	
	cd /

	#
	# do some clean-up
	#
	rm /tmp/ispconfig3_install -R

else
	# 
	# there is no update waiting, so lets start the ISPConfig-System
	#
	cd /usr/local/ispconfig/server
	/usr/bin/php -q /usr/local/ispconfig/server/server.php
fi