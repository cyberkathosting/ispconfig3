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

$list_def_file = "list/xmpp_domain.list.php";
$tform_def_file = "form/xmpp_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onBeforeDelete() {
		global $app, $conf;

		$domain = $this->dataRecord['domain'];

		// Before we delete the email domain,
		// we will delete all depending records.
        $this->delete_accounts($domain);
        // and DNS entries
        $soa = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $domain.'.');
        if ( isset($soa) && !empty($soa) ) $this->remove_dns($soa);
	}

    private function delete_accounts($domain){
        global $app;
        // get all accounts
        $sql = "SELECT * FROM xmpp_user WHERE jid LIKE ? AND " . $app->tform->getAuthSQL('d');
        $users = $app->db->queryAllRecords($sql, '%@'.$domain);
        foreach($users AS $u)
            $app->db->datalogDelete('xmpp_user', 'xmppuser_id', $u['xmppuser_id']);
    }

    private function remove_dns($new_rr) {
        global $app;

        // purge all xmpp related rr-record
        $sql = "SELECT * FROM dns_rr WHERE zone = ? AND (name IN ? AND type = 'CNAME' OR name LIKE ? AND type = 'SRV')  AND " . $app->tform->getAuthSQL('r') . " ORDER BY serial DESC";
        $rec = $app->db->queryAllRecords($sql, $new_rr['zone'], array('xmpp', 'pubsub', 'proxy', 'anon', 'vjud', 'muc'), '_xmpp-%');
        if (is_array($rec[1])) {
            for ($i=0; $i < count($rec); ++$i)
                $app->db->datalogDelete('dns_rr', 'id', $rec[$i]['id']);
        }
    }

}

$page = new page_action;
$page->onDelete();

?>
