#!/bin/bash
# Copyright (c) 2009, Scott Barr <gsbarr@gmail.com>
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#    * Redistributions of source code must retain the above copyright
#      notice, this list of conditions and the following disclaimer.
#    * Redistributions in binary form must reproduce the above copyright
#      notice, this list of conditions and the following disclaimer in the
#      documentation and/or other materials provided with the distribution.
#    * Neither the name of the <organization> nor the
#      names of its contributors may be used to endorse or promote products
#      derived from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS ''AS IS''
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
# DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
# ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
# (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
# SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

# Global vars
install_mail="no"
install_web="no"
install_ftp="no"
install_dns="no"
spinner_pid=0
version="0.7"

source_path=`dirname $0`
source ${source_path}/utils.sh

function package_has_use_flag()
{
        local package=$1
        local useflag=$2

        res=`equery -C -N uses $package | grep -o -P "^ [-+]{1} \+ $useflag"`
        [ -n "$res" ]
}

function package_is_emerged()
{
		local package=$1
		
		installed=`equery -C -N list -e -i $1 | grep $1 | grep "^\[I"`
		[ -n "$installed" ]
}

function is_package_installed()
{
        local usechange="no"
        local uselist=""

        package_is_emerged "$1"
		installed=$?

        if [ -n "$2" ] # Use flags parsed
        then
                for useflag in $2
                do
                        uselist="$uselist +$useflag"

                        # If the use flag isn't currently set or wasn't enabled when installed we'll need to re-install it.
                        package_has_use_flag "$1" "$useflag" || usechange="yes"
                done

                flagedit $1 $uselist --nowarn
        fi

        [ $installed -eq 0 ] && [ "$usechange" == "no" ]
}

