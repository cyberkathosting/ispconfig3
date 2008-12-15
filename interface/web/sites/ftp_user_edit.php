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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/ftp_user.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');
require_once('tools.inc.php');

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
			
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_ftp_user FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Check if the user may add another ftp user.
			if($client["limit_ftp_user"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(ftp_user_id) as number FROM ftp_user WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_ftp_user"]) {
					$app->error($app->tform->wordbook["limit_ftp_user_txt"]);
				}
			}
		}
		
		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf, $interfaceConf;
		/*
		 * If the names are restricted -> remove the restriction, so that the
		 * data can be edited
		 */
		
		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');
		$ftpuser_prefix = ($global_config['ftpuser_prefix'] == '')?'':str_replace('[CLIENTNAME]', $this->getClientName(), $global_config['ftpuser_prefix']);
		
		if ($this->dataRecord['username'] != ""){
			/* REMOVE the restriction */
			$app->tpl->setVar("username", str_replace($ftpuser_prefix , '', $this->dataRecord['username']));
		}
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$app->tpl->setVar("username_prefix", $global_config['ftpuser_prefix']);
		}
		else {
			$app->tpl->setVar("username_prefix", $ftpuser_prefix);
		}

		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		// Get the record of the parent domain
		$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ".intval(@$this->dataRecord["parent_domain_id"]));
		
		// Set a few fixed values
		$this->dataRecord["server_id"] = $parent_domain["server_id"];
		
		parent::onSubmit();
	}
	
	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;
		
		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');
		$ftpuser_prefix = ($global_config['ftpuser_prefix'] == '')?'':str_replace('[CLIENTNAME]', $this->getClientName(), $global_config['ftpuser_prefix']);
		
		if ($app->tform->errorMessage == '') {
			$this->dataRecord['username'] = $ftpuser_prefix . $this->dataRecord['username'];
		}
		
		parent::onBeforeInsert();
	}

		function onAfterInsert() {
		global $app, $conf;
		
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($this->dataRecord["parent_domain_id"]));
		$server_id = $web["server_id"];
		$dir = $web["document_root"];
		$uid = $web["system_user"];
		$gid = $web["system_group"];
		
		// The FTP user shall be owned by the same group then the website
		$sys_groupid = $web['sys_groupid'];
		
		$sql = "UPDATE ftp_user SET server_id = $server_id, dir = '$dir', uid = '$uid', gid = '$gid', sys_groupid = '$sys_groupid' WHERE ftp_user_id = ".$this->id;
		$app->db->query($sql);
		
		
	}

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;

		/*
		 * If the names should be restricted -> do it!
		 */
		
		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');
		$ftpuser_prefix = ($global_config['ftpuser_prefix'] == '')?'':str_replace('[CLIENTNAME]', $this->getClientName(), $global_config['ftpuser_prefix']);
		
		/* restrict the names */
		if ($app->tform->errorMessage == '') {
			$this->dataRecord['username'] = $ftpuser_prefix . $this->dataRecord['username'];
		}
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		
	}
	
	function getClientName() {
		global $app, $conf;
	
		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the group-id of the user
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
		} else {
			// Get the group-id from the data itself
			$client_group_id = $this->dataRecord['client_group_id'];
		}
		/* get the name of the client */
		$tmp = $app->db->queryOneRecord("SELECT name FROM sys_group WHERE groupid = " . $client_group_id);
		$clientName = $tmp['name'];
		if ($clientName == "") $clientName = 'default';
		$clientName = convertClientName($clientName);
		return $clientName;
	
	}
	
}

$page = new page_action;
$page->onLoad();

?>