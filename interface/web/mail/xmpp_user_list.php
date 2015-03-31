<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/xmpp_user.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('mail');

$app->load('listform_actions');


class list_action extends listform_actions {

	function onShow() {
		global $app, $conf;

		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('xmpp');

		parent::onShow();
	}

}

$list = new list_action;
$list->SQLOrderBy = 'ORDER BY xmpp_user.jid';
$list->onLoad();


?>
