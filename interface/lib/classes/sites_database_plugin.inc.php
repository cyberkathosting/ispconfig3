<?php

/*
Copyright (c) 2012, Marius Cramer, pixcept KG
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

class sites_database_plugin {

	public function processDatabaseInsert($form_page) {
        global $app;
        
		if($form_page->dataRecord["parent_domain_id"] > 0) {
			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($form_page->dataRecord["parent_domain_id"]));
		
			//* The Database user shall be owned by the same group then the website
			$sys_groupid = $web['sys_groupid'];
        } else {
            $sys_groupid = $form_page->dataRecord['sys_groupid'];
        }
        

        if($form_page->dataRecord['database_user_id']) {
            // check if there has already been a database on this server with that user
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($form_page->dataRecord['server_id']) . "' AND (`database_user_id` = '" . intval($form_page->dataRecord['database_user_id']) . "' OR `database_ro_user_id` = '" . intval($form_page->dataRecord['database_user_id']) . "') AND `sys_groupid` = '" . intval($sys_groupid) . "'");
            
            if($check && $check['cnt'] < 1) {
                // we need to make a datalog insert for the database users that are connected to this database
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($form_page->dataRecord['database_user_id']) . "' AND `sys_groupid` = '" . intval($sys_groupid) . "'");
                if($db_user) {
                    $db_user['server_id'] = $form_page->dataRecord['server_id'];
                    $app->db->datalogSave('web_database_user', 'INSERT', 'database_user_id', $db_user['database_user_id'], array(), $db_user);
                }
            }
        }

        if($form_page->dataRecord['database_ro_user_id']) {
            // check if there has already been a database on this server with that user
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($form_page->dataRecord['server_id']) . "' AND (`database_user_id` = '" . intval($form_page->dataRecord['database_ro_user_id']) . "' OR `database_ro_user_id` = '" . intval($form_page->dataRecord['database_ro_user_id']) . "') AND `sys_groupid` = '" . intval($sys_groupid) . "'");
            
            if($check && $check['cnt'] < 1) {
                // we need to make a datalog insert for the database users that are connected to this database
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($form_page->dataRecord['database_ro_user_id']) . "' AND `sys_groupid` = '" . intval($sys_groupid) . "'");
                if($db_user) {
                    $db_user['server_id'] = $form_page->dataRecord['server_id'];
                    $app->db->datalogSave('web_database_user', 'INSERT', 'database_user_id', $db_user['database_user_id'], array(), $db_user);
                }
            }
        }
    }
    
    public function processDatabaseUpdate($form_page) {
        global $app;
        
        $old_record = $app->tform->getDataRecord($form_page->id);
        
        if($form_page->dataRecord["parent_domain_id"] > 0) {
            $web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($form_page->dataRecord["parent_domain_id"]));
        
            //* The Database user shall be owned by the same group then the website
            $sys_groupid = $web['sys_groupid'];
        } else {
            $sys_groupid = $form_page->dataRecord['sys_groupid'];
        }
        
        // check if database user has changed
        if($old_record['database_user_id'] && $old_record['database_user_id'] != $form_page->dataRecord['database_user_id'] && $old_record['database_user_id'] != $form_page->dataRecord['database_ro_user_id']) {
            // check if any database on the server still uses this one
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($form_page->dataRecord['server_id']) . "' AND (`database_user_id` = '" . intval($old_record['database_user_id']) . "' OR `database_ro_user_id` = '" . intval($old_record['database_user_id']) . "') AND `sys_groupid` = '" . intval($sys_groupid) . "' AND `database_id` != '" . intval($form_page->id) . "'");
            if($check['cnt'] < 1) {
                // send a datalog delete
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($old_record['database_user_id']) . "' AND `sys_groupid` = '" . intval($sys_groupid) . "'");
                if($db_user) {
                    $db_user['server_id'] = $form_page->dataRecord['server_id'];
                    $app->db->datalogSave('web_database_user', 'DELETE', 'database_user_id', $db_user['database_user_id'], $db_user, array());
                }
            }
        }
        // check if readonly database user has changed
        if($old_record['database_ro_user_id'] && $old_record['database_ro_user_id'] != $form_page->dataRecord['database_ro_user_id'] && $old_record['database_ro_user_id'] != $form_page->dataRecord['database_user_id']) {
            // check if any database on the server still uses this one
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($form_page->dataRecord['server_id']) . "' AND (`database_user_id` = '" . intval($old_record['database_ro_user_id']) . "' OR `database_ro_user_id` = '" . intval($old_record['database_ro_user_id']) . "') AND `sys_groupid` = '" . intval($sys_groupid) . "' AND `database_id` != '" . intval($form_page->id) . "'");
            if($check['cnt'] < 1) {
                // send a datalog delete
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($old_record['database_ro_user_id']) . "' AND `sys_groupid` = '" . intval($sys_groupid) . "'");
                if($db_user) {
                    $db_user['server_id'] = $form_page->dataRecord['server_id'];
                    $app->db->datalogSave('web_database_user', 'DELETE', 'database_user_id', $db_user['database_user_id'], $db_user, array());
                }
            }
        }
        
        if($form_page->dataRecord['database_user_id']) {
            // check if there has already been a database on this server with that user
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($form_page->dataRecord['server_id']) . "' AND (`database_user_id` = '" . intval($form_page->dataRecord['database_user_id']) . "' OR `database_ro_user_id` = '" . intval($form_page->dataRecord['database_user_id']) . "') AND `sys_groupid` = '" . intval($sys_groupid) . "'");
            
            if($check && $check['cnt'] < 1) {
                // we need to make a datalog insert for the database users that are connected to this database
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($form_page->dataRecord['database_user_id']) . "' AND `sys_groupid` = '" . intval($sys_groupid) . "'");
                if($db_user) {
                    $db_user['server_id'] = $form_page->dataRecord['server_id'];
                    $app->db->datalogSave('web_database_user', 'INSERT', 'database_user_id', $db_user['database_user_id'], array(), $db_user);
                }
            }
        }

        if($form_page->dataRecord['database_ro_user_id']) {
            // check if there has already been a database on this server with that user
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($form_page->dataRecord['server_id']) . "' AND (`database_user_id` = '" . intval($form_page->dataRecord['database_ro_user_id']) . "' OR `database_ro_user_id` = '" . intval($form_page->dataRecord['database_ro_user_id']) . "') AND `sys_groupid` = '" . intval($sys_groupid) . "'");
            
            if($check && $check['cnt'] < 1) {
                // we need to make a datalog insert for the database users that are connected to this database
                $db_user = $app->db->queryOneRecord("SELECT * FROM `web_database_user` WHERE `database_user_id` = '" . intval($form_page->dataRecord['database_ro_user_id']) . "' AND `sys_groupid` = '" . intval($sys_groupid) . "'");
                if($db_user) {
                    $db_user['server_id'] = $form_page->dataRecord['server_id'];
                    $app->db->datalogSave('web_database_user', 'INSERT', 'database_user_id', $db_user['database_user_id'], array(), $db_user);
                }
            }
        }
        
    }
    
    public function processDatabaseDelete($primary_id) {
        global $app;
        
        $old_record = $app->tform->getDataRecord($primary_id);
        if($old_record['database_user_id']) {
            // check if any database on the server still uses this one
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($old_record['server_id']) . "' AND (`database_user_id` = '" . intval($old_record['database_user_id']) . "' OR `database_ro_user_id` = '" . intval($old_record['database_user_id']) . "') AND `sys_groupid` = '" . intval($old_record['sys_groupid']) . "' AND `database_id` != '" . intval($primary_id) . "'");
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
            $check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_database` WHERE `server_id` = '" . intval($old_record['server_id']) . "' AND (`database_user_id` = '" . intval($old_record['database_ro_user_id']) . "' OR `database_ro_user_id` = '" . intval($old_record['database_ro_user_id']) . "') AND `sys_groupid` = '" . intval($old_record['sys_groupid']) . "' AND `database_id` != '" . intval($primary_id) . "'");
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

?>
