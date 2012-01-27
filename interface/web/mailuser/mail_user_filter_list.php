<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/mail_user_filter.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('mailuser');

$app->load('listform_actions');


class list_action extends listform_actions {
	
}

$list = new list_action;

$list->SQLExtWhere = "mailuser_id = ".$_SESSION['s']['user']['mailuser_id'];

$list->onLoad();


?>