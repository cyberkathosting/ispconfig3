<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Path to the list definition file
$list_def_file = "list/faq_manage_questions_list.php";

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* Loading the class
$app->uses('listform_actions');

//* Optional limit
//$app->listform_actions->SQLExtWhere = "recipient_id = ".$_SESSION['s']['user']['userid'];

//* Start the form rendering and action ahndling
$app->listform_actions->onLoad();

?>
