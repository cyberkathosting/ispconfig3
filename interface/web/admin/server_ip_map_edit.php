<?php
/*
Copyright (c) 2015, Florian Schaal, schaal @it
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

$tform_def_file = "form/server_ip_map.tform.php";

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_server_ip');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowEnd() {
		global $app;

		// server-list
		$sql = "SELECT server_id, server_name FROM server WHERE mirror_server_id > 0 ORDER BY server_name";
		$servers =  $app->db->queryAllRecords($sql);
		$server_select = "<option value=''></option>";
		if(is_array($servers)) {
			foreach($servers as $server) {
				$selected = ($server['server_id'] == $this->dataRecord['server_id'])?'SELECTED':'';
				$server_select .= "<option value='$server[server_id]' $selected>" . $app->functions->htmlentities($server['server_name']) . "</option>\r\n";
			}
		}
		unset($servers);
		$app->tpl->setVar('server_id', $server_select);

		// ip-list
		$sql = "SELECT server_ip.server_ip_id, server_ip.ip_address AS ip_address, server.server_name, CONCAT(server_ip.ip_address,' :: [', server.server_name, ']') AS source FROM server_ip, server WHERE (server_ip.server_id = server.server_id AND server.web_server =1 AND mirror_server_id = 0 AND virtualhost = 'y' AND IP_TYPE = 'IPv4')";
		$ips = $app->db->queryAllRecords($sql);
		$ip_select = "<option value=''></option>";
		if(is_array($ips)) {
			foreach( $ips as $ip) {
				$selected = ($ip['ip_address'] == $this->dataRecord['source_ip'])?'SELECTED':'';
				$ip_select .= "<option value='" . $app->functions->htmlentities($ip['ip_address']) . "' $selected>" . $app->functions->htmlentities($ip['source']) . "</option>\r\n";
			}
		}
		unset($ips);
		$app->tpl->setVar('source_ip', $ip_select);

		parent::onShowEnd();
	}

	function onBeforeInsert() {
		global $app;

		if($this->dataRecord['server_id']=='') $app->tform->errorMessage .= $app->tform->wordbook['server_empty_error'];

		$sql = "SELECT * FROM server_ip WHERE server_id = ? and ip_address = ?";
		$ip_check=$app->db->queryOneRecord($sql, $this->dataRecord['server_id'], $this->dataRecord['source_ip']);
		if (is_array($ip_check)) $app->tform->errorMessage .= $app->tform->wordbook['ip_mapping_error'];

		$sql = 'SELECT count(*) as no FROM server_ip_map WHERE server_id = ? AND source_ip = ? AND destination_ip = ?';
		$check = $app->db->queryOneRecord($sql, $this->dataRecord['server_id'], $this->dataRecord['source_ip'], $this->dataRecord['destination_ip']);
		if ($check['no'] > 0) $app->tform->errorMessage .= $app->tform->wordbook['duplicate_mapping_error'];	
	}

	function onBeforeUpdate() {
		global $app;

		if($this->dataRecord['server_id']=='') $app->tform->errorMessage .= $app->tform->wordbook['server_empty_error'];

		$sql = "SELECT * FROM server_ip WHERE server_id = ? and ip_address = ?";
		$ip_check=$app->db->queryOneRecord($sql, $this->dataRecord['server_id'], $this->dataRecord['source_ip']);
		if (is_array($ip_check)) $app->tform->errorMessage .= $app->tform->wordbook['ip_mapping_error'];

		$this->oldDataRecord = $app->tform->getDataRecord($this->id);
		if ($this->dataRecord['source_ip'] != $this->oldDataRecord['source_ip'] || $this->dataRecord['destination_ip'] != $this->oldDataRecord['destination_ip']) {
			$sql = 'SELECT count(*) as no FROM server_ip_map WHERE server_id = ? AND source_ip = ? AND destination_ip = ?';
			$check = $app->db->queryOneRecord($sql, $this->dataRecord['server_id'], $this->dataRecord['source_ip'], $this->dataRecord['destination_ip']);
			if ($check['no'] > 0) $app->tform->errorMessage .= $app->tform->wordbook['duplicate_mapping_error'];	
		}
	}

}

$page = new page_action;
$page->onLoad();

?>