function install_progress()
{
	SP_STRING="/-\\|"
	packages=( $2 )
	
	IP_STRING=`printf "1 of %d" "${#packages[@]}"`
	loop_count=0
	nowf=`date +'%b %d, %Y %H:%M'`
	
	while [ -d /proc/$1 ] && [ -d /proc/$$ ]
	do 
		printf "\e[1;37m\e7[ %1.1s %s ] \e8\e[0m" "$SP_STRING" "$IP_STRING"
		sleep 0.2
		
		if [ $loop_count -lt 8 ]
		then
			loop_count=$(($loop_count+1))
		else
			current=`sed -n "/Started emerge on: $nowf/,/G/p" /var/log/emerge.log | grep ">>> emerge" | tail -1 | grep -m 1 -o -P "\d+ of \d+"`
			if [ -n "$current" ]
			then
				IP_STRING=$current
			fi
			loop_count=0
		fi
		
		SP_STRING=${SP_STRING#"${SP_STRING%?}"}${SP_STRING%?};
	done
	
	printf "%-15s" " "
}

function install_packages()
{
	local package_list=$1
	local title=$2
	
	if [ -z "$title" ]
	then
		title="Installing packages"
	fi
	
	if [ -n "$package_list" ]
	then
		echo -e "The following packages are going to be emerged (not including dependencies):"
		echo -e "$package_list"
		echo -e ""
		countdown "00:00:10" Continue in
		
		echo -e ""
		
		printf "%-40s" "$title"	
		
		(emerge $package_list >/dev/null 2>&1) &
		pid=$!
		
		install_progress $pid "$package_list"
		wait $pid
		status=$?
		
		if [ $status -eq 0 ];
	    then
		   echo -e "\e[1;37m[ \e[0m\e[1;32mok\e[0m\e[1;37m ]    \e[0m"
	    else
		   echo -e "\e[1;37m[ \e[0m\e[1;31mfailed\e[0m\e[1;37m ]    \e[0m"
		   echo -e "Failed installing the following packages:"
		   echo -e "$1"
		   exit 1
	    fi
	else
		echo -e "No packages to install!"
	fi
}

function install_rcscripts()
{
	if [ -n "$1" ]
	then
		
		for rc in $1
		do
			res=`rc-status default | grep "$1" | grep -v grep`
			if [ -z "$res" ]
			then
			  printf "\e[1;37m%-40s\e[0m" "Adding $rc to default runlevel"
			  start_spinner
			  
			  rc-update add $rc default &> /dev/null
			  
			  stop_spinner
			  echo -e "\e[1;37m[ \e[0m\e[1;32mdone\e[0m\e[1;37m ]\e[0m"
			fi
		done
	fi
}

function enable_apache_module()
{
	local apache_conffile='/etc/conf.d/apache2'
	
	if [ -n "$1" ]
	then
		source $apache_conffile
			
		if [ $(expr "$APACHE2_OPTS" : ".*$1.*") == "0" ]
		then
			APACHE2_OPTS="${APACHE2_OPTS} -D $1"
			sed -i -e "s:APACHE2_OPTS=\".*\":APACHE2_OPTS=\"${APACHE2_OPTS}\":" $apache_conffile
		fi
	fi
}

function meta_mail()
{
	local package_list=""
	local add_maildrop=""
	local remove_ssmtp="no"
	local rc_scripts=""
	local use_courier="no"
	local use_dovecot="no"
	local useflags_postfix="mysql"
	local installed_postfix="no"
	local installed_amavisd="no"
	
	valid_input "Do you want to use dovecot or courier?" "dovecot/courier"

	if [ "$Return_Val" = "dovecot" ]
	then
		use_dovecot="yes"
	else
	    use_courier="yes"
	fi
	
	echo -e ""
	printf "\e[1;37m%-40s\e[0m" "Building list of required mail packages"
	
	start_spinner
	
	if [ "$use_courier" == "yes" ]
	then
		is_package_installed "net-libs/courier-authlib" "mysql" || { package_list="$package_list net-libs/courier-authlib"; rc_scripts="$rc_scripts courier-authlib"; }
	
		is_package_installed "net-mail/courier-imap" "fam" || { package_list="$package_list net-mail/courier-imap"; rc_scripts="$rc_scripts courier-imapd courier-imapd-ssl courier-pop3d courier-pop3d-ssl"; }
		
		is_package_installed "mail-filter/maildrop" || add_maildrop="yes" # Avoid file collision warnings from emerge
		
		is_package_installed "dev-libs/cyrus-sasl" "mysql" || { package_list="$package_list dev-libs/cyrus-sasl"; rc_scripts="$rc_scripts saslauthd"; }
		
		useflags_postfix="$useflags_postfix sasl"
	fi
	
	if [ "$use_dovecot" == "yes" ]
	then
		is_package_installed "net-mail/dovecot" "sieve managesieve maildir" || { package_list="$package_list net-mail/dovecot"; rc_scripts="$rc_scripts dovecot"; }
		
		useflags_postfix="$useflags_postfix dovecot-sasl"
	fi
	
	if ! is_package_installed "mail-mta/postfix" "$useflags_postfix"
	then
		is_package_installed "mail-mta/ssmtp" && local remove_ssmtp="yes"; # Ssmtp blocks postfix and is installed by default.
		package_list="$package_list mail-mta/postfix"
		rc_scripts="$rc_scripts postfix"
		
		package_is_emerged "mail-mta/postfix" # USE flag difference will result in re-installing. If we are installing for the first time do some things later.
		installed=$?
	
		if [ $installed -eq 1 ]
		then
			installed_postfix="yes"
		fi
	fi	
	
	is_package_installed "net-mail/getmail" || package_list="$package_list net-mail/getmail"
	
	if ! is_package_installed "mail-filter/amavisd-new" "mysql razor spamassassin"
	then
		package_list="$package_list mail-filter/amavisd-new"
		rc_scripts="$rc_scripts amavisd"
		
		package_is_emerged "mail-filter/amavisd-new" # USE flag difference will result in re-installing. If we are installing for the first time do some things later.
		installed=$?
	
		if [ $installed -eq 1 ]
		then
			installed_amavisd="yes"
		fi
	fi
	
	is_package_installed "app-antivirus/clamav" || { package_list="$package_list app-antivirus/clamav"; rc_scripts="$rc_scripts clamd"; }
	
	is_package_installed "dev-perl/Authen-SASL" || package_list="$package_list dev-perl/Authen-SASL"
	
	is_package_installed "dev-perl/perl-ldap" || package_list="$package_list dev-perl/perl-ldap"
	
	stop_spinner
	echo -e "\e[1;37m[ \e[0m\e[1;32mdone\e[0m\e[1;37m ]\e[0m"
	
	if [ "$remove_ssmtp" == "yes" ]
	then
		exec_command "emerge --unmerge mail-mta/ssmtp" "Removing ssmtp to install postfix"
	fi
	
	install_packages "$package_list" "Installing mail packages"
	
	if [ -n "$add_maildrop" ]
	then
		exec_command "COLLISION_IGNORE=\"/usr\" emerge mail-filter/maildrop" "Installing maildrop"
	fi
	
	if [ "$installed_postfix" == "yes" ] && [ ! -d '/etc/mail/aliases.db' ]
	then
		postmap /etc/mail/aliases 2> /dev/null
	fi
	
	if [ "$installed_amavisd" == "yes" ]
	then
		if [ -e '/usr/share/spamassassin/sa-update-pubkey.txt' ]
		then
			sa-update --import /usr/share/spamassassin/sa-update-pubkey.txt
		fi
		
		sa-update
	fi
	
	install_rcscripts "$rc_scripts"
}

function meta_web()
{
	local package_list=""
	local fix_jailkit="no"
	local linguas_add="no"
	local webmail_add="no"
	local rc_scripts=""
	local installed_fcgid="no"
	
	if ! is_package_installed "dev-vcs/subversion"
    then
		flagedit dev-vcs/subversion -apache2 --nowarn
    fi
	
	is_package_installed "app-portage/layman" "subversion" || exec_command "emerge app-portage/layman" "Installing layman";

	# Check if sunrise overlay has been enabled
	if [ -z "$(layman -l | grep sunrise)" ]
	then
		layman -q -S > /dev/null
		exec_command "layman -a sunrise" "Adding/syncing package overlay"
	fi

	if [ -z "$(grep 'var/lib/layman' /etc/make.conf)" ]
	then
		echo "source /var/lib/layman/make.conf" >> /etc/make.conf
	fi
	
	echo -e ""
	printf "\e[1;37m%-40s\e[0m" "Building list of required web packages"
	
	start_spinner
	
	# Check profile and ensure the apache modules ISPConfig needs are enabled.
	source /etc/make.conf
	
	if [ -z "${APACHE2_MODULES+xxx}" ] # Not set, fetch defaults
	then
		source /usr/portage/profiles/base/make.defaults
		echo "APACHE2_MODULES=\"$APACHE2_MODULES\"" >> /etc/make.conf
		
		source /etc/make.conf
	fi
	
	local added_module='no'
	for module in rewrite dav dav_fs auth_digest
	do
		if [ $(expr "$APACHE2_MODULES" : ".*$module.*") == "0" ]
		then
			APACHE2_MODULES="${APACHE2_MODULES} $module"
			added_module='yes'
		fi
	done
	
	if [ "$added_module" == "yes" ]
	then
		sed -i -e "s:APACHE2_MODULES=\".*\":APACHE2_MODULES=\"${APACHE2_MODULES}\":" /etc/make.conf
	fi
	
	local added_worker='no'
	if [ -z "${APACHE2_MPMS+xxx}" ] # Not set, fetch defaults
	then
		echo 'APACHE2_MPMS="prefork"' >> /etc/make.conf
		added_worker='yes'
	else
		if [ $(expr "$APACHE2_MPMS" : '.*prefork.*') == "0" ]
		then
			APACHE2_MPMS="${APACHE2_MPMS} prefork"
			sed -i -e "s:APACHE2_MPMS=\".*\":APACHE2_MPMS=\"${APACHE2_MPMS}\":" /etc/make.conf
			added_worker='yes'
		fi
	fi
	
	if is_package_installed "www-servers/apache" "ssl suexec doc" || "$added_module" == "yes" || "$added_worker" == "yes"
	then
		package_list="$package_list www-servers/apache"
		rc_scripts="$rc_scripts apache2"
	fi
	
	if ! is_package_installed "www-apache/mod_fcgid"
	then
		installed_fcgid="yes"
		package_list="$package_list www-apache/mod_fcgid"
	fi
	
	if ! is_package_installed "app-admin/webalizer" "vhosts apache2"
	then
	
		is_package_installed "media-libs/gd" "jpeg png" || package_list="$package_list media-libs/gd"
		
		if package_has_use_flag "app-admin/webalizer" "nls"
		then
			source /etc/make.conf
			if [ -z "${LINGUAS}" ]
			then
				linguas_add="yes"
			fi
		fi
	
		package_list="$package_list app-admin/webalizer"
	fi
	
	is_package_installed "www-misc/awstats" "vhosts apache2" || package_list="$package_list www-misc/awstats"
	
	if ! is_package_installed "app-admin/vlogger" "dbi"
	then
		# Check if package is masked
		if [ -n "$(equery -C -N list -I -o -e app-admin/vlogger | grep app-admin/vlogger | awk '{print $2}' | grep '^\[M')" ]
		then
			flagedit app-admin/vlogger -- +~amd64 +~x86 > /dev/null
		fi
		
		package_list="$package_list app-admin/vlogger"
	fi
	
	is_package_installed "app-crypt/mcrypt" || package_list="$package_list app-crypt/mcrypt"
	
	is_package_installed "dev-lang/php" "apache2 gd mysql mysqli imap cli cgi pcre xml zlib crypt ctype session unicode mhash ftp soap" || package_list="$package_list dev-lang/php"
	
	if ! is_package_installed "www-apache/mod_suphp"
	then
		# Check if package is masked
		if [ -n "$(equery -C -N list -I -p -e www-apache/mod_suphp | grep www-apache/mod_suphp | awk '{print $2}' | grep '^\[M')" ]
		then
			flagedit www-apache/mod_suphp -- +~amd64 +~x86 > /dev/null
		fi
		
		package_list="$package_list www-apache/mod_suphp"
	fi
	
	is_package_installed "www-apache/mod_ruby" || package_list="$package_list www-apache/mod_ruby"
	
	is_package_installed "media-gfx/imagemagick" "jpeg png tiff" || package_list="$package_list media-gfx/imagemagick"
	
	is_package_installed "dev-php/PEAR-PEAR" || package_list="$package_list dev-php/PEAR-PEAR"
	
	is_package_installed "dev-php/PEAR-Auth" || package_list="$package_list dev-php/PEAR-Auth"
	
	is_package_installed "dev-php5/pecl-imagick" || package_list="$package_list dev-php5/pecl-imagick"
	
	is_package_installed "sys-auth/pam_mysql" || package_list="$package_list sys-auth/pam_mysql"
	
	is_package_installed "app-admin/sudo" || package_list="$package_list app-admin/sudo"
	
	is_package_installed "app-arch/zip" || package_list="$package_list app-arch/zip"
	
	if ! is_package_installed "app-admin/jailkit"
	then
		# Check if package is masked
		if [ -n "$(equery -C -N list -I -o -e app-admin/jailkit | grep app-admin/jailkit | awk '{print $2}' | grep '^\[M')" ]
		then
			flagedit app-admin/jailkit -- +~amd64 +~x86 > /dev/null
		fi
		
		# The ebuild for jailkit 2.3 has a nasty bug that breaks the login shell. Check for version and
		# apply the fix if necessary.
		if [ "$(emerge -pv app-admin/jailkit | grep -o -P '(?<=jailkit-)[\d-.rp_]+')" == "2.3" ]
		then
			fix_jailkit="yes"
			cp /etc/shells /etc/shells~
		fi
		
		package_list="$package_list app-admin/jailkit"
	fi
	
	#if [ "$install_mail" == "yes" ] && ! is_package_installed "mail-client/squirrelmail" "vhosts"
	#then
	#	if ! is_package_installed "app-admin/webapp-config"
	#	then
	#		package_list="$package_list app-admin/webapp-config"
	#	fi
		
	#	webmail_add="yes"
	#	package_list="$package_list mail-client/squirrelmail"
	#fi
	
	stop_spinner
	echo -e "\e[1;37m[ \e[0m\e[1;32mdone\e[0m\e[1;37m ]\e[0m"
	
	if [ "$linguas_add" == "yes" ]
	then
		echo -e ""
		echo -e "The nls use flag is enabled for webalizer and no locale preference is set in make.conf. If "
		echo -e "not set webalizer will fail to install."
		echo -e ""
		
		accept_locales=`find /usr/share/locale/ -maxdepth 1 -type d -exec basename '{}' \; | grep -v "locale" | sort | tr "\n" "/" | sed -e 's,\(.\)/$,\1,'`
		valid_input "Set locale value for gettext-based programs to: " "$accept_locales" "en"
		
		echo "LINGUAS=\"$Return_Val\"" >> /etc/make.conf
		echo -e ""
	fi
	
	install_packages "$package_list" "Installing web packages"
	
	if [ $? -eq 0 ] && [ "$fix_jailkit" == "yes" ] && [ -z "$(grep 'jk_chrootsh' /etc/shells)" ]
	then
		cp /etc/shells /etc/shells.jailkit-v2.3
		cp /etc/shells~ /etc/shells
		echo "/usr/sbin/jk_chrootsh" >> /etc/shells
	fi
	
	for config_module in SUEXEC FCGID AUTH_DIGEST DAV DAV_FS RUBY
	do
		enable_apache_module "$config_module"
	done
	
	#if [ "$webmail_add" == "yes" ]
	#then
	#	exec_command "webapp-config -I -h localhost -u apache -d /webmail squirrelmail $(ls -r /usr/share/webapps/squirrelmail/ | awk '{print $1}')" "Adding squirrelmail to localhost"
	#fi
	
	install_rcscripts "$rc_scripts"
}

function meta_ftp()
{
	local package_list=""
	local rc_scripts=""
	
	echo -e ""
	printf "\e[1;37m%-40s\e[0m" "Building list of required ftp packages"
	
	start_spinner
	
	is_package_installed "net-ftp/pure-ftpd" "mysql" || { package_list="$package_list net-ftp/pure-ftpd"; rc_scripts="$rc_scripts pure-ftpd"; }
	
	is_package_installed "sys-fs/quota" || package_list="$package_list sys-fs/quota";
	
	if ! is_package_installed "sys-fs/quotatool"
	then
		# Check if package is masked
		if [ -n "$(equery -C -N list -I -p -e sys-fs/quotatool | grep sys-fs/quotatool | awk '{print $2}' | grep '^\[M')" ]
		then
			flagedit sys-fs/quotatool -- +~amd64 +~x86 > /dev/null
		fi
	
		package_list="$package_list sys-fs/quotatool"
	fi
	
	stop_spinner
	echo -e "\e[1;37m[ \e[0m\e[1;32mdone\e[0m\e[1;37m ]\e[0m"
	
	if [ $(expr "$package_list" : '.*sys-fs/quota.*') -ne 0 ]
	then
		echo -e ""
		echo -e "\e[1;33mNotice:\e[0m Don't forget to edit your fstab file and add the usrquota & grpquota options to your data partition."
		echo -e ""
		sleep 2
	fi
	
	install_packages "$package_list" "Installing ftp packages"
	
	install_rcscripts "$rc_scripts"
}

function meta_dns()
{
	local package_list=""
	local use_bind="no"
	local use_pdns="no"
	
	valid_input "Do you want to use bind or powerdns?" "bind/powerdns"

	if [ "$Return_Val" = "bind" ]
	then
		use_bind="yes"
	else
	    use_pdns="yes"
	fi
	
	echo -e ""
	printf "\e[1;37m%-40s\e[0m" "Building list of required dns packages"
	
	start_spinner
	
	if [ "$use_bind" == "yes" ]
	then
		is_package_installed "net-dns/bind" "mysql dlz" || { package_list="$package_list net-dns/bind"; rc_scripts="$rc_scripts named"; }
	fi
	
	if [ "$use_pdns" == "yes" ]
	then
		is_package_installed "net-dns/pdns" "mysql" || package_list="$package_list net-dns/pdns";
	fi
	
	stop_spinner
	echo -e "\e[1;37m[ \e[0m\e[1;32mdone\e[0m\e[1;37m ]\e[0m"
	
	install_packages "$package_list" "Installing dns packages"
	
	install_rcscripts "$rc_scripts"
}

function meta_all()
{
	meta_mail
	meta_web
	meta_ftp
	meta_dns
}

case $1 in
	"--version"|"-h"|"--help")
		echo -e "Gentoo Linux ISPConfig setup script"
		echo -e "Version $version"
		echo -e ""
		echo -e "No arguments needed, simply execute the script."
		exit 0
		;;
	*)
		;;
