<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/mail_mailinglist.list.php";

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
		$global_config = $app->getconf->get_global_config('mail');

		if($global_config['mailmailinglist_link'] == 'y') {
			$app->tpl->setVar('mailmailinglist_link', 1);
		} else {
			$app->tpl->setVar('mailmailinglist_link', 0);
		}

		parent::onShow();
	}

}

$list = new list_action;
$list->onLoad();


?>
