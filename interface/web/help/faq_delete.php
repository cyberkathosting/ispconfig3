<?php

// From and List definition files
$list_def_file = 'list/faq_manage_questions_list.php';
$tform_def_file = 'form/faq.tform.php';

// Include the base libraries
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

// Check module permissions
if(!stristr($_SESSION['s']['user']['modules'], 'help')) {
	header('Location: ../index.php');
	die;
}

// Load the form
$app->uses('tform_actions');
$app->tform_actions->onDelete();

?>
