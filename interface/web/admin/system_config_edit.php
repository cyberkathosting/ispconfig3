<?php
/*
Copyright (c) 2008-2010, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/system_config.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('admin');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {


	function onShowEdit() {
		global $app, $conf;
		
		if($_SESSION["s"]["user"]["typ"] != 'admin') die('This function needs admin priveliges');
		
		if($app->tform->errorMessage == '') {
			$app->uses('ini_parser,getconf');
			
			$section = $this->active_tab;
			$server_id = $this->id;
		
			$this->dataRecord = $app->getconf->get_global_config($section);
			if ($section == 'domains'){
				if (isset($this->dataRecord['use_domain_module'])){
					$_SESSION['use_domain_module_old_value'] = $this->dataRecord['use_domain_module'];
				}
			}
		}
		
		$record = $app->tform->getHTML($this->dataRecord, $this->active_tab,'EDIT');
		
		$record['warning'] = $app->tform->lng('warning');
		$record['id'] = $this->id;
		$app->tpl->setVar($record);
	}
	
	function onUpdateSave($sql) {
		global $app,$conf;
		
		if($_SESSION["s"]["user"]["typ"] != 'admin') die('This function needs admin priveliges');
		$app->uses('ini_parser,getconf');
		
		$section = $app->tform->getCurrentTab();
		
		$server_config_array = $app->getconf->get_global_config();
		$server_config_array[$section] = $app->tform->encode($this->dataRecord,$section);
		$server_config_str = $app->ini_parser->get_ini_string($server_config_array);
		
		$sql = "UPDATE sys_ini SET config = '".$app->db->quote($server_config_str)."' WHERE sysini_id = 1";
		if($conf['demo_mode'] != true) $app->db->query($sql);

		/*
		 * If we should use the domain-module, we have to insert all existing domains into the table
		 * (only the first time!)
		 */
		if (($section == 'domains') && 
				($_SESSION['use_domain_module_old_value'] == '') &&
				($server_config_array['domains']['use_domain_module'] == 'y')){
			$sql = "REPLACE INTO domain (sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, domain ) " .
				"SELECT sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, domain " .
				"FROM mail_domain";
			$app->db->query($sql);
			$sql = "REPLACE INTO domain (sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, domain ) " .
				"SELECT sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, domain " .
				"FROM web_domain";
			$app->db->query($sql);
		}
		
		// Maintenance mode
		if($server_config_array['misc']['maintenance_mode'] == 'y'){
			//print_r($_SESSION);
			//echo $_SESSION['s']['id'];
			$app->db->query("DELETE FROM sys_session WHERE session_id != '".$_SESSION['s']['id']."'");
		}
	}
	
}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();


?>