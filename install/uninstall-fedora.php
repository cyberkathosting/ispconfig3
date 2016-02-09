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
	ISPConfig 3 uninstaller for fedora core.
*/

error_reporting(E_ALL|E_STRICT);

require "/usr/local/ispconfig/server/lib/config.inc.php";
require "/usr/local/ispconfig/server/lib/app.inc.php";
require "/usr/local/ispconfig/server/mysql_clientdb.conf";

//** The banner on the command line
echo "\n\n".str_repeat('-', 80)."\n";
echo " _____ ___________   _____              __ _         ____
|_   _/  ___| ___ \ /  __ \            / _(_)       /__  \
  | | \ `--.| |_/ / | /  \/ ___  _ __ | |_ _  __ _    _/ /
  | |  `--. \  __/  | |    / _ \| '_ \|  _| |/ _` |  |_ |
 _| |_/\__/ / |     | \__/\ (_) | | | | | | | (_| | ___\ \
 \___/\____/\_|      \____/\___/|_| |_|_| |_|\__, | \____/
                                              __/ |
                                             |___/ ";
echo "\n".str_repeat('-', 80)."\n";
echo "\n\n>> Uninstall  \n\n";

echo "Are you sure you want to uninsatll ISPConfig? [no]";
$input = fgets(STDIN);
$do_uninstall = rtrim($input);


if($do_uninstall == 'yes') {

	echo "\n\n>> Uninstalling ISPConfig 3... \n\n";

	
	// Delete the ISPConfig database
	//exec("/etc/init.d/mysqld stop");
	//exec("rm -rf /var/lib/mysql/".$conf["db_database"]);
	//exec("/etc/init.d/mysqld start");
	$link = mysqli_connect($clientdb_host, $clientdb_user, $clientdb_password);
	if (!$link) {
		echo "Unable to connect to the database'.mysql_error($link)";
	} else {
		$result=mysqli_query($link,"DROP DATABASE ".$conf['db_database']."';");
		if (!$result) echo "Unable to remove the ispconfig-database ".$conf['db_database']." ".mysqli_error($link)."\n";
		$result=mysqli_query($link, "DROP USER '".$conf['db_user'] ."';");
		if (!$result) echo "Unable to remove the ispconfig-database-user ".$conf['db_user']." ".mysqli_error($link)."\n";
	}
	mysqli_close($link);

	// Deleting the symlink in /var/www
	// Apache
	@unlink("/etc/httpd/conf/sites-enabled/000-ispconfig.vhost");
	@unlink("/etc/httpd/conf/sites-available/ispconfig.vhost");
	@unlink("/etc/httpd/conf/sites-enabled/000-apps.vhost");
	@unlink("/etc/httpd/conf/sites-available/apps.vhost");

	// nginx
	@unlink("/etc/nginx/sites-enabled/000-ispconfig.vhost");
	@unlink("/etc/nginx/sites-available/ispconfig.vhost");
	@unlink("/etc/nginx/sites-enabled/000-apps.vhost");
	@unlink("/etc/nginx/sites-available/apps.vhost");

	// Delete the ispconfig files
	exec('rm -rf /usr/local/ispconfig');

//	echo "Please do not forget to delete the ispconfig user in the mysql.user table.\n\n";

	echo "Finished uninstalling.\n";
} else {
	echo "\n\n>> Canceled uninstall. \n\n";
}

?>
