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

$tform_def_file = "form/mail_transport.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('mail');


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
			$client = $app->db->queryOneRecord("SELECT limit_mailrouting FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Check if the user may add another transport.
			if($client["limit_mailrouting"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(transport_id) as number FROM mail_transport WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_mailrouting"]) {
					$app->error($app->tform->wordbook["limit_mailrouting_txt"]);
				}
			}
		}
		
		parent::onShowNew();
	}
	
	function onShowEnd() {
		global $app, $conf;
		
		$tmp_parts = explode(":",$this->dataRecord["transport"]);
		if(empty($this->id) && empty($tmp_parts[0])) {
			$rec["type"] = 'smtp';
		} else {
			$rec["type"] = $tmp_parts[0];
		}
		if(@substr($tmp_parts[1],0,1) == '[') {
			$rec["mx"] = 'checked="CHECKED"';
			$rec["destination"] = @substr($tmp_parts[1],1,-1);
		} else {
			$rec["mx"] = '';
			$rec["destination"] = @$tmp_parts[1];
		}
		
		$types = array('smtp' => 'smtp','uucp' => 'uucp','slow' => 'slow', 'error' => 'error', '' => 'null');
		$type_select = '';
		if(is_array($types)) {
			foreach( $types as $key => $val) {
				$selected = ($key == $rec["type"])?'SELECTED':'';
				$type_select .= "<option value='$key' $selected>$val</option>\r\n";
			}
		}
		$rec["type"] = $type_select;
		$app->tpl->setVar($rec);
		unset($type);
		unset($types);
		
		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
				
		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_mailrouting FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Check if the user may add another transport.
			if($this->id == 0 && $client["limit_mailrouting"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(transport_id) as number FROM mail_transport WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_mailrouting"]) {
					$app->tform->errorMessage .= $app->tform->wordbook["limit_mailrouting_txt"]."<br>";
				}
				unset($tmp);
			}
		} // end if user is not admin
		
		//* Compose transport field
		if($this->dataRecord["mx"] == 'y') {
			$transport = '['.$this->dataRecord["destination"].']';
		} else {
			$transport = $this->dataRecord["destination"];
		}
		$this->dataRecord["transport"] = $this->dataRecord["type"].':'.$transport;
		unset($this->dataRecord["type"]);
		unset($this->dataRecord["mx"]);
		unset($this->dataRecord["destination"]);
		
		parent::onSubmit();
	}
	
}

$page = new page_action;
$page->onLoad();

?>