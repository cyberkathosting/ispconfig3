<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/database.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_database')) {
				$app->error($app->tform->wordbook["limit_database_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_database')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_database_txt"]);
			}
		} else {
			$settings = $app->getconf->get_global_config('sites');
			$app->tform->formDef['tabs']['database']['fields']['server_id']['default'] = intval($settings['default_dbserver']);
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf, $interfaceConf;

		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT db_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE server_id IN ?", explode(',', $client['db_servers']));

			$only_one_server = count($tmp) === 1;
			$app->tpl->setVar('only_one_server', $only_one_server);

			if ($only_one_server) {
				$app->tpl->setVar('server_id_value', $tmp[0]['server_id']);
			}

			foreach ($tmp as $db_server) {
				$options_db_servers .= '<option value="'.$db_server['server_id'].'"'.($this->id > 0 && $this->dataRecord["server_id"] == $db_server['server_id'] ? ' selected="selected"' : '').'>'.$app->functions->htmlentities($db_server['server_name']).'</option>';
			}

			$app->tpl->setVar("server_id", $options_db_servers);
			unset($tmp);

		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, limit_web_domain, db_servers, contact_name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE server_id IN ?", explode(',', $client['db_servers']));

			$only_one_server = count($tmp) === 1;
			$app->tpl->setVar('only_one_server', $only_one_server);

			if ($only_one_server) {
				$app->tpl->setVar('server_id_value', $tmp[0]['server_id']);
			}

			foreach ($tmp as $db_server) {
				$options_db_servers .= '<option value="'.$db_server['server_id'].'"'.($this->id > 0 && $this->dataRecord["server_id"] == $db_server['server_id'] ? ' selected="selected"' : '').'>'.$app->functions->htmlentities($db_server['server_name']).'</option>';
			}

			$app->tpl->setVar("server_id", $options_db_servers);
			unset($tmp);

		} else {

			// The user is admin
			if($this->id > 0) {
				$server_id = $this->dataRecord["server_id"];
			} else {
				// Get the first server ID
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = 1 ORDER BY server_name LIMIT 0,1");
				$server_id = $tmp['server_id'];
			}

		}

		/*
		 * If the names are restricted -> remove the restriction, so that the
		 * data can be edited
		 */

		//* Get the database name and database user prefix
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$dbname_prefix = $app->tools_sites->replacePrefix($global_config['dbname_prefix'], $this->dataRecord);

		if ($this->dataRecord['database_name'] != ""){
			/* REMOVE the restriction */
			$app->tpl->setVar("database_name", $app->tools_sites->removePrefix($this->dataRecord['database_name'], $this->dataRecord['database_name_prefix'], $dbname_prefix), true);
		}

		if($this->dataRecord['database_name'] == "") {
			$app->tpl->setVar("database_name_prefix", $dbname_prefix, true);
		} else {
			$app->tpl->setVar("database_name_prefix", $app->tools_sites->getPrefix($this->dataRecord['database_name_prefix'], $dbname_prefix, $global_config['dbname_prefix']), true);
		}

		if($this->id > 0) {
			//* we are editing a existing record
			$edit_disabled = @($_SESSION["s"]["user"]["typ"] == 'admin')? 0 : 1; //* admin can change the database-name
			$app->tpl->setVar("edit_disabled", $edit_disabled);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"], true);
			$app->tpl->setVar("database_charset_value", $this->dataRecord["database_charset"], true);
			$app->tpl->setVar("limit_database_quota", $this->dataRecord["database_quota"], true);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r'), @$this->dataRecord["parent_domain_id"]);
		if(!$parent_domain || $parent_domain['domain_id'] != @$this->dataRecord['parent_domain_id']) $app->tform->errorMessage .= $app->tform->lng("no_domain_perm");

		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT db_servers, limit_database, limit_database_quota, parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.groupid = ?", $client_group_id);

			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_database WHERE database_id = ?", $app->functions->intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
				//* Check client quota
				if ($client['limit_database_quota'] >= 0) {
					//* get the database prefix
					$app->uses('getconf,tools_sites');
					$global_config = $app->getconf->get_global_config('sites');
					$dbname_prefix = $app->tools_sites->replacePrefix($global_config['dbname_prefix'], $this->dataRecord);
					//* get quota from other databases
					$tmp = $app->db->queryOneRecord("SELECT sum(database_quota) as db_quota FROM web_database WHERE sys_groupid = ? AND database_name <> ?", $client_group_id, $dbname_prefix.$this->dataRecord['database_name']);
					$used_quota = $app->functions->intval($tmp['db_quota']);
					$new_db_quota = $app->functions->intval($this->dataRecord["database_quota"]);
					if(($used_quota + $new_db_quota > $client['limit_database_quota']) || ($new_db_quota < 0 && $client['limit_database_quota'] >= 0)) {
						$max_free_quota = floor($client['limit_database_quota'] - $used_quota);
						if($max_free_quota < 0) {
							$max_free_quota = 0;
						}
						$app->tform->errorMessage .= $app->tform->lng("limit_database_quota_free_txt").": ".$max_free_quota." MB<br>";
						$this->dataRecord['database_quota'] = $max_free_quota;
					}
					unset($tmp);
					unset($global_config);
					unset($dbname_prefix);
				}

				if($client['parent_client_id'] > 0) {
					// Get the limits of the reseller
					$reseller = $app->db->queryOneRecord("SELECT limit_database, limit_database_quota FROM client WHERE client_id = ?", $client['parent_client_id']);

					//* Check the website quota of the client
					if ($reseller['limit_database_quota'] >= 0) {
						//* get the database prefix
						$app->uses('getconf,tools_sites');
						$global_config = $app->getconf->get_global_config('sites');
						$dbname_prefix = $app->tools_sites->replacePrefix($global_config['dbname_prefix'], $this->dataRecord);
						//* get quota from other databases
						$tmp = $app->db->queryOneRecord("SELECT sum(database_quota) as db_quota FROM web_database, sys_group, client WHERE web_database.sys_groupid=sys_group.groupid AND sys_group.client_id=client.client_id AND ? IN (client.parent_client_id, client.client_id) AND database_name <> ?", $client['parent_client_id'], $dbname_prefix.$this->dataRecord['database_name']);

						$used_quota = $app->functions->intval($tmp['db_quota']);
						$new_db_quota = $app->functions->intval($this->dataRecord["database_quota"]);
						if(($used_quota + $new_db_quota > $reseller["limit_database_quota"]) || ($new_db_quota < 0 && $reseller["limit_database_quota"] >= 0)) {
							$max_free_quota = floor($reseller["limit_database_quota"] - $used_quota);
							if($max_free_quota < 0) $max_free_quota = 0;
							$app->tform->errorMessage .= $app->tform->lng("limit_database_quota_free_txt").": ".$max_free_quota." MB<br>";
							$this->dataRecord["database_quota"] = $max_free_quota;
						}
						unset($tmp);
						unset($global_config);
						unset($dbname_prefix);
					}
				}
				// When the record is inserted
			} else {
				$client['db_servers_ids'] = explode(',', $client['db_servers']);

				// Check if chosen server is in authorized servers for this client
				if (!(is_array($client['db_servers_ids']) && in_array($this->dataRecord["server_id"], $client['db_servers_ids'])) && $_SESSION["s"]["user"]["typ"] != 'admin') {
					$app->error($app->tform->wordbook['error_not_allowed_server_id']);
				}

				// Check if the user may add another database
				if($client["limit_database"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(database_id) as number FROM web_database WHERE sys_groupid = ?", $client_group_id);
					if($tmp["number"] >= $client["limit_database"]) {
						$app->error($app->tform->wordbook["limit_database_txt"]);
					}
				}

				//* Check client quota
				if ($client['limit_database_quota'] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT sum(database_quota) as db_quota FROM web_database WHERE sys_groupid = ?", $client_group_id);
					$db_quota = $tmp['db_quota'];
					$new_db_quota = $app->functions->intval($this->dataRecord["database_quota"]);
					if(($db_quota + $new_db_quota > $client['limit_database_quota']) || ($new_db_quota < 0 && $client['limit_database_quota'] >= 0)) {
						$max_free_quota = floor($client['limit_database_quota'] - $db_quota);
						if($max_free_quota < 0) $max_free_quota = 0;
						$app->tform->errorMessage .= $app->tform->lng("limit_database_quota_free_txt").": ".$max_free_quota." MB<br>";
						$this->dataRecord['database_quota'] = $max_free_quota;
					}
					unset($tmp);
				}
			}
		} else {
			// check if client of database parent domain is client of db user!
			$web_group = $app->db->queryOneRecord("SELECT sys_groupid FROM web_domain WHERE domain_id = ?", $this->dataRecord['parent_domain_id']);
			if($this->dataRecord['database_user_id']) {
				$group = $app->db->queryOneRecord("SELECT sys_groupid FROM web_database_user WHERE database_user_id = ?", $this->dataRecord['database_user_id']);
				if($group['sys_groupid'] != $web_group['sys_groupid']) {
					$app->error($app->tform->wordbook['database_client_differs_txt']);
				}
			}
			if($this->dataRecord['database_ro_user_id']) {
				$group = $app->db->queryOneRecord("SELECT sys_groupid FROM web_database_user WHERE database_user_id = ?", $this->dataRecord['database_ro_user_id']);
				if($group['sys_groupid'] != $web_group['sys_groupid']) {
					$app->error($app->tform->wordbook['database_client_differs_txt']);
				}
			}
		}


		parent::onSubmit();
	}

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;

		//* Site shall not be empty
		if($this->dataRecord['parent_domain_id'] == 0) $app->tform->errorMessage .= $app->tform->lng("database_site_error_empty").'<br />';

		//* Get the database name and database user prefix
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$dbname_prefix = $app->tools_sites->replacePrefix($global_config['dbname_prefix'], $this->dataRecord);

		//* Prevent that the database name and charset is changed
		$old_record = $app->tform->getDataRecord($this->id);
		$dbname_prefix = $app->tools_sites->getPrefix($old_record['database_name_prefix'], $dbname_prefix);
		$this->dataRecord['database_name_prefix'] = $dbname_prefix;

		//* Only admin can change the database name
		if ($_SESSION["s"]["user"]["typ"] != 'admin') {
			if($old_record["database_name"] != $dbname_prefix . $this->dataRecord["database_name"]) {
				$app->tform->errorMessage .= $app->tform->wordbook["database_name_change_txt"].'<br />';
			}
		}
		if($old_record["database_charset"] != $this->dataRecord["database_charset"]) {
			$app->tform->errorMessage .= $app->tform->wordbook["database_charset_change_txt"].'<br />';
		}

		if(!$this->dataRecord['database_user_id']) {
			$app->tform->errorMessage .= $app->tform->wordbook["database_user_missing_txt"].'<br />';
		}

		//* Database username and database name shall not be empty
		if($this->dataRecord['database_name'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_name_error_empty"].'<br />';

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			if($old_record["server_id"] != $this->dataRecord["server_id"]) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Server can not be changed.');
				$this->dataRecord["server_id"] = $rec['server_id'];
			}
		}
		unset($old_record);

		if(strlen($dbname_prefix . $this->dataRecord['database_name']) > 64) $app->tform->errorMessage .= str_replace('{db}', $dbname_prefix . $this->dataRecord['database_name'], $app->tform->wordbook["database_name_error_len"]).'<br />';

		//* Check database name and user against blacklist
		$dbname_blacklist = array($conf['db_database'], 'mysql');
		if(in_array($dbname_prefix . $this->dataRecord['database_name'], $dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database name not allowed.').'<br />';
		}

		if ($app->tform->errorMessage == ''){
			/* restrict the names if there is no error */
			/* crop user and db names if they are too long -> mysql: user: 16 chars / db: 64 chars */
			$this->dataRecord['database_name'] = substr($dbname_prefix . $this->dataRecord['database_name'], 0, 64);
		}

		//* Check for duplicates
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as dbnum FROM web_database WHERE database_name = ? AND server_id = ? AND database_id != ?", $this->dataRecord['database_name'], $this->dataRecord["server_id"], $this->id);
		if($tmp['dbnum'] > 0) $app->tform->errorMessage .= $app->lng('database_name_error_unique').'<br />';

		// get the web server ip (parent domain)
		$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ?", $this->dataRecord['parent_domain_id']);
		if($tmp['server_id'] && $tmp['server_id'] != $this->dataRecord['server_id']) {
			// we need remote access rights for this server, so get it's ip address
			$server_config = $app->getconf->get_server_config($tmp['server_id'], 'server');
			if($server_config['ip_address']!='') {
				if($this->dataRecord['remote_access'] != 'y'){
					$this->dataRecord['remote_ips'] = $server_config['ip_address'];
					$this->dataRecord['remote_access'] = 'y';
				} else {
					if($this->dataRecord['remote_ips'] != ''){
						if(preg_match('/(^|,)' . preg_quote($server_config['ip_address'], '/') . '(,|$)/', $this->dataRecord['remote_ips']) == false) {
							$this->dataRecord['remote_ips'] .= ',' . $server_config['ip_address'];
						}
						$tmp = preg_split('/\s*,\s*/', $this->dataRecord['remote_ips']);
						$tmp = array_unique($tmp);
						$this->dataRecord['remote_ips'] = implode(',', $tmp);
						unset($tmp);
					}
				}
			}
		}
		
		if ($app->tform->errorMessage == '') {
			// force update of the used database user
			if($this->dataRecord['database_user_id']) {
				$user_old_rec = $app->db->queryOneRecord('SELECT * FROM `web_database_user` WHERE `database_user_id` = ?', $this->dataRecord['database_user_id']);
				if($user_old_rec) {
					$user_new_rec = $user_old_rec;
					$user_new_rec['server_id'] = $this->dataRecord['server_id'];
					$app->db->datalogSave('web_database_user', 'UPDATE', 'database_user_id', $this->dataRecord['database_user_id'], $user_old_rec, $user_new_rec);
				}
			}
			if($this->dataRecord['database_ro_user_id']) {
				$user_old_rec = $app->db->queryOneRecord('SELECT * FROM `web_database_user` WHERE `database_user_id` = ?', $this->dataRecord['database_ro_user_id']);
				if($user_old_rec) {
					$user_new_rec = $user_old_rec;
					$user_new_rec['server_id'] = $this->dataRecord['server_id'];
					$app->db->datalogSave('web_database_user', 'UPDATE', 'database_user_id', $this->dataRecord['database_ro_user_id'], $user_old_rec, $user_new_rec);
				}
			}
		}

		parent::onBeforeUpdate();
	}

	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;

		//* Site shell not be empty
		if($this->dataRecord['parent_domain_id'] == 0) $app->tform->errorMessage .= $app->tform->lng("database_site_error_empty").'<br />';

		//* Database username and database name shall not be empty
		if($this->dataRecord['database_name'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_name_error_empty"].'<br />';

		//* Get the database name and database user prefix
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$dbname_prefix = $app->tools_sites->replacePrefix($global_config['dbname_prefix'], $this->dataRecord);
		$this->dataRecord['database_name_prefix'] = $dbname_prefix;

		if(strlen($dbname_prefix . $this->dataRecord['database_name']) > 64) $app->tform->errorMessage .= str_replace('{db}', $dbname_prefix . $this->dataRecord['database_name'], $app->tform->wordbook["database_name_error_len"]).'<br />';

		//* Check database name and user against blacklist
		$dbname_blacklist = array($conf['db_database'], 'mysql');
		if(in_array($dbname_prefix . $this->dataRecord['database_name'], $dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database name not allowed.').'<br />';
		}

		/* restrict the names */
		/* crop user and db names if they are too long -> mysql: user: 16 chars / db: 64 chars */
		if ($app->tform->errorMessage == ''){
			$this->dataRecord['database_name'] = substr($dbname_prefix . $this->dataRecord['database_name'], 0, 64);
		}

		//* Check for duplicates
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as dbnum FROM web_database WHERE database_name = ? AND server_id = ?", $this->dataRecord['database_name'], $this->dataRecord["server_id"]);
		if($tmp['dbnum'] > 0) $app->tform->errorMessage .= $app->tform->lng('database_name_error_unique').'<br />';

		// get the web server ip (parent domain)
		$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ?", $this->dataRecord['parent_domain_id']);
		if($tmp['server_id'] && $tmp['server_id'] != $this->dataRecord['server_id']) {
			// we need remote access rights for this server, so get it's ip address
			$server_config = $app->getconf->get_server_config($tmp['server_id'], 'server');
			if($server_config['ip_address']!='') {
				if($this->dataRecord['remote_access'] != 'y'){
					$this->dataRecord['remote_ips'] = $server_config['ip_address'];
					$this->dataRecord['remote_access'] = 'y';
				} else {
					if($this->dataRecord['remote_ips'] != ''){
						if(preg_match('/(^|,)' . preg_quote($server_config['ip_address'], '/') . '(,|$)/', $this->dataRecord['remote_ips']) == false) {
							$this->dataRecord['remote_ips'] .= ',' . $server_config['ip_address'];
						}
						$tmp = preg_split('/\s*,\s*/', $this->dataRecord['remote_ips']);
						$tmp = array_unique($tmp);
						$this->dataRecord['remote_ips'] = implode(',', $tmp);
						unset($tmp);
					}
				}
			}
		}

		if ($app->tform->errorMessage == '') {
			// force update of the used database user
			if($this->dataRecord['database_user_id']) {
				$user_old_rec = $app->db->queryOneRecord('SELECT * FROM `web_database_user` WHERE `database_user_id` = ?', $this->dataRecord['database_user_id']);
				if($user_old_rec) {
					$user_new_rec = $user_old_rec;
					$user_new_rec['server_id'] = $this->dataRecord['server_id'];
					$app->db->datalogSave('web_database_user', 'UPDATE', 'database_user_id', $this->dataRecord['database_user_id'], $user_old_rec, $user_new_rec);
				}
			}
			if($this->dataRecord['database_ro_user_id']) {
				$user_old_rec = $app->db->queryOneRecord('SELECT * FROM `web_database_user` WHERE `database_user_id` = ?', $this->dataRecord['database_ro_user_id']);
				if($user_old_rec) {
					$user_new_rec = $user_old_rec;
					$user_new_rec['server_id'] = $this->dataRecord['server_id'];
					$app->db->datalogSave('web_database_user', 'UPDATE', 'database_user_id', $this->dataRecord['database_ro_user_id'], $user_old_rec, $user_new_rec);
				}
			}
		}


		parent::onBeforeInsert();
	}

	function onInsertSave($sql) {
		global $app, $conf;

		$app->db->query($sql);
		if($app->db->errorMessage != '') die($app->db->errorMessage);
		$new_id = $app->db->insertID();

		return $new_id;
	}

	function onUpdateSave($sql) {
		global $app;
		if(!empty($sql) && !$app->tform->isReadonlyTab($app->tform->getCurrentTab(), $this->id)) {

			$app->db->query($sql);
			if($app->db->errorMessage != '') die($app->db->errorMessage);
		}
	}

	function onAfterInsert() {
		global $app, $conf;

		$app->uses('sites_database_plugin');
		$app->sites_database_plugin->processDatabaseInsert($this);
	}

	function onAfterUpdate() {
		global $app, $conf;

		$app->uses('sites_database_plugin');
		$app->sites_database_plugin->processDatabaseUpdate($this);
	}

}

$page = new page_action;
$page->onLoad();

?>