esac

clear

echo -e "\e[1;33mGentoo Linux ISPConfig setup script v$version\e[0m"
echo -e "\e[1;32m========================================\e[0m"
echo -e ""

if [ -e "/etc/gentoo-release" ]
then
	BASELAYOUT_VERSION=$(cat /etc/gentoo-release | sed "s/[^0-9.]//g")
	package_is_emerged "sys-apps/baselayout-$BASELAYOUT_VERSION"
	installed=$?
	
	if [ "$installed" != "0" ]
	then
		echo -e ""
		echo "This script is exclusively for use with a Gentoo Linux system."
		exit 10
	fi
else
	echo -e ""
	echo "This script is exclusively for use with a Gentoo Linux system."
	exit 10
fi

sleep 0.5

valid_input "Would you like to sync portage now?" 
if [ "$Return_Val" = "yes" ]
then
	exec_command "emerge --sync --quiet" "Updating portage tree"
fi

# Get all the programs we need to do portage queries etc.
echo -en "\e[1;37mChecking for required packages\e[0m\n"

# Verify if gentoolkit has been emerged and install if not
which equery &> /dev/null
if [ $? -ne 0 ]
then
	exec_command "emerge app-portage/gentoolkit" "Installing gentoolkit"
fi

which flagedit &> /dev/null
if [ $? -ne 0 ]
then
	exec_command "emerge app-portage/flagedit" "Installing flagedit"
