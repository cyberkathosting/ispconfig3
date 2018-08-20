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

$list_def_file = "list/domain.list.php";
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

class page_action extends tform_actions {

	function onBeforeDelete() {
		global $app; $conf;

		//* load language file
		$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'.lng';
		include $lng_file;

		/*
		 * We can only delete domains if they are NOT in use
		 */
		$domain = $this->dataRecord['domain'];

		$sql = "SELECT id FROM dns_soa WHERE origin = ?";
		$res = $app->db->queryOneRecord($sql, $domain.".");
		if (is_array($res)){
			$app->error($wb['error_domain_in dnsuse']);
		}

		$sql = "SELECT id FROM dns_slave WHERE origin = ?";
		$res = $app->db->queryOneRecord($sql, $domain.".");
		if (is_array($res)){
			$app->error($wb['error_domain_in dnsslaveuse']);
		}

		$sql = "SELECT domain_id FROM mail_domain WHERE domain = ?";
		$res = $app->db->queryOneRecord($sql, $domain);
		if (is_array($res)){
			$app->error($wb['error_domain_in mailuse']);
		}

		$sql = "SELECT domain_id FROM web_domain WHERE (domain = ? AND type IN ('alias', 'vhost', 'vhostalias')) OR (domain LIKE ? AND type IN ('subdomain', 'vhostsubdomain'))";
		$res = $app->db->queryOneRecord($sql, $domain, '%.' . $domain);
		if (is_array($res)){
			$app->error($wb['error_domain_in webuse']);
		}
	}

}

$page = new page_action;
$page->onDelete();

?>
