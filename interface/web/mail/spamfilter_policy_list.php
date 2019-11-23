<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/spamfilter_policy.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('mail');

$app->uses('listform_actions');

class list_action extends listform_actions {

	function onShow() {
		global $app, $conf;
		
		// get the config
		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		$content_filter = 'amavisd';
		if($mail_config['content_filter'] == 'rspamd'){
			$content_filter = 'rspamd';
		}
		$app->tpl->setVar("content_filter", $content_filter);

		parent::onShow();
	}

}

$list = new list_action;
//$list->SQLExtWhere = "wb = 'W'";
$list->onLoad();
?>