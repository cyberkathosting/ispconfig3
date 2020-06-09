<?php
/*
Copyright (c) 2012, ISPConfig UG
Contributors: web wack creations,  http://www.web-wack.at
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
require_once 'aps_base.inc.php';

class ApsGUIController extends ApsBase
{
	/**
	 * Constructor
	 *
	 * @param $app the application instance (db handle)
	 */


	public function __construct($app)
	{
		parent::__construct($app);
	}

	/**
	 * Removes www from Domains name
	 *
	 * @param $filename the file to read
	 * @return $sxe a SimpleXMLElement handle
	 */
	public function getMainDomain($domain) {
		if (substr($domain, 0, 4) == 'www.') $domain = substr($domain, 4);
		return $domain;
	}
	

	/**
	 * Reads in a package metadata file and registers it's namespaces
	 *
	 * @param $filename the file to read
	 * @return $sxe a SimpleXMLElement handle
	 */
	private function readInMetaFile($filename)
	{
		$metadata = file_get_contents($filename);
		$metadata = str_replace("xmlns=", "ns=", $metadata);
		$sxe = new SimpleXMLElement($metadata);
		$namespaces = $sxe->getDocNamespaces(true);
		foreach($namespaces as $ns => $url) $sxe->registerXPathNamespace($ns, $url);

		return $sxe;
	}



	/**
	 * Applies a RegEx pattern onto a location path in order to secure it against
	 * code injections and invalid input
	 *
	 * @param $location_unfiltered the file path to secure
	 * @return $location
	 */
	private function secureLocation($location_unfiltered)
	{
		// Filter invalid slashes from string
		$location = preg_replace(array('#/+#', '#\.+#', '#\0+#', '#\\\\+#'),
			array('/', '', '', '/'),
			$location_unfiltered);

		// Remove a beginning or trailing slash
		if(substr($location, -1) == '/') $location = substr($location, 0, strlen($location) - 1);
		if(substr($location, 0, 1) == '/') $location = substr($location, 1);

		return $location;
	}



	/**
	 * Gets the CustomerID (ClientID) which belongs to a specific domain
	 *
	 * @param $domain the domain
	 * @return $customerid
	 */
	private function getCustomerIDFromDomain($domain)
	{
		global $app;
		$customerid = 0;

		$customerdata = $app->db->queryOneRecord("SELECT client_id FROM sys_group, web_domain
            WHERE web_domain.sys_groupid = sys_group.groupid
            AND web_domain.domain = ?", $domain);
		if(!empty($customerdata)) $customerid = $customerdata['client_id'];

		return $customerid;
	}



	/**
	 * Returns the server_id for an already installed instance. Is actually
	 * just a little helper method to avoid redundant code
	 *
	 * @param $instanceid the instance to process
	 * @return $webserver_id the server_id
	 */
	private function getInstanceDataForDatalog($instanceid)
	{
		global $app;
		$webserver_id = '';

		$websrv = $app->db->queryOneRecord("SELECT server_id FROM web_domain
            WHERE domain = (SELECT value FROM aps_instances_settings
                WHERE name = 'main_domain' AND instance_id = ?)", $instanceid);

		// If $websrv is empty, an error has occured. Domain no longer existing? Settings table damaged?
		// Anyhow, remove this instance record because it's not useful at all
		if(empty($websrv))
		{
			$app->db->query("DELETE FROM aps_instances WHERE id = ?", $instanceid);
			$app->db->query("DELETE FROM aps_instances_settings WHERE instance_id = ?", $instanceid);
		}
		else $webserver_id = $websrv['server_id'];

		return $webserver_id;
	}



	/**
	 * Finds out if there is a newer package version for
	 * a given (possibly valid) package ID
	 *
	 * @param $id the ID to check
	 * @return $newer_pkg_id the newer package ID
	 */
	public function getNewestPackageID($id)
	{
		global $app;

		if(preg_match('/^[0-9]+$/', $id) != 1) return 0;

		$result = $app->db->queryOneRecord("SELECT id, name,
            CONCAT(version, '-', CAST(`release` AS CHAR)) AS current_version
            FROM aps_packages
            WHERE name = (SELECT name FROM aps_packages WHERE id = ?)
            AND package_status = 2
            ORDER BY INET_ATON(SUBSTRING_INDEX(CONCAT(version,'.0.0.0'),'.',4)) DESC, `release` DESC", $id);

		if(!empty($result) && ($id != $result['id'])) return $result['id'];

		return 0;
	}

	/**
	 * Validates a given package ID
	 *
	 * @param $id the ID to check
	 * @param $is_admin a flag to allow locked IDs too (for admin calls)
	 * @return boolean
	 */
	public function isValidPackageID($id, $is_admin = false)
	{
		global $app;

		if(preg_match('/^[0-9]+$/', $id) != 1) return false;

		$sql_ext = (!$is_admin) ?
			'package_status = '.PACKAGE_ENABLED.' AND' :
			'(package_status = '.PACKAGE_ENABLED.' OR package_status = '.PACKAGE_LOCKED.') AND';

		$result = $app->db->queryOneRecord("SELECT id FROM aps_packages WHERE ".$sql_ext." id = ?", $id);
		if(!$result) return false;

		return true;
	}



	/**
	 * Validates a given instance ID
	 *
	 * @param $id the ID to check
	 * @param $client_id the calling client ID
	 * @param $is_admin a flag to ignore the client ID check for admins
	 * @return boolean
	 */
	public function isValidInstanceID($id, $client_id, $is_admin = false)
	{
		global $app;

		if(preg_match('/^[0-9]+$/', $id) != 1) return false;

		// Only filter if not admin
		$params = array();
		$sql_ext = '';
		if(!$is_admin) {
			$sql_ext = 'customer_id = ? AND ';
			$params[] = $client_id;
		}
		$params[] = $id;
		
		$result = $app->db->queryOneRecord('SELECT id FROM aps_instances WHERE '.$sql_ext.' id = ?', true, $params);
		if(!$result) return false;

		return true;
	}

	public function createDatabaseForPackageInstance(&$settings, $websrv) {
		global $app;
	
		$app->uses('tools_sites');
	
		$global_config = $app->getconf->get_global_config('sites');
	
		$tmp = array();
		$tmp['parent_domain_id'] = $websrv['domain_id'];
		$tmp['sys_groupid'] = $websrv['sys_groupid'];
		$dbname_prefix = $app->tools_sites->replacePrefix($global_config['dbname_prefix'], $tmp);
		$dbuser_prefix = $app->tools_sites->replacePrefix($global_config['dbuser_prefix'], $tmp);
		unset($tmp);
	
		// get information if the webserver is a db server, too
		$web_server = $app->db->queryOneRecord("SELECT server_id,server_name,db_server FROM server WHERE server_id  = ?", $websrv['server_id']);
		if($web_server['db_server'] == 1) {
			// create database on "localhost" (webserver)
			$mysql_db_server_id = $app->functions->intval($websrv['server_id']);
			$settings['main_database_host'] = 'localhost';
			$mysql_db_remote_access = 'n';
			$mysql_db_remote_ips = '';

			// If we are dealing with chrooted PHP-FPM, use a network connection instead because the MySQL socket file
			// does not exist within the chroot.
			$php_fpm_chroot = $app->db->queryOneRecord("SELECT php_fpm_chroot FROM web_domain WHERE domain_id = ?", $websrv['domain_id']);
			if ($php_fpm_chroot['php_fpm_chroot'] === 'y') {
				$settings['main_database_host'] = '127.0.0.1';
				$mysql_db_remote_access = 'y';
				$mysql_db_remote_ips = '127.0.0.1';
			}
		} else {
			//* get the default database server of the client
			$client = $app->db->queryOneRecord("SELECT default_dbserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $websrv['sys_groupid']);
			if(is_array($client) && $client['default_dbserver'] > 0 && $client['default_dbserver'] != $websrv['server_id']) {
				$mysql_db_server_id =  $app->functions->intval($client['default_dbserver']);
				$dbserver_config = $web_config = $app->getconf->get_server_config($app->functions->intval($mysql_db_server_id), 'server');
				$settings['main_database_host'] = $dbserver_config['ip_address'];
				$mysql_db_remote_access = 'y';
				$webserver_config = $app->getconf->get_server_config($app->functions->intval($websrv['server_id']), 'server');
				$mysql_db_remote_ips = $webserver_config['ip_address'];
			} else {
				/* I left this in place for a fallback that should NEVER! happen.
				 * if we reach this point it means that there is NO default db server for the client
				* AND the webserver has NO db service enabled.
				* We have to abort the aps installation here... so I added a return false
				* although this does not present any error message to the user.
				*/
				return false;
	
				/*$mysql_db_server_id = $websrv['server_id'];
				 $settings['main_database_host'] = 'localhost';
				$mysql_db_remote_access = 'n';
				$mysql_db_remote_ips = '';*/
			}
		}
		
		if (empty($settings['main_database_name'])) {
			//* Find a free db name for the app
			for($n = 1; $n <= 1000; $n++) {
				$mysql_db_name = ($dbname_prefix != '' ? $dbname_prefix.'aps'.$n : uniqid('aps'));
				$tmp = $app->db->queryOneRecord("SELECT count(database_id) as number FROM web_database WHERE database_name = ?", $mysql_db_name);
				if($tmp['number'] == 0) break;
			}
			$settings['main_database_name'] = $mysql_db_name;
		}
		if (empty($settings['main_database_login'])) {
			//* Find a free db username for the app
			for($n = 1; $n <= 1000; $n++) {
				$mysql_db_user = ($dbuser_prefix != '' ? $dbuser_prefix.'aps'.$n : uniqid('aps'));
				$tmp = $app->db->queryOneRecord("SELECT count(database_user_id) as number FROM web_database_user WHERE database_user = ?", $mysql_db_user);
				if($tmp['number'] == 0) break;
			}
			$settings['main_database_login'] = $mysql_db_user;
		}
		
		//* Create the mysql database user if not existing
		$tmp = $app->db->queryOneRecord("SELECT database_user_id FROM web_database_user WHERE database_user = ?", $settings['main_database_login']);
		if(!$tmp) {
			$tmppw = $app->db->queryOneRecord("SELECT PASSWORD(?) as `crypted`", $settings['main_database_password']);
			$insert_data = array("sys_userid" => $websrv['sys_userid'],
								 "sys_groupid" => $websrv['sys_groupid'],
								 "sys_perm_user" => 'riud',
								 "sys_perm_group" => $websrv['sys_perm_group'],
								 "sys_perm_other" => '',
								 "server_id" => 0,
								 "database_user" => $settings['main_database_login'],
								 "database_user_prefix" => $dbuser_prefix,
								 "database_password" => $tmppw['crypted']
								 );
			$mysql_db_user_id = $app->db->datalogInsert('web_database_user', $insert_data, 'database_user_id');
		}
		else $mysql_db_user_id = $tmp['database_user_id'];
		
		//* Create the mysql database if not existing
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as number FROM web_database WHERE database_name = ?", $settings['main_database_name']);
		if($tmp['number'] == 0) {
			$insert_data = array("sys_userid" => $websrv['sys_userid'],
								 "sys_groupid" => $websrv['sys_groupid'],
								 "sys_perm_user" => 'riud',
								 "sys_perm_group" => $websrv['sys_perm_group'],
								 "sys_perm_other" => '',
								 "server_id" => $mysql_db_server_id,
								 "parent_domain_id" => $websrv['domain_id'],
								 "type" => 'mysql',
								 "database_name" => $settings['main_database_name'],
								 "database_name_prefix" => $dbname_prefix,
								 "database_user_id" => $mysql_db_user_id,
								 "database_ro_user_id" => 0,
								 "database_charset" => '',
								 "remote_access" => $mysql_db_remote_access,
								 "remote_ips" => $mysql_db_remote_ips,
								 "backup_copies" => $websrv['backup_copies'],
								 "backup_format_web" => $websrv['backup_format_web'],
								 "backup_format_db" => $websrv['backup_format_db'],
								 "active" => 'y', 
								 "backup_interval" => $websrv['backup_interval']
								 );
			$app->db->datalogInsert('web_database', $insert_data, 'database_id');
		}
		
		return true;
	}
	
	/**
	 * Creates a new database record for the package instance and
	 * an install task
	 *
	 * @param $settings the settings to enter into the DB
	 * @param $packageid the PackageID
	 */
	public function createPackageInstance($settings, $packageid)
	{
		global $app;

		$app->uses('tools_sites');

		$webserver_id = 0;
		$websrv = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain = ?", $this->getMainDomain($settings['main_domain']));
		if(!empty($websrv)) $webserver_id = $websrv['server_id'];
		$customerid = $this->getCustomerIDFromDomain($this->getMainDomain($settings['main_domain']));

		if(empty($settings) || empty($webserver_id)) return false;

		//* Get server config of the web server
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($app->functions->intval($websrv["server_id"]), 'web');

		//* Set PHP mode to php-fcgi and enable suexec in website on apache servers / set PHP mode to PHP-FPM on nginx servers
		if($web_config['server_type'] == 'apache') {
			if(($websrv['php'] != 'fast-cgi' || $websrv['suexec'] != 'y') && $websrv['php'] != 'php-fpm') {
				$app->db->datalogUpdate('web_domain', array("php" => 'fast-cgi', "suexec" => 'y'), 'domain_id', $websrv['domain_id']);
			}
		} else {
			// nginx
			if($websrv['php'] != 'php-fpm' && $websrv['php'] != 'fast-cgi') {
				$app->db->datalogUpdate('web_domain', array("php" => 'php-fpm'), 'domain_id', $websrv['domain_id']);
			}
		}


		//* Create the MySQL database for the application if necessary
		$pkg = $app->db->queryOneRecord('SELECT * FROM aps_packages WHERE id = ?', $packageid);
		$metafile = $this->interface_pkg_dir.'/'.$pkg['path'].'/APP-META.xml';
		$sxe = $this->readInMetaFile($metafile);

		$db_id = parent::getXPathValue($sxe, '//db:id');
		if (!empty($db_id)) {
			// mysql-database-name is updated inside if not set already
			if (!$this->createDatabaseForPackageInstance($settings, $websrv)) return false;
		}
		
		//* Insert new package instance
		$insert_data = array(
			"sys_userid" => $websrv['sys_userid'],
			"sys_groupid" => $websrv['sys_groupid'],
			"sys_perm_user" => 'riud',
			"sys_perm_group" => $websrv['sys_perm_group'],
			"sys_perm_other" => '',
			"server_id" => $webserver_id,
			"customer_id" => $customerid,
			"package_id" => $packageid,
			"instance_status" => INSTANCE_PENDING
		);
		$InstanceID = $app->db->datalogInsert('aps_instances', $insert_data, 'id');

		//* Insert all package settings
		if(is_array($settings)) {
			foreach($settings as $key => $value) {
				$insert_data = array(
					"server_id" => $webserver_id,
					"instance_id" => $InstanceID,
					"name" => $key,
					"value" => $value
				);
				$app->db->datalogInsert('aps_instances_settings', $insert_data, 'id');
			}
		}

		//* Set package status to install afetr we inserted the settings
		$app->db->datalogUpdate('aps_instances', array("instance_status" => INSTANCE_INSTALL), 'id', $InstanceID);
		
		return $InstanceID;
	}

	/**
	 * Sets the status of an instance to "should be removed" and creates a
	 * datalog entry to give the ISPConfig server a real removal advice
	 *
	 * @param $instanceid the instance to delete
	 */
	public function deleteInstance($instanceid, $keepdatabase = false)
	{
		global $app;

		if (!$keepdatabase) {
			$sql = "SELECT web_database.database_id as database_id, web_database.database_user_id as `database_user_id` FROM aps_instances_settings, web_database WHERE aps_instances_settings.value = web_database.database_name AND aps_instances_settings.name = 'main_database_name' AND aps_instances_settings.instance_id = ? LIMIT 0,1";
			$tmp = $app->db->queryOneRecord($sql, $instanceid);
			if($tmp['database_id'] > 0) $app->db->datalogDelete('web_database', 'database_id', $tmp['database_id']);
	
			$database_user = $tmp['database_user_id'];
			$tmp = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `database_user_id` = ? OR `database_ro_user_id` = ?", $database_user, $database_user);
			if($tmp['cnt'] < 1) $app->db->datalogDelete('web_database_user', 'database_user_id', $database_user);
		}

		$app->db->datalogUpdate('aps_instances', array("instance_status" => INSTANCE_REMOVE), 'id', $instanceid);

	}

	/**
	 * Read the settings to be filled when installing
	 *
	 * @param $id the internal ID of the package
	 * @return array
	 */
	public function getPackageSettings($id)
	{
		global $app;

		$pkg = $app->db->queryOneRecord('SELECT * FROM aps_packages WHERE id = ?', $id);

		// Load in meta file if existing and register its namespaces
		$metafile = $this->interface_pkg_dir.'/'.$pkg['path'].'/APP-META.xml';
		if(!file_exists($metafile))
			return array('error' => 'The metafile for '.$settings['Name'].' couldn\'t be found');

		$sxe = $this->readInMetaFile($metafile);

		$groupsettings = parent::getXPathValue($sxe, '//settings/group/setting', true);
		if(empty($groupsettings)) return array();

		$settings = array();
		foreach($groupsettings as $setting)
		{
			$setting_id = strval($setting['id']);

			if($setting['type'] == 'string' || $setting['type'] == 'email' || $setting['type'] == 'integer'
				|| $setting['type'] == 'float' || $setting['type'] == 'domain-name')
			{
				$settings[] = array('SettingID' => $setting_id,
					'SettingName' => $setting->name,
					'SettingDescription' => $setting->description,
					'SettingType' => $setting['type'],
					'SettingInputType' => 'string',
					'SettingDefaultValue' => strval($setting['default-value']),
					'SettingRegex' => $setting['regex'],
					'SettingMinLength' => $setting['min-length'],
					'SettingMaxLength' => $setting['max-length']);
			}
			else if($setting['type'] == 'password')
				{
					$settings[] = array('SettingID' => $setting_id,
						'SettingName' => $setting->name,
						'SettingDescription' => $setting->description,
						'SettingType' => 'password',
						'SettingInputType' => 'password',
						'SettingDefaultValue' => '',
						'SettingRegex' => $setting['regex'],
						'SettingMinLength' => $setting['min-length'],
						'SettingMaxLength' => $setting['max-length']);
				}
			else if($setting['type'] == 'boolean')
				{
					$settings[] = array('SettingID' => $setting_id,
						'SettingName' => $setting->name,
						'SettingDescription' => $setting->description,
						'SettingType' => 'boolean',
						'SettingInputType' => 'checkbox',
						'SettingDefaultValue' => strval($setting['default-value']));
				}
			else if($setting['type'] == 'enum')
				{
					$choices = array();
					foreach($setting->choice as $choice)
					{
						$choices[] = array('EnumID' => strval($choice['id']),
							'EnumName' => $choice->name);
					}
					$settings[] = array('SettingID' => $setting_id,
						'SettingName' => $setting->name,
						'SettingDescription' => $setting->description,
						'SettingType' => 'enum',
						'SettingInputType' => 'select',
						'SettingDefaultValue' => strval($setting['default-value']),
						'SettingChoices' => $choices);
				}
		}

		return $settings;
	}



	/**
	 * Validates the user input according to the settings array and
	 * delivers errors if occurring
	 *
	 * @param $input the user $_POST array
	 * @param $pkg_details the package details
	 * @param $settings the package settings array
	 * @return array in this structure:
	 *               array(2) {
	 *                  ["input"]=> ...
	 *                  ["errors"]=> ...
	 *               }
	 */
	public function validateInstallerInput($postinput, $pkg_details, $domains, $settings = array())
	{
		global $app;

		$ret = array();
		$input = array();
		$error = array();

		// Main domain (obligatory)
		if(isset($postinput['main_domain']))
		{
			if(!in_array($postinput['main_domain'], $domains)) $error[] = $app->lng('error_main_domain');
			else $input['main_domain'] = $postinput['main_domain'];
		}
		else $error[] = $app->lng('error_main_domain');

		if(isset($postinput['admin_password']))
		{
			$app->uses('validate_password');

			$passwordError = $app->validate_password->password_check('', $postinput['admin_password'], '');
			if ($passwordError) {
				$error[] = $passwordError;
			}
		}

		// Main location (not obligatory but must be supplied)
		if(isset($postinput['main_location']))
		{
			$temp_errstr = '';
			// It can be empty but if the user did write something, check it
			$userinput = false;
			if(strlen($postinput['main_location']) > 0) $userinput = true;

			// Filter invalid input slashes (twice!)
			$main_location = $this->secureLocation($postinput['main_location']);
			$main_location = $this->secureLocation($main_location);
			// Only allow digits, words, / and -
			$main_location = preg_replace("/[^\d\w\/\-]/i", "", $main_location);
			if($userinput && (strlen($main_location) == 0)) $temp_errstr = $app->lng('error_inv_main_location');

			// Find out document_root and make sure no apps are installed twice to one location
			if(in_array($postinput['main_domain'], $domains))
			{
				$docroot = $app->db->queryOneRecord("SELECT document_root, web_folder FROM web_domain
                    WHERE domain = ?", $this->getMainDomain($postinput['main_domain']));
				if(trim($docroot['web_folder']) == '') {
					$new_path = $docroot['document_root'];
				} else {
					$new_path = $docroot['document_root'] . '/' . $docroot['web_folder'];
				}
				if(substr($new_path, -1) != '/') $new_path .= '/';
				$new_path .= $main_location;

				// Get the $customerid which belongs to the selected domain
				$customerid = $this->getCustomerIDFromDomain($this->getMainDomain($postinput['main_domain']));

				// First get all domains used for an install, then their loop them
				// and get the corresponding document roots as well as the defined
				// locations. If an existing doc_root + location matches with the
				// new one -> error
				$instance_domains = $app->db->queryAllRecords("SELECT instance_id, s.value AS domain
                    FROM aps_instances AS i, aps_instances_settings AS s
                    WHERE i.id = s.instance_id AND s.name = 'main_domain'
                        AND i.customer_id = ?", $customerid);
				for($i = 0; $i < count($instance_domains); $i++)
				{
					$used_path = '';

					$doc_root = $app->db->queryOneRecord("SELECT document_root FROM web_domain
                        WHERE domain = ?", $instance_domains[$i]['domain']);

					// Probably the domain settings were changed later, so make sure the doc_root
					// is not empty for further validation
					if(!empty($doc_root))
					{
						$used_path = $doc_root['document_root'];
						if(substr($used_path, -1) != '/') $used_path .= '/';

						$location_for_domain = $app->db->queryOneRecord("SELECT value
                            FROM aps_instances_settings WHERE name = 'main_location'
                            AND instance_id = ?", $instance_domains[$i]['instance_id']);

						// The location might be empty but the DB return must not be false!
						if($location_for_domain) $used_path .= $location_for_domain['value'];

						// If user is trying to install into exactly the same path, give an error
						if($new_path == $used_path)
						{
							$temp_errstr = $app->lng('error_used_location');
							break;
						}

						// If the new path is _below_ an existing path, give an error because the
						// installation will delete the files of the existing APS installation
						if (mb_substr($used_path, 0, mb_strlen($new_path)) === $new_path) {
							$temp_errstr = $app->lng('error_used_location');
							break;
						}

						// If the new path is _within_ an existing path, give an error. Even if
						// installation would proceed fine in theory, deleting the "lower" package
						// in the future would also inadvertedly delete the "nested" package
						if (mb_substr($new_path, 0, mb_strlen($used_path)) === $used_path) {
							$temp_errstr = $app->lng('error_used_location');
							break;
						}
					}
				}
			}
			else $temp_errstr = $app->lng('error_main_domain');

			if($temp_errstr == '') $input['main_location'] = htmlspecialchars($main_location);
			else $error[] = $temp_errstr;
		}
		else $error[] = $app->lng('error_no_main_location');

		// License (the checkbox must be set)
		if(isset($pkg_details['License need agree'])
			&& $pkg_details['License need agree'] == 'true')
		{
			if(isset($postinput['license']) && $postinput['license'] == 'on') $input['license'] = 'true';
			else $error[] = $app->lng('error_license_agreement');
		}

		// Database
		if(isset($pkg_details['Requirements Database'])
			&& $pkg_details['Requirements Database'] != '')
		{
			if (isset($postinput['main_database_host'])) $input['main_database_host'] = $postinput['main_database_host'];
			if (isset($postinput['main_database_name'])) $input['main_database_name'] = $postinput['main_database_name'];
			if (isset($postinput['main_database_login'])) $input['main_database_login'] = $postinput['main_database_login'];
			
			if(isset($postinput['main_database_password']))
			{
				if($postinput['main_database_password'] == '') $error[] = $app->lng('error_no_database_pw');
				else if(strlen($postinput['main_database_password']) > 8)
						$input['main_database_password'] = htmlspecialchars($postinput['main_database_password']);
					else $error[] = $app->lng('error_short_database_pw');
			}
			else $error[] = $app->lng('error_no_database_pw');
		}

		// Validate the package settings
		foreach($settings as $setting)
		{
			$temp_errstr = '';
			$setting_id = strval($setting['SettingID']);

			// We assume that every setting must be set
			if((isset($postinput[$setting_id]) && ($postinput[$setting_id] != ''))
				|| ($setting['SettingType'] == 'boolean'))
			{
				if($setting['SettingType'] == 'string' || $setting['SettingType'] == 'password')
				{
					if($app->functions->intval($setting['SettingMinLength'], true) != 0
						&& strlen($postinput[$setting_id]) < $app->functions->intval($setting['SettingMinLength'], true))
						$temp_errstr = sprintf($app->lng('error_short_value_for'), $setting['setting_name']);

					if($app->functions->intval($setting['SettingMaxLength'], true) != 0
						&& strlen($postinput[$setting_id]) > $app->functions->intval($setting['SettingMaxLength'], true))
						$temp_errstr = sprintf($app->lng('error_long_value_for'), $setting['setting_name']);

					if(isset($setting['SettingRegex'])
						&& !preg_match("/".$setting['SettingRegex']."/", $postinput[$setting_id]))
						$temp_errstr = sprintf($app->lng('error_inv_value_for'), $setting['setting_name']);
				}
				else if($setting['SettingType'] == 'email')
					{
						if(filter_var(strtolower($postinput[$setting_id]), FILTER_VALIDATE_EMAIL) === false)
							$temp_errstr = sprintf($app->lng('error_inv_email_for'), $setting['setting_name']);
					}
				else if($setting['SettingType'] == 'domain-name')
					{
						if(!preg_match("^(http|https)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&%\$#\=~_\-]+))*$",
								$postinput[$setting_id]))
							$temp_errstr = sprintf($app->lng('error_inv_domain_for'), $setting['setting_name']);
					}
				else if($setting['SettingType'] == 'integer')
					{
						if(filter_var($postinput[$setting_id], FILTER_VALIDATE_INT) === false)
							$temp_errstr = sprintf($app->lng('error_inv_integer_for'), $setting['setting_name']);
					}
				else if($setting['SettingType'] == 'float')
					{
						if(filter_var($postinput[$setting_id], FILTER_VALIDATE_FLOAT) === false)
							$temp_errstr = sprintf($app->lng('error_inv_float_for'), $setting['setting_name']);
					}
				else if($setting['SettingType'] == 'boolean')
					{
						// If we have a boolean value set, it must be either true or false
						if(!isset($postinput[$setting_id])) $postinput[$setting_id] = 'false';
						else if(isset($postinput[$setting_id]) && $postinput[$setting_id] != 'true')
								$postinput[$setting_id] = 'true';
					}
				else if($setting['SettingType'] == 'enum')
					{
						$found = false;
						for($i = 0; $i < count($setting['SettingChoices']); $i++)
						{
							if($setting['SettingChoices'][$i]['EnumID'] == $postinput[$setting_id])
								$found = true;
						}
						if(!$found) $temp_errstr = sprintf($app->lng('error_inv_value_for'), $setting['SettingName']);
					}

				if($temp_errstr == '') $input[$setting_id] = $postinput[$setting_id];
				else $error[] = $temp_errstr;
			}
			else $error[] = sprintf($app->lng('error_no_value_for'), $setting['SettingName']);
		}

		$ret['input'] = $input;
		$ret['error'] = array_unique($error);

		return $ret;
	}



	/**
	 * Read the metadata of a package and returns some content
	 *
	 * @param $id the internal ID of the package
	 * @return array
	 */
	public function getPackageDetails($id)
	{
		global $app;

		$pkg = $app->db->queryOneRecord('SELECT * FROM aps_packages WHERE id = ?', $id);

		// Load in meta file if existing and register its namespaces
		$metafile = $this->interface_pkg_dir.'/'.$pkg['path'].'/APP-META.xml';
		if(!file_exists($metafile))
			return array('error' => 'The metafile for '.$pkg['name'].' couldn\'t be found');

		$metadata = file_get_contents($metafile);
		$metadata = str_replace("xmlns=", "ns=", $metadata);
		$sxe = new SimpleXMLElement($metadata);
		$namespaces = $sxe->getDocNamespaces(true);
		foreach($namespaces as $ns => $url) $sxe->registerXPathNamespace($ns, $url);

		$pkg['Summary'] = htmlspecialchars(parent::getXPathValue($sxe, '//summary'));
		$pkg['Homepage'] = parent::getXPathValue($sxe, '//homepage');
		$pkg['Description'] = nl2br(htmlspecialchars(trim(parent::getXPathValue($sxe, '//description'))));
		$pkg['Config script'] = strtoupper(parent::getXPathValue($sxe, '//configuration-script-language'));
		$installed_size = parent::getXPathValue($sxe, '//installed-size');
		$pkg['Installed Size'] = (!empty($installed_size)) ? parent::convertSize((int)$installed_size) : '';

		// License
		$pkg['License need agree'] = parent::getXPathValue($sxe, '//license/@must-accept');
		$pkg['License name'] = parent::getXPathValue($sxe, '//license/text/name'); // might be empty
		$pkg['License type'] = 'file'; // default type
		$pkg['License content'] = ''; // default license filename on local system
		$license_url = parent::getXPathValue($sxe, '//license/text/url');
		if(!empty($license_url))
		{
			$pkg['License type'] = 'url';
			$pkg['License content'] = htmlspecialchars($license_url);
		}
		else
		{
			$lic = @file_get_contents($this->interface_pkg_dir.'/'.$pkg['path'].'/LICENSE');
			$pkg['License content'] = htmlentities($lic, ENT_QUOTES, 'ISO-8859-1');
		}

		// Languages
		$languages = parent::getXPathValue($sxe, '//languages/language', true);
		$pkg['Languages'] = (is_array($languages)) ? implode(' ', $languages) : '';

		// Icon
		$icon = parent::getXPathValue($sxe, '//icon/@path');
		if(!empty($icon))
		{
			// Using parse_url() to filter malformed URLs
			$path = dirname(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH)).'/'.
				basename($this->interface_pkg_dir).'/'.$pkg['path'].'/'.basename((string)$icon);
			// nginx: if $_SERVER['PHP_SELF'] is doubled, remove /sites/aps_packagedetails_show.php from beginning of path
			$path = preg_replace('@^/sites/aps_packagedetails_show.php(.*)@', '$1', $path);
			$pkg['Icon'] = $path;
		}
		else $pkg['Icon'] = '';

		// Screenshots
		$screenshots = parent::getXPathValue($sxe, '//screenshot', true);
		if(!empty($screenshots))
		{
			foreach($screenshots as $screen)
			{
				// Using parse_url() to filter malformed URLs
				$path = dirname(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH)).'/'.
					basename($this->interface_pkg_dir).'/'.$pkg['path'].'/'.basename((string)$screen['path']);
				// nginx: if $_SERVER['PHP_SELF'] is doubled, remove /sites/aps_packagedetails_show.php from beginning of path
				$path = preg_replace('@^/sites/aps_packagedetails_show.php(.*)@', '$1', $path);

				$pkg['Screenshots'][] = array('ScreenPath' => $path,
					'ScreenDescription' => htmlspecialchars(trim((string)$screen->description)));
			}
		}
		else $pkg['Screenshots'] = ''; // if no screenshots are available, set the variable though

		// Changelog
		$changelog = parent::getXPathValue($sxe, '//changelog/version', true);
		if(!empty($changelog))
		{
			foreach($changelog as $change)
			{
				$entries = array();
				foreach($change->entry as $entry) $entries[] = htmlspecialchars(trim((string)$entry));

				$pkg['Changelog'][] = array('ChangelogVersion' => (string)$change['version'],
					'ChangelogDescription' => implode('<br />', $entries));
			}
		}

		else $pkg['Changelog'] = '';

		// PHP extensions
		$php_extensions = parent::getXPathValue($sxe, '//php:extension', true);
		$php_ext = '';
		if(!empty($php_extensions))
		{
			foreach($php_extensions as $extension)
			{
				if(strtolower($extension) == 'php') continue;
				$php_ext .= $extension.' ';
			}
		}
		$pkg['Requirements PHP extensions'] = trim($php_ext);

		// PHP bool options
		$pkg['Requirements PHP settings'] = array();
		$php_bool_options = array('allow-url-fopen', 'file-uploads', 'magic-quotes-gpc',
			'register-globals', 'safe-mode', 'short-open-tag');
		foreach($php_bool_options as $option)
		{
			$value = parent::getXPathValue($sxe, '//php:'.$option);
			if(!empty($value))
			{
				$option = str_replace('-', '_', $option);
				$value = str_replace(array('false', 'true'), array('off', 'on'), $value);
				$pkg['Requirements PHP settings'][] = array('PHPSettingName' => $option,
					'PHPSettingValue' => $value);
			}
		}

		// PHP integer value settings
		$memory_limit = parent::getXPathValue($sxe, '//php:memory-limit');
		if(!empty($memory_limit))
			$pkg['Requirements PHP settings'][] = array('PHPSettingName' => 'memory_limit',
				'PHPSettingValue' => parent::convertSize((int)$memory_limit));

		$max_exec_time = parent::getXPathValue($sxe, '//php:max-execution-time');
		if(!empty($max_exec_time))
			$pkg['Requirements PHP settings'][] = array('PHPSettingName' => 'max-execution-time',
				'PHPSettingValue' => $max_exec_time);

		$post_max_size = parent::getXPathValue($sxe, '//php:post-max-size');
		if(!empty($post_max_size))
			$pkg['Requirements PHP settings'][] = array('PHPSettingName' => 'post_max_size',
				'PHPSettingValue' => parent::convertSize((int)$post_max_size));

		// Get supported PHP versions
		$pkg['Requirements Supported PHP versions'] = '';
		$php_min_version = parent::getXPathValue($sxe, '//php:version/@min');
		$php_max_not_including = parent::getXPathValue($sxe, '//php:version/@max-not-including');
		if(!empty($php_min_version) && !empty($php_max_not_including))
			$pkg['Requirements Supported PHP versions'] = $php_min_version.' - '.$php_max_not_including;
		else if(!empty($php_min_version))
				$pkg['Requirements Supported PHP versions'] = '> '.$php_min_version;
			else if(!empty($php_max_not_including))
					$pkg['Requirements Supported PHP versions'] = '< '.$php_min_version;

				// Database
				$db_id = parent::getXPathValue($sxe, '//db:id');
			$db_server_type = parent::getXPathValue($sxe, '//db:server-type');
		$db_min_version = parent::getXPathValue($sxe, '//db:server-min-version');
		if(!empty($db_id))
		{
			$db_server_type = str_replace('postgresql', 'PostgreSQL', $db_server_type);
			$db_server_type = str_replace('microsoft:sqlserver', 'MSSQL', $db_server_type);
			$db_server_type = str_replace('mysql', 'MySQL', $db_server_type);

			$pkg['Requirements Database'] = $db_server_type;
			if(!empty($db_min_version)) $pkg['Requirements Database'] .= ' > '.$db_min_version;
		}
		else $pkg['Requirements Database'] = '';

		return $pkg;
	}

}

?>
