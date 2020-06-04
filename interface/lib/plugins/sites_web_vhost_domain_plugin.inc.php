<?php
/**
 * sites_web_vhost_domain_plugin plugin
 *
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
 */


class sites_web_vhost_domain_plugin {

	var $plugin_name        = 'sites_web_vhost_domain_plugin';
	var $class_name         = 'sites_web_vhost_domain_plugin';

	// TODO: This function is a duplicate from the one in interface/web/sites/web_domain_edit.php
	//       There should be a single "token replacement" function to be called from modules and
	//  from the main code.
	// Returna a "3/2/1" path hash from a numeric id '123'
	function id_hash($id, $levels) {
		$hash = "" . $id % 10 ;
		$id /= 10 ;
		$levels -- ;
		while ( $levels > 0 ) {
			$hash .= "/" . $id % 10 ;
			$id /= 10 ;
			$levels-- ;
		}
		return $hash;
	}

	/*
            This function is called when the plugin is loaded
    */
	function onLoad() {
		global $app;
		//Register for the events
		$app->plugin->registerEvent('sites:web_vhost_domain:on_after_insert', 'sites_web_vhost_domain_plugin', 'sites_web_vhost_domain_edit');
		$app->plugin->registerEvent('sites:web_vhost_domain:on_after_update', 'sites_web_vhost_domain_plugin', 'sites_web_vhost_domain_edit');
	}

	/*
		Function to create the sites_web_domain rule and insert it into the custom rules
    */
	function sites_web_vhost_domain_edit($event_name, $page_form) {
		global $app, $conf;

		$vhostdomain_type = 'domain';
		if($page_form->dataRecord['type'] == 'vhostalias') $vhostdomain_type = 'aliasdomain';
		elseif($page_form->dataRecord['type'] == 'vhostsubdomain') $vhostdomain_type = 'subdomain';
		
		// make sure that the record belongs to the clinet group and not the admin group when a dmin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = ?, sys_perm_group = 'ru' WHERE domain_id = ?", $client_group_id, $page_form->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = ?, sys_perm_group = 'riud' WHERE domain_id = ?", $client_group_id, $page_form->id);
		}
		// Get configuration for the web system
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($app->functions->intval($page_form->dataRecord['server_id']), 'web');
		if(isset($app->tform) && is_object($app->tform)) $web_rec = $app->tform->getDataRecord($page_form->id);
		else $web_rec = $app->remoting_lib->getDataRecord($page_form->id);
		
		if($vhostdomain_type == 'domain') {
			$document_root = str_replace("[website_id]", $page_form->id, $web_config["website_path"]);
			$document_root = str_replace("[website_idhash_1]", $this->id_hash($page_form->id, 1), $document_root);
			$document_root = str_replace("[website_idhash_2]", $this->id_hash($page_form->id, 1), $document_root);
			$document_root = str_replace("[website_idhash_3]", $this->id_hash($page_form->id, 1), $document_root);
			$document_root = str_replace("[website_idhash_4]", $this->id_hash($page_form->id, 1), $document_root);

			// get the ID of the client
			if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
				$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
				$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ?", $client_group_id);
				$client_id = $app->functions->intval($client["client_id"]);
			} elseif (isset($page_form->dataRecord["client_group_id"])) {
				$client_group_id = $page_form->dataRecord["client_group_id"];
				$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ?", $app->functions->intval(@$page_form->dataRecord["client_group_id"]));
				$client_id = $app->functions->intval($client["client_id"]);
			} else {
				$tmp = $app->db->queryOneRecord('SELECT sys_groupid FROM web_domain WHERE domain_id = ?',$page_form->id);
				$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ?", $app->functions->intval($tmp['sys_groupid']));
				$client_id = $app->functions->intval($client["client_id"]);
			}

