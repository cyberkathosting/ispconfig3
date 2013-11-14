<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Path to the list definition file
$list_def_file = "list/support_message.list.php";

//* Check permissions for module
$app->auth->check_module_permissions('help');

//* Loading the class
$app->uses('listform_actions');

//* Optional limit
$app->listform_actions->SQLExtWhere = "support_message.recipient_id = ".$_SESSION['s']['user']['userid'];

//* Start the form rendering and action ahndling
$app->listform_actions->onLoad();

?>
