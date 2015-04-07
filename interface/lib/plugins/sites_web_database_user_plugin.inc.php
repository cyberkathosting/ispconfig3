<?php
/**
 * sites_web_database_user_plugin plugin
 *
 * @author Marius Cramer <m.cramer@pixcept.de> pixcept KG 2012
 */


class sites_web_database_user_plugin {

	var $plugin_name        = 'sites_web_database_user_plugin';
	var $class_name         = 'sites_web_database_user_plugin';

	/*
            This function is called when the plugin is loaded
    */
	function onLoad() {
		global $app;
		//Register for the events
		$app->plugin->registerEvent('sites:web_database_user:on_after_update', 'sites_web_database_user_plugin', 'sites_web_database_user_edit');
		$app->plugin->registerEvent('sites:web_database_user:on_after_insert', 'sites_web_database_user_plugin', 'sites_web_database_user_edit');
	}

	/*
		Function to create the sites_web_database_user rule and insert it into the custom rules
    */
	function sites_web_database_user_edit($event_name, $page_form) {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when a dmin inserts it
		// also make sure that the user can not delete entry created by an admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database_user SET sys_groupid = ?, sys_perm_group = 'ru' WHERE database_user_id = ?", $client_group_id, $page_form->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($page_form->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($page_form->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database_user SET sys_groupid = ?, sys_perm_group = 'riud' WHERE database_user_id = ?", $client_group_id, $page_form->id);
		}
	}

}
