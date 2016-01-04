<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/client.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('client');

$app->uses('listform_actions');

class list_action extends listform_actions {
	function onShow() {
		global $app;
		
		if(is_file(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php')){
			$app->tpl->setVar('has_robot', true);
		}
		
		parent::onShow();
	}

}

$list = new list_action;
$list->SQLOrderBy = 'ORDER BY client.company_name, client.contact_name, client.client_id';
$list->SQLExtWhere = "client.limit_client = 0";
$list->SQLExtSelect = ', LOWER(client.country) as countryiso';
$list->onLoad();
?>
