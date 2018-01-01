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

$tform_def_file = "form/server.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_server_services');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowEnd() {
		global $app, $conf;

		// Getting Servers
		$sql = "SELECT server_id,server_name FROM server WHERE server_id != ? AND mirror_server_id != ? ORDER BY server_name";
		$mirror_servers = $app->db->queryAllRecords($sql, $this->id, $this->id);
		$mirror_server_select = '<option value="0">'.$app->tform->lng('- None -').'</option>';
		if(is_array($mirror_servers)) {
			foreach( $mirror_servers as $mirror_server) {
				$selected = ($mirror_server["server_id"] == $this->dataRecord['mirror_server_id'])?'SELECTED':'';
				$mirror_server_select .= "<option value='$mirror_server[server_id]' $selected>" . $app->functions->htmlentities($mirror_server['server_name']) . "</option>\r\n";
			}
		}
		$app->tpl->setVar("mirror_server_id", $mirror_server_select);

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app;

		//* We do not want to mirror the the server itself and the master can not be a mirror
		if($this->id == $this->dataRecord['mirror_server_id'] || $this->id == 1) $this->dataRecord['mirror_server_id'] = 0;

		parent::onSubmit();

	}

}

$page = new page_action;
$page->onLoad();

?>
