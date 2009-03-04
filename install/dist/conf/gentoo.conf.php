<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

//*** Gentoo default settings

//* Main
$dist['init_scripts'] = '/etc/init.d';
$dist['runlevel'] = '/etc';
$dist['shells'] = '/etc/shells';
$dist['cron_tab'] = '/var/spool/cron/crontabs/root';
$dist['pam'] = '/etc/pam.d';

//* MySQL
$dist['mysql']['init_script'] = 'mysql';

//* Apache
$dist['apache']['user'] = 'apache';
$dist['apache']['group'] = 'apache';
$dist['apache']['init_script'] = 'apache2';
$dist['apache']['version'] = '2.2';
$dist['apache']['vhost_dist_dir'] = '/etc/apache2/vhosts.d';
$dist['apache']['vhost_dist_enabled_dir'] = '/etc/apache2/vhosts.d';
$conf['apache']['vhost_port'] = '8080';

//* Postfix
$dist['postfix']['config_dir'] = '/etc/postfix';
$dist['postfix']['init_script'] = 'postfix';
$dist['postfix']['user'] = 'postfix';
$dist['postfix']['group'] = 'postfix';
$dist['postfix']['vmail_userid'] = '5000';
$dist['postfix']['vmail_username'] = 'vmail';
$dist['postfix']['vmail_groupid'] = '5000';
$dist['postfix']['vmail_groupname'] = 'vmail';
$dist['postfix']['vmail_mailbox_base'] = '/var/vmail';

//* Getmail
$dist['getmail']['config_dir'] = '/etc/getmail';
$dist['getmail']['program'] = '/usr/bin/getmail';

//* Courier
$dist['courier']['config_dir'] = '/etc/courier';
$dist['courier']['courier-authdaemon'] = 'courier-authlib';
$dist['courier']['courier-imap'] = 'courier-imapd';
$dist['courier']['courier-imap-ssl'] = 'courier-imapd-ssl';
$dist['courier']['courier-pop'] = 'courier-pop3d';
$dist['courier']['courier-pop-ssl'] = 'courier-pop3d-ssl';

//* SASL
$dist['saslauthd']['config'] = '/etc/default/saslauthd';
$dist['saslauthd']['init_script'] = 'saslauthd';

//* Amavisd
$dist['amavis']['config_dir'] = '/etc/amavis';
$dist['amavis']['init_script'] = 'amavisd';

//* ClamAV
$dist['clamav']['init_script'] = 'clamd';

//* Pureftpd
$dist['pureftpd']['config_dir'] = '/etc/pure-ftpd';
$dist['pureftpd']['init_script'] = 'pure-ftpd';

//* MyDNS
$dist['mydns']['config_dir'] = '/etc';
$dist['mydns']['init_script'] = 'mydns';

//* Jailkit
$conf['jailkit']['config_dir'] = '/etc/jailkit';
$conf['jailkit']['jk_init'] = 'jk_init.ini';
$conf['jailkit']['jk_chrootsh'] = 'jk_chrootsh.ini';
$conf['jailkit']['jailkit_chroot_app_programs'] = '/usr/bin/groups /usr/bin/id /usr/bin/dircolors /usr/bin/lesspipe /usr/bin/basename /usr/bin/dirname /usr/bin/nano /usr/bin/pico';


?>