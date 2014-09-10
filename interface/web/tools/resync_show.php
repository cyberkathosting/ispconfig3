<?php
/*
Copyright (c) 2014, Florian Schaal, info@schaal-24.de
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

$tform_def_file = 'form/resync.tform.php';

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function get_servers($type) {
		global $app;

		$inactive_server = false;
		$tmp = $app->db->queryAllRecords("SELECT server_id, server_name, active FROM server WHERE ".$type."_server = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		foreach ($tmp as $server) {
			if ( $server['active'] == '0' ) {
				$server['server_name'] .= ' [inactive]';
				$inactive_server = true;
			}
			$options_servers .= "<option value='$server[server_id]'>$server[server_name]</option>";
		}
		if ( count ($tmp) > 1 ) {
			$options_servers = "<option value='0'>all active $type-server</option>" . $options_servers;
			if ($inactive_server) $options_servers .= "<option value='-1'>force all $type-server</option>";
		}

		return $options_servers;

	}

	function onShowEnd() {
		global $app, $conf;

		$servers = $this->get_servers('mail');
		$app->tpl->setVar('mail_server_id', $servers);
		if ( !empty($servers) ) $app->tpl->setVar('mail_server_found', 1);

		$servers = $this->get_servers('web');
		$app->tpl->setVar('web_server_id', $servers);
		$app->tpl->setVar('ftp_server_id', $servers);
		if ( !empty($servers) ) $app->tpl->setVar('web_server_found', 1);

		$servers = $this->get_servers('dns');
		$app->tpl->setVar('dns_server_id', $servers);
		if ( !empty($servers) ) $app->tpl->setVar('dns_server_found', 1);

		$servers = $this->get_servers('file');
		$app->tpl->setVar('file_server_id', $servers);
		if ( !empty($servers) ) $app->tpl->setVar('file_server_found', 1);

		$servers = $this->get_servers('db');
		$app->tpl->setVar('db_server_id', $servers);
		if ( !empty($servers) ) $app->tpl->setVar('db_server_found', 1);

		$servers = $this->get_servers('vserver');
		$app->tpl->setVar('vserver_server_id', $servers);
		if ( !empty($servers) ) $app->tpl->setVar('vserver_server_found', 1);

		$servers = $this->get_servers('firewall');
		$app->tpl->setVar('firewall_server_id', $servers);
		if ( !empty($servers) ) $app->tpl->setVar('firewall_server_found', 1);

		parent::onShowEnd();
	}

}

$page = new page_action;
$page->onLoad();

?>
