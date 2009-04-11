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

$tform_def_file = "form/domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('domain');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;
		
		if($_SESSION["s"]["user"]["typ"] != 'admin') {
  		$app->error($app->tform->wordbook["onlyforadmin_txt"]);
		}
		
		parent::onShowNew();
	}
	
	function onShowEnd() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin') {
  		$app->error($app->tform->wordbook["onlyforadmin_txt"]);
		}

		// Fill the client select field
		$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0";
		$clients = $app->db->queryAllRecords($sql);
		$client_select = "<option value='0'></option>";
		if(is_array($clients)) {
			foreach( $clients as $client) {
				$selected = @($client["groupid"] == $this->dataRecord["sys_groupid"])?'SELECTED':'';
				$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
			}
		}
		$app->tpl->setVar("client_group_id",$client_select);
		
		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		parent::onSubmit();
	}
	
	function onAfterInsert() {
		global $app, $conf;
		
	}
	
	function onBeforeUpdate () {
		global $app, $conf;
	
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
	}
	
	function onAfterDelete() {
		global $app, $conf;
		
	}
	
}

$page = new page_action;
$page->onLoad();

?>