fi

if ! is_package_installed "app-admin/rsyslog"
then
	printf "\e[1;37m%-40s\e[0m" "Checking for installed system loggers"
	start_spinner

	loggers=( syslog-ng metalog sysklogd  )
	clogger=""
	rsyslog_install="yes"

	for logger in $loggers
	do
		if is_package_installed "app-admin/$logger" && [ -n "$(rc-config list default | grep $logger)" ]
		then
			clogger=$logger
			break
		fi
	done

	stop_spinner
	echo -e "\e[1;37m[ \e[0m\e[1;32mdone\e[0m\e[1;37m ]\e[0m"

	if [ "$clogger" != "" ]
	then
		echo -e ""
		echo -e "$clogger appears to be installed on your system."
		echo -e "To use the log monitoring features in ISPConfig"
		echo -e "the log facilities need to be configured to certain"
		echo -e "paths. Currently the default rsyslog file is used."
		echo -e ""
		
		valid_input "Would you like to replace $clogger with rsyslog?"
		if [ "$Return_Val" = "yes" ]
		then
			echo -e ""
			exec_command "/etc/init.d/$clogger stop" "Stopping $clogger"
			exec_command "rc-update del $clogger default" "Remove $clogger from default runlevel"
		else
			rsyslog_install="no"
		fi

	fi

	if [ "$rsyslog_install" == "yes" ]
	then
		exec_command "emerge app-admin/rsyslog" "Installing rsyslog"
		exec_command "/etc/init.d/rsyslog start" "Starting rsyslog"
		exec_command "rc-update add rsyslog default" "Add rsyslog to default runlevel"
	fi
	
	echo -e ""
	
