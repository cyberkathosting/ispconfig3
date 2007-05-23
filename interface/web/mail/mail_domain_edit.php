<?php
/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/mail_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

// Checking module permissions
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
	header("Location: ../index.php");
	exit;
}

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowEnd() {
		global $app, $conf;
		
		if($_SESSION["s"]["user"]["typ"] == 'admin') {
			// Getting Domains of the user
			$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0";
			$clients = $app->db->queryAllRecords($sql);
			$client_select = "<option value='0'></option>";
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = ($client["groupid"] == $this->dataRecord["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
		$app->tpl->setVar("client_group_id",$client_select);
		}
		
		parent::onShowEnd();
	}
	
	function onSubmit() {
		if($_SESSION["s"]["user"]["typ"] != 'admin') unset($this->dataRecord["client_group_id"]);
		parent::onSubmit();
	}
	
	function onAfterInsert() {
		global $app, $conf;
		
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_domain SET sys_groupid = $client_group_id WHERE domain_id = ".$this->id);
		}
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_domain SET sys_groupid = $client_group_id WHERE domain_id = ".$this->id);
		}
	}
	
}

$page = new page_action;
$page->onLoad();

?>