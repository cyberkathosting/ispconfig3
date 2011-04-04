<?php
/*
Copyright (c) 2005 - 2008, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/client.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');
require_once('tools.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('client');

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
			$client = $app->db->queryOneRecord("SELECT limit_client FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Check if the user may add another website.
			if($client["limit_client"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(client_id) as number FROM client WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_client"]) {
					$app->error($app->tform->wordbook["limit_client_txt"]);
				}
			}
		}
		
		parent::onShowNew();
	}
	
	
	function onSubmit() {
		global $app, $conf;
		
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user' && $this->id == 0) {
			
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_client FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Check if the user may add another website.
			if($client["limit_client"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(client_id) as number FROM client WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_client"]) {
					$app->error($app->tform->wordbook["limit_client_txt"]);
				}
			}
		}
		
		parent::onSubmit();
	}


	function onShowEnd() {

		global $app;

		$sql = "SELECT template_id,template_name FROM client_template WHERE template_type = 'a'";
		$tpls = $app->db->queryAllRecords($sql);
		$option = '';
		$tpl = array();
		foreach($tpls as $item){
			$option .= '<option value="' . $item['template_id'] . '|' .  $item['template_name'] . '">' . $item['template_name'] . '</option>';
			$tpl[$item['template_id']] = $item['template_name'];
		}
		$app->tpl->setVar('tpl_add_select',$option);

		$sql = "SELECT template_additional FROM client WHERE client_id = " . $this->id;
		$result = $app->db->queryOneRecord($sql);
		$tplAdd = explode("/", $result['template_additional']);
		$text = '';
		foreach($tplAdd as $item){
			if (trim($item) != ''){
				if ($text != '') $text .= '<br />';
				$text .= $tpl[$item];
			}
		}

		$app->tpl->setVar('template_additional_list', $text);

		parent::onShowEnd();

	}

	/*
	 This function is called automatically right after
	 the data was successful inserted in the database.
	*/
	function onAfterInsert() {
		global $app, $conf;
		// Create the group for the client
		$groupid = $app->db->datalogInsert('sys_group', "(name,description,client_id) VALUES ('".mysql_real_escape_string($this->dataRecord["username"])."','',".$this->id.")", 'groupid');
		$groups = $groupid;
		
		$username = $app->db->quote($this->dataRecord["username"]);
		$password = $app->db->quote($this->dataRecord["password"]);
		$modules = $conf['interface_modules_enabled'];
		if($this->dataRecord["limit_client"] > 0) $modules .= ',client';
		$startmodule = (stristr($modules,'dashboard'))?'dashboard':'client';
		$usertheme = $app->db->quote($this->dataRecord["usertheme"]);
		$type = 'user';
		$active = 1;
		$language = $app->db->quote($this->dataRecord["language"]);
		
		//Generate ssh-rsa-keys
		exec('ssh-keygen -t rsa -C '.$username.'-rsa-key-'.time().' -f /tmp/id_rsa -N ""');
		
		$privatekey = file_get_contents('/tmp/id_rsa');
		$publickey  = file_get_contents('/tmp/id_rsa.pub');
		
		exec('rm -f /tmp/id_rsa /tmp/id_rsa.pub');

		// Create the controlpaneluser for the client
		$sql = "INSERT INTO sys_user (username,passwort,modules,startmodule,app_theme,typ,active,language,groups,default_group,client_id,id_rsa,ssh_rsa)
		VALUES ('$username',md5('$password'),'$modules','$startmodule','$usertheme','$type','$active','$language',$groups,$groupid,".$this->id.",'$privatekey','$publickey')";
		$app->db->query($sql);
		
		//* If the user who inserted the client is a reseller (not admin), we will have to add this new client group 
		//* to his groups, so he can administrate the records of this client.
		if($_SESSION['s']['user']['typ'] == 'user') {
			$app->auth->add_group_to_user($_SESSION['s']['user']['userid'],$groupid);
			$app->db->query("UPDATE client SET parent_client_id = ".intval($_SESSION['s']['user']['client_id'])." WHERE client_id = ".$this->id);
		}
		
		$app->db->query("UPDATE client SET created_at = ".time()." WHERE client_id = ".$this->id);

		/* If there is a client-template, process it */
		applyClientTemplates($this->id);

		parent::onAfterInsert();
	}
	
	
	/*
	 This function is called automatically right after
	 the data was successful updated in the database.
	*/
	function onAfterUpdate() {
		global $app;
		
		// username changed
		if($conf['demo_mode'] != true && isset($this->dataRecord['username']) && $this->dataRecord['username'] != '' && $this->oldDataRecord['username'] != $this->dataRecord['username']) {
			$username = $app->db->quote($this->dataRecord["username"]);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET username = '$username' WHERE client_id = $client_id";
			$app->db->query($sql);
			
			$tmp = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE client_id = $client_id");
			$app->db->datalogUpdate("sys_group", "name = '$username'", 'groupid', $tmp['groupid']);
			unset($tmp);
		}
		
		// password changed
		if($conf['demo_mode'] != true && isset($this->dataRecord["password"]) && $this->dataRecord["password"] != '') {
			$password = $app->db->quote($this->dataRecord["password"]);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET passwort = md5('$password') WHERE client_id = $client_id";
			$app->db->query($sql);
		}
		
		// language changed
		if($conf['demo_mode'] != true && isset($this->dataRecord['language']) && $this->dataRecord['language'] != '' && $this->oldDataRecord['language'] != $this->dataRecord['language']) {
			$language = $app->db->quote($this->dataRecord["language"]);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET language = '$language' WHERE client_id = $client_id";
			$app->db->query($sql);
		}
		
		// reseller status changed
		if(isset($this->dataRecord["limit_client"]) && $this->dataRecord["limit_client"] != $this->oldDataRecord["limit_client"]) {
			$modules = $conf['interface_modules_enabled'];
			if($this->dataRecord["limit_client"] > 0) $modules .= ',client';
			$modules = $app->db->quote($modules);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET modules = '$modules' WHERE client_id = $client_id";
			$app->db->query($sql);
		}
		
		/*
		 *  If there is a client-template, process it */
		applyClientTemplates($this->id);

		parent::onAfterUpdate();
	}
}

$page = new page_action;
$page->onLoad();

?>
