<?php

// Set the path to the form definition file.
$tform_def_file = 'form/remote_user.tform.php';

// include the core configuration and application classes
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

// Check the  module permissions and redirect if not allowed.
if(!stristr($_SESSION['s']['user']['modules'], 'admin')) {
	header('Location: ../index.php');
	die;
}

$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_remote_users');

// Disable this function in demo mode
if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

// Load the templating and form classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

// Create a class page_action that extends the tform_actions base class
class page_action extends tform_actions {

	//* Customisations for the page actions will be defined here
	function onShow() {
		global $app;

		// Translate the items, very bad trick... :( because the language file is not loaded yet when the form file gets parsed
		foreach($app->tform->formDef["tabs"]['remote_user']['fields']['remote_functions']['value'] as $key => $val) {
			$app->tform->formDef["tabs"]['remote_user']['fields']['remote_functions']['value'][$key] = $app->tform->lng($val).'<br>';
		}

		parent::onShow();
	}

}

// Create the new page object
$page = new page_action();

// Start the page rendering and action handling
$page->onLoad();

?>
