<?php
/**
 * clients_template_plugin plugin
 *
 * @author Marius Cramer <m.cramer@pixcept.de> pixcept KG
 * @author (original tools.inc.php) Till Brehm, projektfarm Gmbh
 * @author (original tools.inc.php) Oliver Vogel www.muv.com
 */


class clients_template_plugin {

	var $plugin_name        = 'clients_template_plugin';
	var $class_name         = 'clients_template_plugin';


	/*
            This function is called when the plugin is loaded
    */
	function onLoad() {
		global $app;
		//Register for the events
		$app->plugin->registerEvent('client:client:on_after_insert', 'clients_template_plugin', 'apply_client_templates');
		$app->plugin->registerEvent('client:client:on_after_update', 'clients_template_plugin', 'apply_client_templates');
		$app->plugin->registerEvent('client:reseller:on_after_insert', 'clients_template_plugin', 'apply_client_templates');
		$app->plugin->registerEvent('client:reseller:on_after_update', 'clients_template_plugin', 'apply_client_templates');
	}

	function apply_client_templates($event_name, $page_form) {
		global $app;

		$app->uses('client_templates');
		$app->client_templates->apply_client_templates($page_form->id);
	}

}
