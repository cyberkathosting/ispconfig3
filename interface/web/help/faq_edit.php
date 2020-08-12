<?php

// Set the path to the form definition file.
$tform_def_file = 'form/faq.tform.php';

// include the core configuration and application classes
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

// Check the  module permissions and redirect if not allowed.
$app->auth->check_module_permissions('admin');

// Do not allow FAQ editor in DEMO mode
if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

// Load the templating and form classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

// Create a class page_action that extends the tform_actions base class
class page_action extends tform_actions {

	//* Customisations for the page actions will be defined here

}

// Create the new page object
$page = new page_action();

// Start the page rendering and action handling
$page->onLoad();

?>
