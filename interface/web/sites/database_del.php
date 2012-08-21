<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

$list_def_file = "list/database.list.php";
$tform_def_file = "form/database.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->uses("tform_actions");
class page_action extends tform_actions {
	function onBeforeDelete() {
		global $app; $conf;
		if($app->tform->checkPerm($this->id,'d') == false) $app->error($app->lng('error_no_delete_permission'));
        
        $old_record = $app->tform->getDataRecord($this->id);
        if($old_record['database_user_id']) {
            // check if any database on the server still uses this one
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($old_record['server_id']) . "' AND (`database_user_id` = '" . intval($old_record['database_user_id']) . "' OR `database_ro_user_id` = '" . intval($old_record['database_user_id']) . "') AND `sys_groupid` = '" . intval($old_record['sys_groupid']) . "' AND `database_id` != '" . intval($this->id) . "'");
            if($check['cnt'] < 1) {
                // send a datalog delete
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($old_record['database_user_id']) . "' AND `sys_groupid` = '" . intval($old_record['sys_groupid']) . "'");
                if($db_user) {
                    $db_user['server_id'] = $old_record['server_id'];
                    $app->db->datalogSave('web_database_user', 'DELETE', 'database_user_id', $db_user['database_user_id'], $db_user, array());
                }
            }
        }
        if($old_record['database_ro_user_id']) {
            // check if any database on the server still uses this one
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($old_record['server_id']) . "' AND (`database_user_id` = '" . intval($old_record['database_ro_user_id']) . "' OR `database_ro_user_id` = '" . intval($old_record['database_ro_user_id']) . "') AND `sys_groupid` = '" . intval($old_record['sys_groupid']) . "' AND `database_id` != '" . intval($this->id) . "'");
            if($check['cnt'] < 1) {
                // send a datalog delete
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($old_record['database_ro_user_id']) . "' AND `sys_groupid` = '" . intval($old_record['sys_groupid']) . "'");
                if($db_user) {
                    $db_user['server_id'] = $old_record['server_id'];
                    $app->db->datalogSave('web_database_user', 'DELETE', 'database_user_id', $db_user['database_user_id'], $db_user, array());
                }
            }
        }
        
	}
}

$page = new page_action;
$page->onDelete();

?>