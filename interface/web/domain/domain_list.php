<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/domain.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('domain');

$app->uses('listform_actions');

$app->listform_actions->SQLOrderBy = 'ORDER BY domain';
$app->listform_actions->onLoad();

?>