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

		// make sure that the record belongs to the clinet group and not the admin group when a dmin inserts it
		// also make sure that the user can not delete entry created by an admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$updates = "sys_groupid = $client_group_id, sys_perm_group = 'ru'";
			if ($event_name == 'mail:mail_domain:on_after_update') {
				$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = $client_group_id");
				$client_user_id = ($tmp['userid'] > 0)?$tmp['userid']:1;
				$updates = "sys_userid = $client_user_id, $updates";
			}
			$app->db->query("UPDATE mail_domain SET $updates WHERE domain_id = ".$page_form->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$updates = "sys_groupid = $client_group_id, sys_perm_group = 'riud'";
			if ($event_name == 'mail:mail_domain:on_after_update') {
				$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = $client_group_id");
				$client_user_id = ($tmp['userid'] > 0)?$tmp['userid']:1;
				$updates = "sys_userid = $client_user_id, $updates";
			}
			$app->db->query("UPDATE mail_domain SET $updates WHERE domain_id = ".$page_form->id);
		}

		//** If the domain name or owner has been changed, change the domain and owner in all mailbox records
		if($page_form->oldDataRecord && ($page_form->oldDataRecord['domain'] != $page_form->dataRecord['domain'] ||
				(isset($page_form->dataRecord['client_group_id']) && $page_form->oldDataRecord['sys_groupid'] != $page_form->dataRecord['client_group_id']))) {
			$app->uses('getconf');
			$mail_config = $app->getconf->get_server_config($page_form->dataRecord["server_id"], 'mail');

			//* Update the mailboxes
			$mailusers = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE email like '%@".$app->db->quote($page_form->oldDataRecord['domain'])."'");
			$sys_groupid = $app->functions->intval((isset($page_form->dataRecord['client_group_id']))?$page_form->dataRecord['client_group_id']:$page_form->oldDataRecord['sys_groupid']);
			$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = $sys_groupid");
			$client_user_id = $app->functions->intval(($tmp['userid'] > 0)?$tmp['userid']:1);
			if(is_array($mailusers)) {
				foreach($mailusers as $rec) {
					// setting Maildir, Homedir, UID and GID
					$mail_parts = explode("@", $rec['email']);
					$maildir = str_replace("[domain]", $page_form->dataRecord['domain'], $mail_config["maildir_path"]);
					$maildir = str_replace("[localpart]", $mail_parts[0], $maildir);
					$maildir = $app->db->quote($maildir);
					$email = $app->db->quote($mail_parts[0].'@'.$page_form->dataRecord['domain']);
					$app->db->datalogUpdate('mail_user', "maildir = '$maildir', email = '$email', sys_userid = $client_user_id, sys_groupid = '$sys_groupid'", 'mailuser_id', $rec['mailuser_id']);
				}
			}

			//* Update the aliases
			$forwardings = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE source like '%@".$app->db->quote($page_form->oldDataRecord['domain'])."' OR destination like '%@".$app->db->quote($page_form->oldDataRecord['domain'])."'");
			if(is_array($forwardings)) {
				foreach($forwardings as $rec) {
					$destination = $app->db->quote(str_replace($page_form->oldDataRecord['domain'], $page_form->dataRecord['domain'], $rec['destination']));
					$source = $app->db->quote(str_replace($page_form->oldDataRecord['domain'], $page_form->dataRecord['domain'], $rec['source']));
					$app->db->datalogUpdate('mail_forwarding', "source = '$source', destination = '$destination', sys_userid = $client_user_id, sys_groupid = '$sys_groupid'", 'forwarding_id', $rec['forwarding_id']);
				}
			}

			//* Update the mailinglist
			$mailing_lists = $app->db->queryAllRecords("SELECT mailinglist_id FROM mail_mailinglist WHERE domain = '".$app->db->quote($page_form->oldDataRecord['domain'])."'");
			if(is_array($mailing_lists)) {
				foreach($mailing_lists as $rec) {
					$app->db->datalogUpdate('mail_mailinglist', "sys_userid = $client_user_id, sys_groupid = '$sys_groupid'", 'mailinglist_id', $rec['mailinglist_id']);
				}
			}

			//* Update the mailget records
			$mail_gets = $app->db->queryAllRecords("SELECT mailget_id FROM mail_get WHERE destination LIKE '%@".$app->db->quote($page_form->oldDataRecord['domain'])."'");
			if(is_array($mail_gets)) {
				foreach($mail_gets as $rec) {
					$destination = $app->db->quote(str_replace($page_form->oldDataRecord['domain'], $page_form->dataRecord['domain'], $rec['destination']));
					$app->db->datalogUpdate('mail_get', "destination = '$destination', sys_userid = $client_user_id, sys_groupid = '$sys_groupid'", 'mailget_id', $rec['mailget_id']);
				}
			}

			if ($page_form->oldDataRecord["domain"] != $page_form->dataRecord['domain']) {
				//* Delete the old spamfilter record
				$tmp = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '@".$app->db->quote($page_form->oldDataRecord["domain"])."'");
				$app->db->datalogDelete('spamfilter_users', 'id', $tmp["id"]);
				unset($tmp);
			}
			$app->db->query("UPDATE spamfilter_users SET email=REPLACE(email, '".$app->db->quote($page_form->oldDataRecord['domain'])."', '".$app->db->quote($page_form->dataRecord['domain'])."'), sys_userid = $client_user_id, sys_groupid = $sys_groupid WHERE email LIKE '%@".$app->db->quote($page_form->oldDataRecord['domain'])."'");

		} // end if domain name changed
	}

}
