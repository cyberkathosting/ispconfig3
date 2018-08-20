<?php
/*
Copyright (c) 2010 Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('client');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

//* load language file
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'.lng';
include $lng_file;


class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf, $wb;

		// Only admins can add domains, so we don't need any check

		$app->tpl->setVar($wb);

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf, $wb;
		
		if($_SESSION["s"]["user"]["typ"] != 'admin' && $this->id == 0) {
			if(!$app->tform->checkClientLimit('limit_domainmodule')) {
				$app->uses('ini_parser,getconf');
				$settings = $app->getconf->get_global_config('domains');
				if ($settings['use_domain_module'] == 'y') {
					$app->error($settings['new_domain_html']);
				}
			}
		}

		if($_SESSION["s"]["user"]["typ"] == 'admin') {
			// Getting Clients of the user
			//$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0 ORDER BY name";
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY client.company_name, client.contact_name, sys_group.name";
			$clients = $app->db->queryAllRecords($sql);
			$clients = $app->functions->htmlentities($clients);
			$client_select = '';
			if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
			if($this->id > 0) $tmp_data_record = $app->tform->getDataRecord($this->id); else $tmp_data_record = $this->dataRecord;
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = ($client["groupid"] == $tmp_data_record["sys_groupid"] || $client["groupid"] == $tmp_data_record["client_group_id"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id", $client_select);

		} else {
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			$client = $app->functions->htmlentities($client);
			
			// Fill the client select field
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
			//die($sql);
			$records = $app->db->queryAllRecords($sql, $client['client_id']);
			$records = $app->functions->htmlentities($records);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client['client_id']);
			$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contactname'].'</option>';
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($records)) {
				$selected_client_group_id = 0; // needed to get list of PHP versions
				foreach( $records as $rec) {
					if(is_array($this->dataRecord) && ($rec["groupid"] == $this->dataRecord['client_group_id'] || $rec["groupid"] == $this->dataRecord['sys_groupid']) && !$selected_client_group_id) $selected_client_group_id = $rec["groupid"];
					$selected = @(is_array($this->dataRecord) && ($rec["groupid"] == $this->dataRecord['client_group_id'] || $rec["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					if($selected == 'SELECTED') $selected_client_group_id = $rec["groupid"];
					$client_select .= "<option value='$rec[groupid]' $selected>$rec[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id", $client_select);
		}

		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		$app->tpl->setVar($wb);

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf, $wb;

		if($_SESSION["s"]["user"]["typ"] == 'admin') {
			if ($this->id == 0) {
				/*
				 * We create a new record
				*/
				// Check if the user is empty
				if(isset($this->dataRecord['client_group_id']) && $this->dataRecord['client_group_id'] == 0) {
					$app->tform->errorMessage .= $wb['error_client_group_id_empty'];
				}
				//* make sure that the domain is lowercase
				if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);
			}
			else {
				/*
				 * We edit a existing one, but domain name can't be changed
				*/
				$oldData = $app->tform->getDataRecord($this->id);
				$this->dataRecord["domain"] = $oldData["domain"];
			}
		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			if ($this->id == 0) {
				/*
				 * We create a new record
				*/
				// Check if the user is empty
				if(isset($this->dataRecord['client_group_id']) && $this->dataRecord['client_group_id'] == 0) {
					$app->tform->errorMessage .= $wb['error_client_group_id_empty'];
				}
				//* make sure that the domain is lowercase
				if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);
			}
			else {
				/*
				 * We edit a existing one, but domain name can't be changed
				*/
				$oldData = $app->tform->getDataRecord($this->id);
				$this->dataRecord["domain"] = $oldData["domain"];
			}
		} else {
			if($this->id > 0) {
				/*
				 * Clients may not edit anything, so we reset the old data
				*/
				$this->dataRecord = $app->tform->getDataRecord($this->id);
			} else {
				/*
				 * clients may not create a new domain
				*/
				$app->error($wb['error_client_can_not_add_domain']);
			}
		}

		$app->tpl->setVar($wb);

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the client group and not the admin group when admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if(($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) || ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid']))) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE domain SET sys_groupid = ?, sys_perm_group = 'ru' WHERE domain_id = ?", $client_group_id, $this->id);
		}
	}

	function onAfterUpdate() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT client.client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			$group = $app->db->queryOneRecord("SELECT sys_group.groupid FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? AND sys_group.groupid = ? ORDER BY client.company_name, client.contact_name, sys_group.name", $client['client_id'], $this->dataRecord["client_group_id"]);
			$this->dataRecord["client_group_id"] = $group["groupid"];
		}

		// make sure that the record belongs to the client group and not the admin group when admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if(isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE domain SET sys_groupid = ?, sys_perm_group = 'ru' WHERE domain_id = ?", $client_group_id, $this->id);

			$data = new tform_actions();
			$tform = $app->tform;
			$app->tform = new tform();

			$app->tform->loadFormDef("../dns/form/dns_soa.tform.php");
			$data->oldDataRecord = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE origin = ?", $this->dataRecord['domain'].".");
			if ($data->oldDataRecord) {
				$data->dataRecord = array_merge($data->oldDataRecord, array('client_group_id' => $this->dataRecord["client_group_id"]));
				$data->id = $data->dataRecord['id'];
				$app->plugin->raiseEvent("dns:dns_soa:on_after_update", $data);
			}

			$app->tform->loadFormDef("../dns/form/dns_slave.tform.php");
			$data->oldDataRecord = $app->db->queryOneRecord("SELECT * FROM dns_slave WHERE origin = ?", $this->dataRecord['domain'].".");
			if ($data->oldDataRecord) {
				$data->dataRecord = array_merge($data->oldDataRecord, array('client_group_id' => $this->dataRecord["client_group_id"]));
				$data->id = $data->dataRecord['id'];
				$app->plugin->raiseEvent("dns:dns_slave:on_after_update", $data);
			}

			$app->tform->loadFormDef("../mail/form/mail_domain.tform.php");
			$data->oldDataRecord = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = ?", $this->dataRecord['domain']);
			if ($data->oldDataRecord) {
				$data->dataRecord = array_merge($data->oldDataRecord, array('client_group_id' => $this->dataRecord["client_group_id"]));
				$data->id = $data->dataRecord['domain_id'];
				$app->plugin->raiseEvent("mail:mail_domain:on_after_update", $data);
			}

			$app->tform->loadFormDef("../sites/form/web_vhost_domain.tform.php");
			$data->oldDataRecord = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain = ?", $this->dataRecord['domain']);
			if ($data->oldDataRecord) {
				$data->dataRecord = array_merge($data->oldDataRecord, array('client_group_id' => $this->dataRecord["client_group_id"]));
				$data->id = $data->dataRecord['domain_id'];
				$app->plugin->raiseEvent("sites:web_vhost_domain:on_after_update", $data);
			}

			$app->tform = $tform;
		}
	}

}

$page = new page_action;
$page->onLoad();

?>
