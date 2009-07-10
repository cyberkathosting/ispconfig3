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

$tform_def_file = "form/shell_user.tform.php";

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
			if(!$app->tform->checkClientLimit('limit_shell_user')) {
				$app->error($app->tform->wordbook["limit_shell_user_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_shell_user')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_shell_user_txt"]);
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
		//$shelluser_prefix = ($global_config['shelluser_prefix'] == '')?'':str_replace('[CLIENTNAME]', $this->getClientName(), $global_config['shelluser_prefix']);
		$shelluser_prefix = replacePrefix($global_config['shelluser_prefix'], $this->dataRecord);
		
		if ($this->dataRecord['username'] != ""){
			/* REMOVE the restriction */
			$app->tpl->setVar("username", str_replace($shelluser_prefix , '', $this->dataRecord['username']));
		}
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$app->tpl->setVar("username_prefix", $global_config['shelluser_prefix']);
		} else {
			$app->tpl->setVar("username_prefix", $shelluser_prefix);
		}
		
		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("parent_domain_id_value", $this->dataRecord["parent_domain_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		// Get the record of the parent domain
		$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ".intval(@$this->dataRecord["parent_domain_id"]));
		
		// Set a few fixed values
		$this->dataRecord["server_id"] = $parent_domain["server_id"];
		
		if(isset($this->dataRecord['username']) && trim($this->dataRecord['username']) == '') $app->tform->errorMessage .= $app->tform->lng('username_error_empty').'<br />';
		if(isset($this->dataRecord['username']) && empty($this->dataRecord['parent_domain_id'])) $app->tform->errorMessage .= $app->tform->lng('parent_domain_id_error_empty').'<br />';
		
		parent::onSubmit();
	}
	
	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;

		// check if the username is not blacklisted
		$blacklist = file(ISPC_LIB_PATH.'/shelluser_blacklist');
		foreach($blacklist as $line) {
			if(strtolower(trim($line)) == strtolower(trim($this->dataRecord['username']))){
				$app->tform->errorMessage .= 'The username is not allowed.';
			}
		}
		unset($blacklist);
		
		/*
		 * If the names should be restricted -> do it!
		 */
		if ($app->tform->errorMessage == ''){
			
			$app->uses('getconf');
			$global_config = $app->getconf->get_global_config('sites');
			// $shelluser_prefix = ($global_config['shelluser_prefix'] == '')?'':str_replace('[CLIENTNAME]', $this->getClientName(), $global_config['shelluser_prefix']);
			$shelluser_prefix = replacePrefix($global_config['shelluser_prefix'], $this->dataRecord);
			
			/* restrict the names */
			$this->dataRecord['username'] = $shelluser_prefix . $this->dataRecord['username'];
		}
		parent::onBeforeInsert();
	}
	
	function onAfterInsert() {
		global $app, $conf;
		
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($this->dataRecord["parent_domain_id"]));
		$server_id = $web["server_id"];
		$dir = $web["document_root"];
		$puser = $web["system_user"];
		$pgroup = $web["system_group"];
		
		// The FTP user shall be owned by the same group then the website
		$sys_groupid = $web['sys_groupid'];
		
		$sql = "UPDATE shell_user SET server_id = $server_id, dir = '$dir', puser = '$puser', pgroup = '$pgroup', sys_groupid = '$sys_groupid' WHERE shell_user_id = ".$this->id;
		$app->db->query($sql);
		
	}
	
	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;
		
		// check if the username is not blacklisted
		$blacklist = file(ISPC_LIB_PATH.'/shelluser_blacklist');
		foreach($blacklist as $line) {
			if(strtolower(trim($line)) == strtolower(trim($this->dataRecord['username']))){
				$app->tform->errorMessage .= 'The username is not allowed.';
			}
		}
		unset($blacklist);

		/*
		 * If the names should be restricted -> do it!
		 */
		if ($app->tform->errorMessage == '') {
			/*
			* If the names should be restricted -> do it!
			*/
			$app->uses('getconf');
			$global_config = $app->getconf->get_global_config('sites');
			// $shelluser_prefix = ($global_config['shelluser_prefix'] == '')?'':str_replace('[CLIENTNAME]', $this->getClientName(), $global_config['shelluser_prefix']);
			$shelluser_prefix = replacePrefix($global_config['shelluser_prefix'], $this->dataRecord);
			
			/* restrict the names */
			$this->dataRecord['username'] = $shelluser_prefix . $this->dataRecord['username'];
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
			$web = $app->db->queryOneRecord("SELECT sys_groupid FROM web_domain WHERE domain_id = ".intval($this->dataRecord['parent_domain_id']));
			$client_group_id = $web['sys_groupid'];
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