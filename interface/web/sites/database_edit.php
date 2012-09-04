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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

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
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf, $interfaceConf;

		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT default_dbserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_dbserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_dbserver]'>$tmp[server_name]</option>");
			unset($tmp);

		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, limit_web_domain, default_webserver, contact_name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_webserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_webserver]'>$tmp[server_name]</option>");
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
			$app->tpl->setVar("database_name", str_replace($dbname_prefix , '', $this->dataRecord['database_name']));
		}
		
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$app->tpl->setVar("database_name_prefix", $global_config['dbname_prefix']);
		} else {
			$app->tpl->setVar("database_name_prefix", $dbname_prefix);
		}
		
		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
			$app->tpl->setVar("database_charset_value", $this->dataRecord["database_charset"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT default_dbserver, limit_database FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_database WHERE database_id = ".intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
				// When the record is inserted
			} else {
				// set the server ID to the default dbserver of the client
				$this->dataRecord["server_id"] = $client["default_dbserver"];


				// Check if the user may add another database
				if($client["limit_database"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(database_id) as number FROM web_database WHERE sys_groupid = $client_group_id");
					if($tmp["number"] >= $client["limit_database"]) {
						$app->error($app->tform->wordbook["limit_database_txt"]);
					}
				}

			}
		}


		parent::onSubmit();
	}

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;

		//* Site shell not be empty
		if($this->dataRecord['parent_domain_id'] == 0) $app->tform->errorMessage .= $app->tform->lng("database_site_error_empty").'<br />';
		
		//* Get the database name and database user prefix
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$dbname_prefix = $app->tools_sites->replacePrefix($global_config['dbname_prefix'], $this->dataRecord);
		
		//* Prevent that the database name and charset is changed
		$old_record = $app->tform->getDataRecord($this->id);
		if($old_record["database_name"] != $dbname_prefix . $this->dataRecord["database_name"]) {
			$app->tform->errorMessage .= $app->tform->wordbook["database_name_change_txt"].'<br />';
		}
		if($old_record["database_charset"] != $this->dataRecord["database_charset"]) {
			$app->tform->errorMessage .= $app->tform->wordbook["database_charset_change_txt"].'<br />';
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
		
		if(strlen($dbname_prefix . $this->dataRecord['database_name']) > 64) $app->tform->errorMessage .= str_replace('{db}',$dbname_prefix . $this->dataRecord['database_name'],$app->tform->wordbook["database_name_error_len"]).'<br />';
		
		//* Check database name and user against blacklist
		$dbname_blacklist = array($conf['db_database'],'mysql');
		if(in_array($dbname_prefix . $this->dataRecord['database_name'],$dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database name not allowed.').'<br />';
		}
		
		if ($app->tform->errorMessage == ''){
			/* restrict the names if there is no error */
            /* crop user and db names if they are too long -> mysql: user: 16 chars / db: 64 chars */
			$this->dataRecord['database_name'] = substr($dbname_prefix . $this->dataRecord['database_name'], 0, 64);
		}
		
		//* Check for duplicates
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as dbnum FROM web_database WHERE database_name = '".$this->dataRecord['database_name']."' AND server_id = '".$this->dataRecord["server_id"]."' AND database_id != '".$this->id."'");
		if($tmp['dbnum'] > 0) $app->tform->errorMessage .= $app->lng('database_name_error_unique').'<br />';
		
        // get the web server ip (parent domain)
        $tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = '".$this->dataRecord['parent_domain_id']."'");
        if($tmp['server_id'] && $tmp['server_id'] != $this->dataRecord['server_id']) {
            // we need remote access rights for this server, so get it's ip address
            $server_config = $app->getconf->get_server_config($tmp['server_id'], 'server');
            if($server_config['ip_address']!='') {
                if($this->dataRecord['remote_access'] != 'y') $this->dataRecord['remote_ips'] = '';
                $this->dataRecord['remote_access'] = 'y';
                if(preg_match('/(^|,)' . preg_quote($server_config['ip_address'], '/') . '(,|$)/', $this->dataRecord['remote_ips']) == false) {
                    $this->dataRecord['remote_ips'] .= ($this->dataRecord['remote_ips'] != '' ? ',' : '') . $server_config['ip_address'];
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
		
		if(strlen($dbname_prefix . $this->dataRecord['database_name']) > 64) $app->tform->errorMessage .= str_replace('{db}',$dbname_prefix . $this->dataRecord['database_name'],$app->tform->wordbook["database_name_error_len"]).'<br />';
		
		//* Check database name and user against blacklist
		$dbname_blacklist = array($conf['db_database'],'mysql');
		if(in_array($dbname_prefix . $this->dataRecord['database_name'],$dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database name not allowed.').'<br />';
		}
		
		/* restrict the names */
        /* crop user and db names if they are too long -> mysql: user: 16 chars / db: 64 chars */
		if ($app->tform->errorMessage == ''){
			$this->dataRecord['database_name'] = substr($dbname_prefix . $this->dataRecord['database_name'], 0, 64);
		}
		
		//* Check for duplicates
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as dbnum FROM web_database WHERE database_name = '".$this->dataRecord['database_name']."' AND server_id = '".$this->dataRecord["server_id"]."'");
		if($tmp['dbnum'] > 0) $app->tform->errorMessage .= $app->tform->lng('database_name_error_unique').'<br />';

        // get the web server ip (parent domain)
        $tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = '".$this->dataRecord['parent_domain_id']."'");
        if($tmp['server_id'] && $tmp['server_id'] != $this->dataRecord['server_id']) {
            // we need remote access rights for this server, so get it's ip address
            $server_config = $app->getconf->get_server_config($tmp['server_id'], 'server');
            if($server_config['ip_address']!='') {
                if($this->dataRecord['remote_access'] != 'y') $this->dataRecord['remote_ips'] = '';
                $this->dataRecord['remote_access'] = 'y';
                if(preg_match('/(^|,)' . preg_quote($server_config['ip_address'], '/') . '(,|$)/', $this->dataRecord['remote_ips']) == false) {
                    $this->dataRecord['remote_ips'] .= ($this->dataRecord['remote_ips'] != '' ? ',' : '') . $server_config['ip_address'];
                }
            }
        }
        
		parent::onBeforeInsert();
	}

    function onInsertSave($sql) {
        global $app, $conf;
        
        $app->uses('sites_database_plugin');
        
        $app->sites_database_plugin->processDatabaseInsert($this);
        
        $app->db->query($sql);
        if($app->db->errorMessage != '') die($app->db->errorMessage);
        $new_id = $app->db->insertID();
        
        return $new_id;
    }

    function onUpdateSave($sql) {
        global $app;
        if(!empty($sql) && !$app->tform->isReadonlyTab($app->tform->getCurrentTab(),$this->id)) {
            
            $app->uses('sites_database_plugin');
            $app->sites_database_plugin->processDatabaseUpdate($this);

            $app->db->query($sql);
            if($app->db->errorMessage != '') die($app->db->errorMessage);
        }
    }
    
	function onAfterInsert() {
		global $app, $conf;
		
		if($this->dataRecord["parent_domain_id"] > 0) {
			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($this->dataRecord["parent_domain_id"]));
		
			//* The Database user shall be owned by the same group then the website
			$sys_groupid = $web['sys_groupid'];
			$backup_interval = $web['backup_interval'];
			$backup_copies = $web['backup_copies'];
		
			$sql = "UPDATE web_database SET sys_groupid = '$sys_groupid', backup_interval = '$backup_interval', backup_copies = '$backup_copies' WHERE database_id = ".$this->id;
			$app->db->query($sql);
		}
	}

	function onAfterUpdate() {
		global $app, $conf;

		if($this->dataRecord["parent_domain_id"] > 0) {
			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($this->dataRecord["parent_domain_id"]));
		
			//* The Database user shall be owned by the same group then the website
			$sys_groupid = $web['sys_groupid'];
			$backup_interval = $web['backup_interval'];
			$backup_copies = $web['backup_copies'];
		
			$sql = "UPDATE web_database SET sys_groupid = '$sys_groupid', backup_interval = '$backup_interval', backup_copies = '$backup_copies' WHERE database_id = ".$this->id;
			$app->db->query($sql);
		}

	}

}

$page = new page_action;
$page->onLoad();

?>