			$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $client_group_id);
			$client_user_id = $app->functions->intval(($tmp['userid'] > 0)?$tmp['userid']:1);

			// Set the values for document_root, system_user and system_group
			$system_user     = 'web'.$page_form->id;
			$system_group     = 'client'.$client_id;

			$document_root     = str_replace("[client_id]", $client_id, $document_root);
			$document_root    = str_replace("[client_idhash_1]", $this->id_hash($client_id, 1), $document_root);
			$document_root    = str_replace("[client_idhash_2]", $this->id_hash($client_id, 2), $document_root);
			$document_root    = str_replace("[client_idhash_3]", $this->id_hash($client_id, 3), $document_root);
			$document_root    = str_replace("[client_idhash_4]", $this->id_hash($client_id, 4), $document_root);
			
			if($event_name == 'sites:web_vhost_domain:on_after_update') {
				if(($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) &&  isset($page_form->dataRecord["client_group_id"]) && $page_form->dataRecord["client_group_id"] != $page_form->oldDataRecord["sys_groupid"]) {

					$sql = "UPDATE web_domain SET system_user = ?, system_group = ?, document_root = ? WHERE domain_id = ?";
					$app->db->query($sql, $system_user, $system_group, $document_root, $page_form->id);

					// Update the FTP user(s) too
					$records = $app->db->queryAllRecords("SELECT ftp_user_id FROM ftp_user WHERE parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('ftp_user', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid'], "uid" => $system_user, "gid" => $system_group, "dir" => $document_root), 'ftp_user_id', $app->functions->intval($rec['ftp_user_id']));
					}
					unset($records);
					unset($rec);

					// Update the webdav user(s) too
					$records = $app->db->queryAllRecords("SELECT webdav_user_id FROM webdav_user WHERE parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('webdav_user', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid']), 'webdav_user_id', $app->functions->intval($rec['webdav_user_id']));
					}
					unset($records);
					unset($rec);

					// Update the web folder(s) too
					$records = $app->db->queryAllRecords("SELECT web_folder_id FROM web_folder WHERE parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('web_folder', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid']), 'web_folder_id', $app->functions->intval($rec['web_folder_id']));
					}
					unset($records);
					unset($rec);

					//* Update all web folder users
					$records = $app->db->queryAllRecords("SELECT web_folder_user.web_folder_user_id FROM web_folder_user, web_folder WHERE web_folder_user.web_folder_id = web_folder.web_folder_id AND web_folder.parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('web_folder_user', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid']), 'web_folder_user_id', $app->functions->intval($rec['web_folder_user_id']));
					}
					unset($records);
					unset($rec);

					// Update the Shell user(s) too
					$records = $app->db->queryAllRecords("SELECT shell_user_id FROM shell_user WHERE parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('shell_user', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid'], "puser" => $system_user, "pgroup" => $system_group, "dir" => $document_root), 'shell_user_id', $app->functions->intval($rec['shell_user_id']));
					}
					unset($records);
					unset($rec);

					// Update the cron(s) too
					$records = $app->db->queryAllRecords("SELECT id FROM cron WHERE parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('cron', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid']), 'id', $app->functions->intval($rec['id']));
					}
					unset($records);
					unset($rec);

					//* Update all subdomains and alias domains
					$records = $app->db->queryAllRecords("SELECT domain_id, `domain`, `type`, `web_folder` FROM web_domain WHERE parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$update_columns = array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid']);
						if($rec['type'] == 'vhostsubdomain' || $rec['type'] == 'vhostalias') {
							$php_open_basedir = str_replace("[website_path]/web", $document_root.'/'.$rec['web_folder'], $web_config["php_open_basedir"]);
							$php_open_basedir = str_replace("[website_domain]/web", $rec['domain'].'/'.$rec['web_folder'], $php_open_basedir);
							$php_open_basedir = str_replace("[website_path]", $document_root, $php_open_basedir);
							$php_open_basedir = str_replace("[website_domain]", $rec['domain'], $php_open_basedir);

							$update_columns["document_root"] = $document_root;
							$update_columns["php_open_basedir"] = $php_open_basedir;
						}
						$app->db->datalogUpdate('web_domain', $update_columns, 'domain_id', $rec['domain_id']);
					}
					unset($records);
					unset($rec);

					//* Update all databases
					$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('web_database', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid']), 'database_id', $app->functions->intval($rec['database_id']));
					}

					//* Update all database users
					$records = $app->db->queryAllRecords("SELECT web_database_user.database_user_id FROM web_database_user, web_database WHERE web_database_user.database_user_id IN (web_database.database_user_id, web_database.database_ro_user_id) AND web_database.parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('web_database_user', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid']), 'database_user_id', $app->functions->intval($rec['database_user_id']));
					}
					unset($records);
					unset($rec);

					// Update APS instances
					$records = $app->db->queryAllRecords("SELECT instance_id FROM aps_instances_settings WHERE name = 'main_domain' AND value = ?", $page_form->oldDataRecord["domain"]);
					if(is_array($records) && !empty($records)){
						foreach($records as $rec){
							$app->db->datalogUpdate('aps_instances', array("sys_userid" => $web_rec['sys_userid'], "sys_groupid" => $web_rec['sys_groupid'], "customer_id" => $client_id), 'id', $rec['instance_id']);
						}
					}
					unset($records);
					unset($rec);

				}

				//* If the domain name has been changed, we will have to change all subdomains + APS instances
				if(!empty($page_form->dataRecord["domain"]) && !empty($page_form->oldDataRecord["domain"]) && $app->functions->idn_encode($page_form->dataRecord["domain"]) != $page_form->oldDataRecord["domain"]) {
					//* Change SSL Domain
					$tmp=$app->db->queryOneRecord("SELECT ssl_domain FROM web_domain WHERE domain_id = ?", $page_form->id);
					if($tmp['ssl_domain'] != '') {
						$plain=str_replace($page_form->oldDataRecord["domain"], $app->functions->idn_encode($page_form->dataRecord["domain"]), $tmp);
						$app->db->query("UPDATE web_domain SET ssl_domain = ? WHERE domain_id = ?", $plain, $page_form->id);
					}

					$records = $app->db->queryAllRecords("SELECT domain_id,domain FROM web_domain WHERE (type = 'subdomain' OR type = 'vhostsubdomain' OR type = 'vhostalias') AND domain LIKE ?", "%." . $page_form->oldDataRecord["domain"]);
					foreach($records as $rec) {
						$subdomain = str_replace($page_form->oldDataRecord["domain"], $app->functions->idn_encode($page_form->dataRecord["domain"]), $rec['domain']);
						$app->db->datalogUpdate('web_domain', array("domain" => $subdomain), 'domain_id', $rec['domain_id']);
					}
					unset($records);
					unset($rec);
					unset($subdomain);

					// Update APS instances
					$records = $app->db->queryAllRecords("SELECT id, instance_id FROM aps_instances_settings WHERE name = 'main_domain' AND value = ?", $page_form->oldDataRecord["domain"]);
					if(is_array($records) && !empty($records)){
						foreach($records as $rec){
							$app->db->datalogUpdate('aps_instances_settings', array("value" => $app->functions->idn_encode($page_form->dataRecord["domain"])), 'id', $rec['id']);
						}
					}
					unset($records);
					unset($rec);
				}

				//* Set allow_override if empty
				if($web_rec['allow_override'] == '') {
					$sql = "UPDATE web_domain SET allow_override = ? WHERE domain_id = ?";
					$app->db->query($sql, $web_config["htaccess_allow_override"], $page_form->id);
				}

				//* Set php_open_basedir if empty or domain or client has been changed
				if(empty($web_rec['php_open_basedir']) ||
					(!empty($page_form->dataRecord["domain"]) && !empty($page_form->oldDataRecord["domain"]) && $app->functions->idn_encode($page_form->dataRecord["domain"]) != $page_form->oldDataRecord["domain"])) {
					$php_open_basedir = $web_rec['php_open_basedir'];
					$php_open_basedir = str_replace($page_form->oldDataRecord['domain'], $web_rec['domain'], $php_open_basedir);
					$sql = "UPDATE web_domain SET php_open_basedir = ? WHERE domain_id = ?";
					$app->db->query($sql, $php_open_basedir, $page_form->id);
				}
				if(empty($web_rec['php_open_basedir']) ||
					(isset($page_form->dataRecord["client_group_id"]) && $page_form->dataRecord["client_group_id"] != $page_form->oldDataRecord["sys_groupid"])) {
					$document_root = str_replace("[client_id]", $client_id, $document_root);
					$php_open_basedir = str_replace("[website_path]", $document_root, $web_config["php_open_basedir"]);
					$php_open_basedir = str_replace("[website_domain]", $web_rec['domain'], $php_open_basedir);
					$sql = "UPDATE web_domain SET php_open_basedir = ? WHERE domain_id = ?";
					$app->db->query($sql, $php_open_basedir, $page_form->id);
				}

				//* Change database backup options when web backup options have been changed
				if(isset($page_form->dataRecord['backup_interval']) && ($page_form->dataRecord['backup_interval'] != $page_form->oldDataRecord['backup_interval'] || $page_form->dataRecord['backup_copies'] != $page_form->oldDataRecord['backup_copies'] || $page_form->dataRecord['backup_format_web'] != $page_form->oldDataRecord['backup_format_web'] || $page_form->dataRecord['backup_format_db'] != $page_form->oldDataRecord['backup_format_db'])) {
					//* Update all databases
					$backup_interval = $page_form->dataRecord['backup_interval'];
					$backup_copies = $app->functions->intval($page_form->dataRecord['backup_copies']);
					$backup_format_web = $page_form->dataRecord['backup_format_web'];
					$backup_format_db = $page_form->dataRecord['backup_format_db'];
					$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE parent_domain_id = ".$page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('web_database', array("backup_interval" => $backup_interval, "backup_copies" => $backup_copies, "backup_format_web" => $backup_format_web, "backup_format_db" => $backup_format_db), 'database_id', $rec['database_id']);
					}
					unset($records);
					unset($rec);
					unset($backup_copies);
					unset($backup_interval);
                    unset($backup_format_web);
                    unset($backup_format_db);
				}

				//* Change vhost subdomain and alias ip/ipv6 if domain ip/ipv6 has changed
				if(isset($page_form->dataRecord['ip_address']) && ($page_form->dataRecord['ip_address'] != $page_form->oldDataRecord['ip_address'] || $page_form->dataRecord['ipv6_address'] != $page_form->oldDataRecord['ipv6_address'])) {
					$records = $app->db->queryAllRecords("SELECT domain_id FROM web_domain WHERE (type = 'vhostsubdomain' OR type = 'vhostalias') AND parent_domain_id = ?", $page_form->id);
					foreach($records as $rec) {
						$app->db->datalogUpdate('web_domain', array("ip_address" => $web_rec['ip_address'], "ipv6_address" => $web_rec['ipv6_address']), 'domain_id', $rec['domain_id']);
					}
					unset($records);
					unset($rec);
				}
			} else {
				$php_open_basedir    = str_replace("[website_path]", $document_root, $web_config["php_open_basedir"]);
				$php_open_basedir    = str_replace("[website_domain]", $app->functions->idn_encode($page_form->dataRecord['domain']), $php_open_basedir);
				$htaccess_allow_override  = $web_config["htaccess_allow_override"];
				
				$sql = "UPDATE web_domain SET system_user = ?, system_group = ?, document_root = ?, allow_override = ?, php_open_basedir = ?  WHERE domain_id = ?";
				$app->db->query($sql, $system_user, $system_group, $document_root, $htaccess_allow_override, $php_open_basedir, $page_form->id);
			}
		} else {
			if(isset($page_form->dataRecord["parent_domain_id"]) && $page_form->dataRecord["parent_domain_id"] != $page_form->oldDataRecord["parent_domain_id"]) {
				$parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = ?", $page_form->dataRecord['parent_domain_id']);

				// Set the values for document_root, system_user and system_group
				$system_user = $parent_domain['system_user'];
				$system_group = $parent_domain['system_group'];
				$document_root = $parent_domain['document_root'];
				$php_open_basedir = str_replace("[website_path]/web", $document_root.'/'.$page_form->dataRecord['web_folder'], $web_config["php_open_basedir"]);
				$php_open_basedir = str_replace("[website_domain]/web", $app->functions->idn_encode($page_form->dataRecord['domain']).'/'.$page_form->dataRecord['web_folder'], $php_open_basedir);
				$php_open_basedir = str_replace("[website_path]", $document_root, $php_open_basedir);
				$php_open_basedir = str_replace("[website_domain]", $app->functions->idn_encode($page_form->dataRecord['domain']), $php_open_basedir);
				$htaccess_allow_override = $parent_domain['allow_override'];
				$sql = "UPDATE web_domain SET sys_groupid = ?,system_user = ?, system_group = ?, document_root = ?, allow_override = ?, php_open_basedir = ? WHERE domain_id = ?";
				$app->db->query($sql, $parent_domain['sys_groupid'], $system_user, $system_group, $document_root, $htaccess_allow_override, $php_open_basedir, $page_form->id);
			}
		}
	}

}
