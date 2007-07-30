<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/dns_soa.list.php";

/******************************************
* End Form configuration
******************************************/

// Checking module permissions
if(!stristr($_SESSION["s"]["user"]["modules"],'dns')) {
	header("Location: ../index.php");
	exit;
}

$app->uses('listform_actions');
// $app->listform_actions->SQLExtWhere = "access = 'REJECT'";

$app->listform_actions->onLoad();


?>