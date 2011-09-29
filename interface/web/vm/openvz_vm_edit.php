<?php
/*
Copyright (c) 2005 - 2010, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/openvz_vm.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('vm');

// Loading classes
$app->uses('tpl,tform');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;
		
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_openvz_vm')) {
				$app->error($app->tform->wordbook["limit_openvz_vm_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_openvz_vm')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_openvz_vm_txt"]);
			}
		}
		
		parent::onShowNew();
	}
	
	function onShowEnd() {
		global $app, $conf;

		//* Client: If the logged in user is not admin and has no sub clients (no rseller)
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			
			//* Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, client.limit_openvz_vm_template_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			//* Fill the template_id field
			if($client['limit_openvz_vm_template_id'] == 0) {
				$sql = 'SELECT template_id,template_name FROM openvz_template WHERE 1 ORDER BY template_name';
			} else {
				$sql = 'SELECT template_id,template_name FROM openvz_template WHERE template_id = '.$client['limit_openvz_vm_template_id'].' ORDER BY template_name';
			}
			$records = $app->db->queryAllRecords($sql);
			if(is_array($records)) {
				foreach( $records as $rec) {
					$selected = @($rec["template_id"] == $this->dataRecord["template_id"])?'SELECTED':'';
					$template_id_select .= "<option value='$rec[template_id]' $selected>$rec[template_name]</option>\r\n";
				}
			}
			$app->tpl->setVar("template_id_select",$template_id_select);
			
			//* Reseller: If the logged in user is not admin and has sub clients (is a rseller)
		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			
			//* Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, client.limit_openvz_vm_template_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			
			//* Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id']." ORDER BY name";
			$records = $app->db->queryAllRecords($sql);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
			$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contact_name'].'</option>';
			$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($records)) {
				foreach( $records as $rec) {
					$selected = @($rec["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$rec[groupid]' $selected>$rec[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);
			
			//* Fill the template_id field
			if($client['limit_openvz_vm_template_id'] == 0) {
				$sql = 'SELECT template_id,template_name FROM openvz_template WHERE 1 ORDER BY template_name';
			} else {
				$sql = 'SELECT template_id,template_name FROM openvz_template WHERE template_id = '.$client['limit_openvz_vm_template_id'].' ORDER BY template_name';
			}
			$records = $app->db->queryAllRecords($sql);
			if(is_array($records)) {
				foreach( $records as $rec) {
					$selected = @($rec["template_id"] == $this->dataRecord["template_id"])?'SELECTED':'';
					$template_id_select .= "<option value='$rec[template_id]' $selected>$rec[template_name]</option>\r\n";
				}
			}
			$app->tpl->setVar("template_id_select",$template_id_select);

			//* Admin: If the logged in user is admin
		} else {

			//* Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0 ORDER BY name";
			$clients = $app->db->queryAllRecords($sql);
			$client_select = "<option value='0'></option>";
			$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @($client["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);
			
			//* Fill the template_id field
			$sql = 'SELECT template_id,template_name FROM openvz_template WHERE 1 ORDER BY template_name';
			$records = $app->db->queryAllRecords($sql);
			if(is_array($records)) {
				foreach( $records as $rec) {
					$selected = @($rec["template_id"] == $this->dataRecord["template_id"])?'SELECTED':'';
					$template_id_select .= "<option value='$rec[template_id]' $selected>$rec[template_name]</option>\r\n";
				}
			}
			$app->tpl->setVar("template_id_select",$template_id_select);

		}
		
		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
			$app->tpl->setVar("ostemplate_id_value", $this->dataRecord["ostemplate_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		// Datepicker
		$date_format = $app->lng('conf_format_dateshort');
		$trans = array("d" => "dd", "m" => "mm", "Y" => "yy");
		$date_format = strtr($date_format, $trans);
		$app->tpl->setVar("date_format", $date_format);
		$app->tpl->setVar("datepicker_dayNamesMin", $app->lng('datepicker_dayNamesMin'));
		$app->tpl->setVar("datepicker_dayNames", $app->lng('datepicker_dayNames'));
		$app->tpl->setVar("datepicker_monthNamesShort", $app->lng('datepicker_monthNamesShort'));
		$app->tpl->setVar("datepicker_nextText", $app->lng('datepicker_nextText'));
		$app->tpl->setVar("datepicker_prevText", $app->lng('datepicker_prevText'));
		
		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		//* Clients may not set the client_group_id, so we unset them if user is not a admin and the client is not a reseller
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);

		parent::onSubmit();
	}

}

$page = new page_action;
$page->onLoad();

?>