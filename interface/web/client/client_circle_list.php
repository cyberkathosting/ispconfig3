<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/client_circle.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('client');

$app->uses('listform_actions');

$app->listform_actions->SQLOrderBy = 'ORDER BY client_circle.circle_name, client_circle.circle_id';
$app->listform_actions->onLoad();


?>