fi

if ! is_package_installed "dev-db/mysql" "extraengine big-tables"
then
	package_is_emerged "dev-db/mysql"
	installed=$?
	
	exec_command "emerge dev-db/mysql" "Installing MySql"
	
	if [ $installed -eq 1 ]
	then
		echo -e ""
		echo -e "\e[1;33mNotice:\e[0m Don't forget to set the mysql root password with: /usr/bin/mysqladmin -u root password 'new-password'."
		echo -e ""
	fi
fi

if [ ! -d '/var/lib/mysql/mysql' ]
then
	exec_command "mysql_install_db"	"Set-up mysql grant tables"
fi

install_rcscripts "mysql"

mysql_started=`eselect rc show | grep mysql | grep started | grep -v grep`
if [ -z "$mysql_started" ]
then
	exec_command "/etc/init.d/mysql start"	"Starting MySQL service"
fi

which vim &> /dev/null
if [ $? -ne 0 ]
then
	exec_command "emerge app-editors/vim" "Installing vim"
fi

is_package_installed "sys-devel/binutils" || exec_command "emerge sys-devel/binutils" "Installing binutils";
is_package_installed "app-forensics/rkhunter" || exec_command "emerge app-forensics/rkhunter" "Installing rkhunter";
is_package_installed "net-analyzer/fail2ban" || exec_command "emerge net-analyzer/fail2ban" "Installing fail2ban";

