<?php
/**
 * mail_mail_domain_plugin plugin
 *
 * @author Sergio Cambra <sergio@programatica.es> 2014
 */


class mail_mail_domain_plugin {

	var $plugin_name        = 'mail_mail_domain_plugin';
	var $class_name         = 'mail_mail_domain_plugin';

	/*
            This function is called when the plugin is loaded
	 */
	function onLoad() {
		global $app;

		//Register for the events
		$app->plugin->registerEvent('mail:mail_domain:on_after_insert', 'mail_mail_domain_plugin', 'mail_mail_domain_edit');
		$app->plugin->registerEvent('mail:mail_domain:on_after_update', 'mail_mail_domain_plugin', 'mail_mail_domain_edit');
	}

	/*
		Function to create the sites_web_domain rule and insert it into the custom rules
	 */
	function mail_mail_domain_edit($event_name, $page_form) {
		global $app, $conf;
		
		$domain = $app->functions->idn_encode($page_form->dataRecord['domain']);

		// make sure that the record belongs to the client group and not the admin group when admin inserts it
		// also make sure that the user can not delete entry created by an admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$updates = "sys_groupid = ?, sys_perm_group = 'ru'";
			$update_params = array($client_group_id);
			if ($event_name == 'mail:mail_domain:on_after_update') {
				$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $client_group_id);
				$client_user_id = ($tmp['userid'] > 0)?$tmp['userid']:1;
				$updates .= ", sys_userid = ?";
				$update_params[] = $client_user_id;
			}
			$update_params[] = $page_form->id;
			$app->db->query("UPDATE mail_domain SET " . $updates . " WHERE domain_id = ?", true, $update_params);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$updates = "sys_groupid = ?, sys_perm_group = 'riud'";
			$update_params = array($client_group_id);
			if ($event_name == 'mail:mail_domain:on_after_update') {
				$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $client_group_id);
				$client_user_id = ($tmp['userid'] > 0)?$tmp['userid']:1;
				$updates .= ", sys_userid = ?";
				$update_params[] = $client_user_id;
			}
			$update_params[] = $page_form->id;
			$app->db->query("UPDATE mail_domain SET " . $updates . " WHERE domain_id = ?", true, $update_params);
		}

		//** If the domain name or owner has been changed, change the domain and owner in all mailbox records
		if($page_form->oldDataRecord && ($page_form->oldDataRecord['domain'] != $domain ||
				(isset($page_form->dataRecord['client_group_id']) && $page_form->oldDataRecord['sys_groupid'] != $page_form->dataRecord['client_group_id']))) {
			$app->uses('getconf');
			$mail_config = $app->getconf->get_server_config($page_form->dataRecord["server_id"], 'mail');

			$old_domain = $app->functions->idn_encode($page_form->oldDataRecord['domain']);

			//* Update the mailboxes
			$mailusers = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE email like ?", "%@" . $page_form->oldDataRecord['domain']);
			$sys_groupid = $app->functions->intval((isset($page_form->dataRecord['client_group_id']))?$page_form->dataRecord['client_group_id']:$page_form->oldDataRecord['sys_groupid']);
			$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $sys_groupid);
			$client_user_id = $app->functions->intval(($tmp['userid'] > 0)?$tmp['userid']:1);
			if(is_array($mailusers)) {
				foreach($mailusers as $rec) {
					// setting Maildir, Homedir, UID and GID
					$mail_parts = explode("@", $rec['email']);
					$maildir = str_replace("[domain]", $domain, $mail_config["maildir_path"]);
					$maildir = str_replace("[localpart]", $mail_parts[0], $maildir);
					$email = $mail_parts[0].'@'.$domain;

					// update spamfilter_users and spamfilter_wblist if email change
					$skip_spamfilter_users_update = false;
					if($email != $rec['email']) {
						$tmp_olduser = $app->db->queryOneRecord("SELECT id,fullname FROM spamfilter_users WHERE email = ?", $rec['email']);
						if($tmp_olduser['id'] > 0) {
							$tmp_newuser = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", $email);
							if($tmp_newuser['id'] > 0) {
								// There is a spamfilter_users for both old and new email, we'll update old wblist entries
								$tmp_wblist = $app->db->queryAllRecords("SELECT wblist_id FROM spamfilter_wblist WHERE rid = ?", $tmp_olduser['id']);
								foreach ($tmp_wblist as $tmp) {
									$update_data = array(
										'rid' => $tmp_newuser['id'],
										'sys_userid' => $client_user_id,
										'sys_groupid' => $sys_groupid,
									);
									$app->db->datalogUpdate('spamfilter_wblist', $update_data, 'wblist_id', $tmp['wblist_id']);
								}

								// now delete old spamfilter_users entry
								$app->db->datalogDelete('spamfilter_users', 'id', $tmp_olduser['id']);
							} else {
								$update_data = array(
									'email' => $email,
									'sys_userid' => $client_user_id,
									'sys_groupid' => $sys_groupid,
								);
								if($tmp_olduser['fullname'] == $app->functions->idn_decode($rec['email'])) {
									$update_data['fullname'] = $app->functions->idn_decode($email);
								}
								$app->db->datalogUpdate('spamfilter_users', $update_data, 'id', $tmp_olduser['id']);
								$skip_spamfilter_users_update = true;

								$tmp_wblist = $app->db->queryAllRecords("SELECT wblist_id FROM spamfilter_wblist WHERE rid = ?", $tmp_olduser['id']);
								$update_data = array(
									'sys_userid' => $client_user_id,
									'sys_groupid' => $sys_groupid,
								);
								foreach ($tmp_wblist as $tmp) {
									$app->db->datalogUpdate('spamfilter_wblist', $update_data, 'wblist_id', $tmp['wblist_id']);
								}
							}
						}

						$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", $email);
						if($tmp_user["id"] > 0) {
							// There is already a record that we will update
							if(!$skip_spamfilter_users_update) {
								$update_data = array(
									'sys_userid' => $client_user_id,
									'sys_groupid' => $sys_groupid,
								);
								$app->db->datalogUpdate('spamfilter_users', $update_data, 'id', $tmp_user['id']);
							}
						} else {
							// We create a new record
							$insert_data = array(
								"sys_userid" => $client_user_id,
								"sys_groupid" => $sys_groupid,
								"sys_perm_user" => 'riud',
								"sys_perm_group" => 'riud',
								"sys_perm_other" => '',
								"server_id" => $page_form->dataRecord["server_id"],
								"priority" => 5,
								"policy_id" => 0,
								"email" => $email,
								"fullname" => $app->functions->idn_decode($email),
								"local" => 'Y'
							);
							$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
						}
					}

					$app->db->datalogUpdate('mail_user', array("maildir" => $maildir, "email" => $email, "sys_userid" => $client_user_id, "sys_groupid" => $sys_groupid), 'mailuser_id', $rec['mailuser_id']);

				}
			}

			//* Update the aliases
			$forwardings = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE source like ? OR destination like ?", '%@' . $old_domain, '%@' . $old_domain);
			if(is_array($forwardings)) {
				foreach($forwardings as $rec) {
					$destination = str_replace($old_domain, $domain, $rec['destination']);
					$source = str_replace($old_domain, $domain, $rec['source']);

					// update spamfilter_users and spamfilter_wblist if source email changes
					$skip_spamfilter_users_update = false;
					if(strpos($rec['source'],'@'.$old_domain) && $source != $rec['source']) {
						$tmp_olduser = $app->db->queryOneRecord("SELECT id,fullname FROM spamfilter_users WHERE email = ?", $rec['source']);
						if($tmp_olduser['id'] > 0) {
							$tmp_newuser = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", $source);
							if($tmp_newuser['id'] > 0) {
								// There is a spamfilter_users for both old and new email, we'll update old wblist entries
								$tmp_wblist = $app->db->queryAllRecords("SELECT wblist_id FROM spamfilter_wblist WHERE rid = ?", $tmp_olduser['id']);
								foreach ($tmp_wblist as $tmp) {
									$update_data = array(
										'rid' => $tmp_newuser['id'],
										'sys_userid' => $client_user_id,
										'sys_groupid' => $sys_groupid,
									);
									$app->db->datalogUpdate('spamfilter_wblist', $update_data, 'wblist_id', $tmp['wblist_id']);
								}

								// now delete old spamfilter_users entry
								$app->db->datalogDelete('spamfilter_users', 'id', $tmp_olduser['id']);
							} else {
								$update_data = array(
									'email' => $source,
									'sys_userid' => $client_user_id,
									'sys_groupid' => $sys_groupid,
								);
								if($tmp_olduser['fullname'] == $app->functions->idn_decode($rec['source'])) {
									$update_data['fullname'] = $app->functions->idn_decode($source);
								}
								$app->db->datalogUpdate('spamfilter_users', $update_data, 'id', $tmp_olduser['id']);
								$skip_spamfilter_users_update = true;

								$tmp_wblist = $app->db->queryAllRecords("SELECT wblist_id FROM spamfilter_wblist WHERE rid = ?", $tmp_olduser['id']);
								$update_data = array(
									'sys_userid' => $client_user_id,
									'sys_groupid' => $sys_groupid,
								);
								foreach ($tmp_wblist as $tmp) {
									$app->db->datalogUpdate('spamfilter_wblist', $update_data, 'wblist_id', $tmp['wblist_id']);
								}
							}
						}


						$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", $source);
						if($tmp_user["id"] > 0) {
							// There is already a record that we will update
							if(!$skip_spamfilter_users_update) {
								$update_data = array(
									'sys_userid' => $client_user_id,
									'sys_groupid' => $sys_groupid,
								);
								$app->db->datalogUpdate('spamfilter_users', $update_data, 'id', $tmp_user['id']);
							}
						}

					}

					$app->db->datalogUpdate('mail_forwarding', array("source" => $source, "destination" => $destination, "sys_userid" => $client_user_id, "sys_groupid" => $sys_groupid), 'forwarding_id', $rec['forwarding_id']);
				}
			}

			//* Update the mailinglist
			$mailinglists = $app->db->queryAllRecords("SELECT * FROM mail_mailinglist WHERE domain = ?", $old_domain);
			if(is_array($mailinglists)) {
				foreach($mailinglists as $rec) {
					$update_data = array(
						'sys_userid' => $client_user_id,
						'sys_groupid' => $sys_groupid,
						'domain' => $domain,
						'email' => str_replace($old_domain, $domain, $rec['email']),
					);
					$app->db->datalogUpdate('mail_mailinglist', $update_data, 'mailinglist_id', $rec['mailinglist_id']);
				}
			}

			//* Update the mailget records
			$mail_gets = $app->db->queryAllRecords("SELECT mailget_id, destination FROM mail_get WHERE destination LIKE ?", "%@" . $page_form->oldDataRecord['domain']);
			if(is_array($mail_gets)) {
				foreach($mail_gets as $rec) {
					$destination = str_replace($page_form->oldDataRecord['domain'], $domain, $rec['destination']);
					$app->db->datalogUpdate('mail_get', array("destination" => $destination, "sys_userid" => $client_user_id, "sys_groupid" => $sys_groupid), 'mailget_id', $rec['mailget_id']);
				}
			}

			// Spamfilter policy
			$policy_id = $app->functions->intval($page_form->dataRecord["policy"]);

			// If domain changes, update spamfilter_users
			// and fire spamfilter_wblist_update events so rspamd files are rewritten
			if ($old_domain != $domain) {
				$tmp_users = $app->db->queryOneRecord("SELECT id,fullname FROM spamfilter_users WHERE email LIKE ?", '%@' . $old_domain);
				if(is_array($tmp_users)) {
					foreach ($tmp_users as $tmp_old) {
						$tmp_new = $app->db->queryOneRecord("SELECT id,fullname FROM spamfilter_users WHERE email = ?", '@' . $domain);
						if($tmp_new['id'] > 0) {
							// There is a spamfilter_users for both old and new domain, we'll update old wblist entries
							$update_data = array(
								'sys_userid' => $client_user_id,
								'sys_groupid' => $sys_groupid,
								'rid' => $tmp_new['id'],
							);
							$tmp_wblist = $app->db->queryAllRecords("SELECT wblist_id FROM spamfilter_wblist WHERE rid = ?", $tmp_old['id']);
							foreach ($tmp_wblist as $tmp) {
								$app->db->datalogUpdate('spamfilter_wblist', $update_data, 'wblist_id', $tmp['wblist_id']);
							}

							// now delete old spamfilter_users entry
							$app->db->datalogDelete('spamfilter_users', 'id', $tmp_old['id']);

							/// and update the new
							$update_data = array(
								'sys_userid' => $client_user_id,
								'sys_groupid' => $sys_groupid,
							);
							$app->db->datalogUpdate('spamfilter_users', $update_data, 'id', $tmp_old['id']);
						} else {
							$update_data = array(
								'sys_userid' => $client_user_id,
								'sys_groupid' => $sys_groupid,
								'email' => '@' . $domain,
							);
							if($tmp_old['fullname'] == '@' . $old_domain) {
								$update_data['fullname'] = '@' . $domain;
							}
							$app->db->datalogUpdate('spamfilter_users', $update_data, 'id', $tmp_old['id']);
						}
					}
				}
			}

			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", '@' . $domain);
			if(isset($tmp_user['id']) && $tmp_user['id'] > 0) {
				// There is already a record that we will update
				if($policy_id != $tmp_user['policy_id']) {
					$update_data = array(
						'policy_id' => $policy_id,
					);
					$app->db->datalogUpdate('spamfilter_users', $update_data, 'id', $tmp_user["id"]);
				}
			} else {
				// We create a new record
				$insert_data = array(
					"sys_userid" => $client_user_id,
					"sys_groupid" => $sys_groupid,
					"sys_perm_user" => 'riud',
					"sys_perm_group" => 'riud',
					"sys_perm_other" => '',
					"server_id" => $page_form->dataRecord["server_id"],
					"priority" => 5,
					"policy_id" => $policy_id,
					"email" => '@' . $domain,
					"fullname" => '@' . $domain,
					"local" => 'Y'
				);
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
			}

		} // end if domain name changed

	}

}
