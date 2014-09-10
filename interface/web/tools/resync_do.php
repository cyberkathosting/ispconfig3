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

    function onSubmit() {
        global $app, $conf, $interfaceConf;

		function query_server($table, $server_id, $server_type, $where = "WHERE active = 'y'", $active_only = true) {
			global $app;
			$server_name = array();
			if ( $server_id <= 0 ) { //* resync multiple server
				if ($active_only) {
					$tmp = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE ".$server_type."_server = 1 AND active = 1 AND mirror_server_id = 0");
				} else {
					$tmp = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE ".$server_type."_server = 1 AND mirror_server_id = 0");
				}
				foreach ($tmp as $server) {
					$tmp_id .= $server['server_id'].',';
					$server_name[$server['server_id']] = $server['server_name'];
				}
			}
			if ( isset($tmp_id) ) $server_id = rtrim($tmp_id,',');

			if ($active_only) {
				$sql = "SELECT * FROM ".$table." ".$where." AND server_id IN (".$server_id.")"; 
			} else { 
				$sql = "SELECT * FROM ".$table." ".$where; 
			}
			$records = $app->db->queryAllRecords($sql);

			return array($records, $server_name);
		}			

		//* websites
		if(isset($this->dataRecord['resync_sites']) && $this->dataRecord['resync_sites'] == 1) {
			$db_table = 'web_domain';
			$index_field = 'domain_id';
			$server_type = 'web';
			$server_id = $app->functions->intval($this->dataRecord['web_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg = '<b>Resynced Website:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['domain'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* ftp
		if(isset($this->dataRecord['resync_ftp']) && $this->dataRecord['resync_ftp'] == 1) {
			$db_table = 'ftp_user';
			$index_field = 'ftp_user_id';
			$server_type = 'web';
			$server_id = $app->functions->intval($this->dataRecord['ftp_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced FTP user:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['username'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* webdav
		if(isset($this->dataRecord['resync_webdav']) && $this->dataRecord['resync_webdav'] == 1) {
			$db_table = 'webdav_user';
			$index_field = 'webdav_user_id';
			$server_type = 'file';
			$server_id = $app->functions->intval($this->dataRecord['webdav_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced WebDav-User</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['username'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* shell
		if(isset($this->dataRecord['resync_shell']) && $this->dataRecord['resync_shell'] == 1) {
			$db_table = 'shell_user';
			$index_field = 'shell_user_id';
			$server_type = 'web';
			$server_id = $app->functions->intval($this->dataRecord['shell_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced Shell user:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['username'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* cron
		if(isset($this->dataRecord['resync_cron']) && $this->dataRecord['resync_cron'] == 1) {
			$db_table = 'cron';
			$index_field = 'id';
			$server_type = 'web';
			$server_id = $app->functions->intval($this->dataRecord['cron_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced Cronjob:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['command'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* database
		if(isset($this->dataRecord['resync_db']) && $this->dataRecord['resync_db'] == 1) {
			$db_table = 'web_database_user';
			$index_field = 'database_user_id';
			$server_type = 'db';
			$server_id = $app->functions->intval($this->dataRecord['db_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1');
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced Database User:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['database_user'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';

			$db_table = 'web_database';
			$index_field = 'database_id';
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$msg .= '<b>Resynced Database:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['database_name'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';

		}

		//* maildomains
		if(isset($this->dataRecord['resync_mail']) && $this->dataRecord['resync_mail'] == 1) {
			$db_table = 'mail_domain';
			$index_field = 'domain_id';
			$server_type = 'mail';
			$server_id = $app->functions->intval($this->dataRecord['mail_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced Maildomain:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['domain'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* mailbox
		if(isset($this->dataRecord['resync_mailbox']) && $this->dataRecord['resync_mailbox'] == 1) {
			$db_table = 'mail_user';
			$index_field = 'mailuser_id';
			$server_type = 'mail';
			$server_id = $app->functions->intval($this->dataRecord['mailbox_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced Mailbox:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['email'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';

			$db_table = 'mail_forwarding';
			$index_field = 'forwarding_id';
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced Alias</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* dns
		if(isset($this->dataRecord['resync_dns']) && $this->dataRecord['resync_dns'] == 1) {
			$db_table = 'dns_soa';
			$index_field = 'id';
			$server_type = 'dns';
			$server_id = $app->functions->intval($this->dataRecord['dns_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type, "WHERE active = 'Y'");
			$zone_records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced DNS zone</b><br>';
			if(is_array($zone_records) && !empty($zone_records)) {
				foreach($zone_records as $zone_rec) {
					if ($server_id == -1) $records = query_server('dns_rr', $server_id, $server_type, 'WHERE 1', false)[0]; else $records = query_server('dns_rr', $server_id, $server_type, "WHERE active = 'Y'")[0];
					$rr_count = 0;
					if (is_array($records)) {
						foreach($records as $rec) {
							$new_serial = $app->validate_dns->increase_serial($rec['serial']);
							$app->db->datalogUpdate('dns_rr', "serial = '".$new_serial."'", 'id', $rec['id']);
							$rr_count++;
						}
					} else { $msg .= 'no dns recordsesults<br>'; }
					$new_serial = $app->validate_dns->increase_serial($zone_rec['serial']);
					$app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $zone_rec['id']);
					$msg .= $zone_rec['origin'].' on '.$server_name[$zone_rec['server_id']].' with '.$rr_count.' records<br>';
				}
			} else { $msg .= 'no results<br>'; }
			$msg .= '<br>';
        }

		//* clients
		if(isset($this->dataRecord['resync_client']) && $this->dataRecord['resync_client'] == 1) {
        	$db_table = 'client';
        	$index_field = 'client_id';
        	$records = $app->db->queryAllRecords("SELECT * FROM ".$db_table);
			$msg .= '<b>Resynced clients</b><br>';
			if(is_array($records)) {
	        	$tform_def_file = '../client/form/client.tform.php';
    	    	$app->uses('tpl,tform,tform_actions');
        		$app->load('tform_actions');
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$tmp = new tform_actions;
					$tmp->id = $rec[$index_field];
					$tmp->dataRecord = $rec;
					$tmp->oldDataRecord = $rec;
					$app->plugin->raiseEvent('client:client:on_after_update', $tmp);
					$msg .= $rec['contact_name'].'<br>';
					unset($tmp);
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* vserver
		if(isset($this->dataRecord['resync_vserver']) && $this->dataRecord['resync_vserver'] == 1) {
			$db_table = 'openvz_vm';
			$index_field = 'vm_id';
			$server_type = 'vserver';
			$server_id = $app->functions->intval($this->dataRecord['vserver_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced vServer:</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$msg .= $rec['hostname'].' on '.$server_name[$rec['server_id']].'<br>';
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		//* firewall
		if(isset($this->dataRecord['resync_firewall']) && $this->dataRecord['resync_firewall'] == 1) {
			$db_table = 'iptables';
			$index_field = 'iptables_id';
			$server_type = 'firewall';
			$server_id = $app->functions->intval($this->dataRecord['firewall_server_id']);
			if ($server_id == -1) $tmp = query_server($db_table, $server_id, $server_type, 'WHERE 1', false); else $tmp = query_server($db_table, $server_id, $server_type);
			$records = $tmp[0];
			$server_name = $tmp[1];
			unset($tmp);
			$msg .= '<b>Resynced Firewall</b><br>';
			if(is_array($records)) {
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
				}
			} else { $msg .= 'no results<bg>'; }
			$msg .= '<br>';
		}

		echo $msg;
    }

}

$page = new page_action;
$page->onLoad();
?>
