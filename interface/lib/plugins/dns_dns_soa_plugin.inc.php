<?php
/**
 * dns_dns_soa_plugin plugin
 *
 * @author Sergio Cambra <sergio@programatica.es> 2014
 */


class dns_dns_soa_plugin {

	var $plugin_name        = 'dns_dns_soa_plugin';
	var $class_name         = 'dns_dns_soa_plugin';

	/*
            This function is called when the plugin is loaded
    */
	function onLoad() {
		global $app;
		//Register for the events
		$app->plugin->registerEvent('dns:dns_soa:on_after_insert', 'dns_dns_soa_plugin', 'dns_dns_soa_edit');
		$app->plugin->registerEvent('dns:dns_soa:on_after_update', 'dns_dns_soa_plugin', 'dns_dns_soa_edit');
	}

	/*
		Function to change dns soa owner
    */
	function dns_dns_soa_edit($event_name, $page_form) {
		global $app, $conf;

		if ($event_name == 'dns:dns_soa:on_after_update') {
			$tmp = $app->db->diffrec($page_form->oldDataRecord, $app->tform->getDataRecord($page_form->id));
			if($tmp['diff_num'] > 0) {
				// Update the serial number of the SOA record
				$soa = $app->db->queryOneRecord("SELECT serial FROM dns_soa WHERE id = ?", $page_form->id);
				$app->db->query("UPDATE dns_soa SET serial = ? WHERE id = ?", $app->validate_dns->increase_serial($soa["serial"]), $page_form->id);
			}

			//** When the client group has changed, change also the owner of the record if the owner is not the admin user
			if($page_form->oldDataRecord["client_group_id"] != $page_form->dataRecord["client_group_id"] && $page_form->dataRecord["sys_userid"] != 1) {
				$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
				$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $client_group_id);
				if($tmp["userid"] > 0) {
					$app->db->query("UPDATE dns_soa SET sys_userid = ? WHERE id = ?", $tmp["userid"], $page_form->id);
					$app->db->query("UPDATE dns_rr SET sys_userid = ? WHERE zone = ?", $tmp["userid"], $page_form->id);
				}
			}
		}

		// make sure that the record belongs to the client group and not the admin group when a dmin inserts it
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$app->db->query("UPDATE dns_soa SET sys_groupid = ?, sys_perm_group = 'ru' WHERE id = ?", $client_group_id, $page_form->id);
			// And we want to update all rr records too, that belong to this record
			$app->db->query("UPDATE dns_rr SET sys_groupid = ? WHERE zone = ?", $client_group_id, $page_form->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$app->db->query("UPDATE dns_soa SET sys_groupid = ?, sys_perm_group = 'riud' WHERE id = ?", $client_group_id, $page_form->id);
			// And we want to update all rr records too, that belong to this record
			$app->db->query("UPDATE dns_rr SET sys_groupid = ? WHERE zone = ?", $client_group_id, $page_form->id);
		}
	}

}
