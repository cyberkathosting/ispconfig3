<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/mail_blacklist.list.php";

/******************************************
* End Form configuration
******************************************/

if($_SESSION["s"]["user"]["typ"] != 'admin') $app->error('This function needs admin priveliges');

//* Check permissions for module
$app->auth->check_module_permissions('mail');

$app->uses('listform_actions');
$app->listform_actions->SQLExtWhere = "mail_access.access = 'REJECT'";

$app->listform_actions->onLoad();


?>
