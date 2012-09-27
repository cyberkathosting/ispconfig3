<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

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

$app->listform_actions->SQLOrderBy = 'ORDER BY company_name, contact_name, client_id';
$app->listform_actions->SQLExtWhere = "limit_client = 0";
$app->listform_actions->SQLExtSelect = ', client.country as countryiso';
$app->listform_actions->onLoad();


?>