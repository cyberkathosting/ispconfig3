<?php

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

// Path to the list definition file
$list_def_file = 'list/remote_user.list.php';

// Check the module permissions
if(!stristr($_SESSION['s']['user']['modules'], 'admin')) {
	header('Location: ../index.php');
	die();
}

// Loading the class
$app->uses('listform_actions');

// Optional limit
// $app->listform_actions->SQLExtWhere = 'type = 'alias'';

// Start the form rendering and action ahndling
$app->listform_actions->onLoad();

?>