echo -e ""

# Service based packages
echo -en "\e[1;37mService based packages\e[0m\n"

valid_input "Install all packages or select targeted services?" "all/targeted"

if [ "$Return_Val" = "all" ]
then
	install_mail="yes"
	install_web="yes"
	install_ftp="yes"
	install_dns="yes"
	meta_all
else
	valid_input "Install mail related packages?"
	install_mail=$Return_Val
	if [ "$install_mail" = "yes" ]
	then
		meta_mail
	fi
	
	echo -e ""
	echo -e "\e[1;33mNotice:\e[0m If this server is going to run the ISPConfig interface, say 'yes' to web related packages."
	echo -e ""
	
	valid_input "Install web related packages?"
	install_web=$Return_Val
	if [ "$install_web" = "yes" ]
	then
		meta_web
	fi
	
	echo -e ""
	valid_input "Install ftp related packages?"
	install_ftp=$Return_Val
	if [ "$install_ftp" = "yes" ]
	then
		meta_ftp
	fi
	
	echo -e ""
	valid_input "Install dns related packages?"
	install_dns=$Return_Val
	if [ "$install_dns" = "yes" ]
	then
		meta_dns
	fi
fi

if [ "$install_web" = "no" ] && ! is_package_installed "dev-lang/php" "-apache2 gd mysql mysqli imap cli cgi pcre xml zlib crypt ctype session unicode mhash ftp"
then
	exec_command "emerge dev-lang/php" "Installing PHP"
fi

echo -e ""
echo -e "\e[1;33mSetup script completed\e[0m"
echo -e "\e[1;32m========================================\e[0m"
echo -e ""

valid_input "Do you want to start the ISPConfig installer?"
if [ "$Return_Val" = "yes" ]
then
	clear
	php -q ../install/install.php
fi

exit $?
