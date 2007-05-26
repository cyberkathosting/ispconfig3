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

/*
	ISPConfig 3 installer.
*/

// Include the library with the basic installer functions
require_once('lib/install.lib.php');

// Include the base class of the installer class
require_once('lib/installer_base.lib.php');

$distname = get_distname();

// Include the distribution specific installer class library
// and configuration
include_once('dist/lib/'.$distname.'.lib.php');
include_once('dist/conf/'.$distname.'.conf.php');

$inst = new installer();



echo "This application will install ISPConfig 3 on your server.\n";

// $conf["language"] = $inst->request_language();

// TODO: all other queries, for testing I will setup everything in $conf

// Initialize the MySQL server connection
include_once('lib/mysql.lib.php');
$inst->db = new db();

// Create the mysql database
$inst->configure_database();

// Configure postfix
$inst->configure_postfix();

// Configure saslauthd
swriteln('Configuring SASL');
$inst->configure_saslauthd();


// Configure PAM
swriteln('Configuring PAM');
$inst->configure_pam();

// Configure courier
swriteln('Configuring Courier');
$inst->configure_courier();

// Configure Spamasassin
swriteln('Configuring Spamassassin');
$inst->configure_spamassassin();

// Configure Amavis
swriteln('Configuring Amavisd');
$inst->configure_amavis();

// Configure Amavis
swriteln('Installing ISPConfig');
$inst->install_ispconfig();


/*
Restart services:

saslauthd
all courier
apache2
postfix
amavisd
calmd
spamd



*/


echo "Installation finished.\n";


?>