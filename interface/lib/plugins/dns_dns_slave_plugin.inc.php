<?php
/**
 * dns_dns_slave_plugin plugin
 *
 * @author Sergio Cambra <sergio@programatica.es> 2014
 */


class dns_dns_slave_plugin {

	var $plugin_name        = 'dns_dns_slave_plugin';
	var $class_name         = 'dns_dns_slave_plugin';

	/*
            This function is called when the plugin is loaded
    */
	function onLoad() {
		global $app;
		//Register for the events
		$app->plugin->registerEvent('dns:dns_slave:on_after_insert', 'dns_dns_slave_plugin', 'dns_dns_slave_edit');
		$app->plugin->registerEvent('dns:dns_slave:on_after_update', 'dns_dns_slave_plugin', 'dns_dns_slave_edit');
	}

	/*
		Function to change dns slave owner
    */
	function dns_dns_slave_edit($event_name, $page_form) {
		global $app, $conf;

		// make sure that the record belongs to the client group and not the admin group when a dmin inserts it
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$app->db->query("UPDATE dns_slave SET sys_groupid = ? WHERE id = ?", $client_group_id, $page_form->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE dns_slave SET sys_groupid = ? WHERE id = ?", $client_group_id, $page_form->id);
		}

		//** When the client group has changed, change also the owner of the record if the owner is not the admin user
		if($page_form->oldDataRecord && $page_form->oldDataRecord["client_group_id"] != $page_form->dataRecord["client_group_id"] && $page_form->dataRecord["sys_userid"] != 1) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $client_group_id);
			if($tmp["userid"] > 0) {
				$app->db->query("UPDATE dns_slave SET sys_userid = ? WHERE id = ?", $tmp["userid"], $page_form->id);
			}
		}
	}

}
