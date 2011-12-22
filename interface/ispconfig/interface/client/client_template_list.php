<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/client_template.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('client');
if(!$_SESSION["s"]["user"]["typ"] == 'admin') die('Client-Templates are only for Admins.');

$app->uses('listform_actions');
$app->listform_actions->onLoad();
?>
