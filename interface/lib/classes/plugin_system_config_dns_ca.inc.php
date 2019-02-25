<?php

/*
Copyright (c) 2017, Florian Schaal, schaal @it UG
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

class plugin_system_config_dns_ca extends plugin_base {

	var $module;
	var $form;
	var $tab;
	var $record_id;
	var $formdef;
	var $options;
	var $error = '';

	function onShow() {
		global $app;

		$pluginTpl = new tpl;
		$pluginTpl->newTemplate('templates/system_config_dns_ca_edit.htm');
		include 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_system_config.lng';
		$pluginTpl->setVar($wb);
		$ca_id = $app->functions->intval($_GET['id']);
		if(isset($_GET['action']) && ($_GET['action'] == 'edit') && $ca_id > 0) {
			$pluginTpl->setVar('edit_record', 1);
			$rec = $app->db->queryOneRecord("SELECT * FROM dns_ssl_ca WHERE id = ?", $ca_id);
			$pluginTpl->setVar('id', $rec['id']);
			$pluginTpl->setVar('ca_name', $rec['ca_name']);
			$pluginTpl->setVar('ca_issue', $rec['ca_issue']);
			$pluginTpl->setVar('ca_wildcard', $rec['ca_wildcard']);
			$pluginTpl->setVar('ca_critical', $rec['ca_critical']);
			$pluginTpl->setVar('ca_iodef', $rec['ca_iodef']);
			$pluginTpl->setVar('active', $rec['active']);
		} elseif(isset($_GET['action']) && ($_GET['action'] == 'save') && $ca_id > 0) {
			$pluginTpl->setVar('edit_record', 0);
			$pluginTpl->setVar('id', $ca_id);
			$pluginTpl->setVar('ca_name', $app->functions->htmlentities($_POST['ca_name']));
			$pluginTpl->setVar('ca_issue', $app->functions->htmlentities($_POST['ca_issue']));
			$pluginTpl->setVar('ca_wildcard', $app->functions->htmlentities($_POST['ca_wildcard']));
			$pluginTpl->setVar('ca_critical', $app->functions->htmlentities($_POST['ca_critical']));
			$pluginTpl->setVar('ca_iodef', $app->functions->htmlentities($_POST['ca_iodef']));
			$pluginTpl->setVar('active', $app->functions->htmlentities($_POST['active']));
		} else {
			$pluginTpl->setVar('edit_record', 0);
		}

		return $pluginTpl->grab();

	}

	function onUpdate() {
		global $app;

		$ca_id = $app->functions->intval($_GET['id']);
		if(isset($_GET['action']) && $_GET['action'] == 'save') {
			if($ca_id > 0) {
				$app->db->query("UPDATE dns_ssl_ca SET ca_name = ?, ca_issue = ?, ca_wildcard = ?, ca_iodef = ?, active = ? WHERE id = ?", $_POST['ca_name'], $_POST['ca_issue'], $_POST['ca_wildcard'], $_POST['ca_iodef'], $_POST['active'], $ca_id);
			} else {
				$app->db->query("INSERT INTO (sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, ca_name, ca_issue, ca_wildcard, ca_iodef, active) VALUES(1, 1, 'riud', 'riud', '', ?, ?, ?, ?, ?", $_POST['ca_name'], $_POST['ca_issue'], $_POST['ca_wildcard'], $_POST['ca_iodef'], $_POST['active']);
			}
		}
	}

}

?>
