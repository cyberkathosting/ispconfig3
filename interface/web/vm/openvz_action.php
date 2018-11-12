<?php
/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
All rights reserved.
*/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('vm');

$action = (isset($_POST['action']) && $_POST['action'] != '')?$_POST['action']:'show';
$vm_id = $app->functions->intval($_REQUEST['id']);
$error_msg = '';
$notify_msg = '';

if($vm_id == 0) die('Invalid VM ID');

if(isset($_POST) && count($_POST) > 1) {	
	//* CSRF Check
	$app->auth->csrf_token_check();
}
$vm = $app->db->queryOneRecord("SELECT server_id, veid FROM openvz_vm WHERE vm_id = ?", $vm_id);
$veid = $app->functions->intval($vm['veid']);
$server_id = $app->functions->intval($vm['server_id']);

//* Loading classes
$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/openvz_action.htm');

//* load language file
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_openvz_action.lng';
include_once $lng_file;
$app->tpl->setVar($wb);

$app->tpl->setVar('id', $vm_id);
$app->tpl->setVar('veid', $veid);

$options = array('start_option_enabled'=>'', 'stop_option_enabled'=>'', 'restart_option_enabled'=>'', 'ostemplate_option_enabled'=>'');


//* Show the action select page
if($action == 'show') {

	$options['start_option_enabled'] = 'checked="checked"';

} elseif ($action == 'start') {

	//* Start the virtual machine
	$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
		"VALUES (?, UNIX_TIMESTAMP(), 'openvz_start_vm', ?, 'pending', '')";
	$app->db->query($sql, $server_id, $veid);

	$app->tpl->setVar('msg', $wb['start_exec_txt']);
	$options['start_option_enabled'] = 'checked="checked"';

} elseif ($action == 'stop') {

	//* Stop the virtual machine
	$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
		"VALUES (?, UNIX_TIMESTAMP(), 'openvz_stop_vm', ?, 'pending', '')";
	$app->db->query($sql, $server_id, $veid);

	$app->tpl->setVar('msg', $wb['stop_exec_txt']);
	$options['stop_option_enabled'] = 'checked="checked"';

} elseif ($action == 'restart') {

	//* Restart the virtual machine
	$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
		"VALUES (?, UNIX_TIMESTAMP(), 'openvz_restart_vm', ?, 'pending', '')";
	$app->db->query($sql, $server_id, $veid);

	$app->tpl->setVar('msg', $wb['restart_exec_txt']);
	$options['restart_option_enabled'] = 'checked="checked"';

} elseif ($action == 'ostemplate') {

	$ostemplate_name = $_POST['ostemplate_name'];

	if(!preg_match("/^[a-zA-Z0-9\.\-\_]{1,50}$/i", $ostemplate_name)) {
		$error_msg .= $wb['ostemplate_name_error'].'<br />';
		$app->tpl->setVar('ostemplate_name', $ostemplate_name);
	}

	//* Quote name

	//* Check for duplicates
	$tmp = $app->db->queryOneRecord("SELECT count(ostemplate_id) as number FROM openvz_ostemplate WHERE template_file = ?", $ostemplate_name);
	if($tmp['number'] > 0) $error_msg .= $wb['ostemplate_name_unique_error'].'<br />';
	unset($tmp);

	if($error_msg == '') {
		//* Create ostemplate action
		$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
			"VALUES (?, UNIX_TIMESTAMP(), 'openvz_create_ostpl', ?, 'pending', '')";
		$app->db->query($sql, $server_id, $veid.":".$ostemplate_name);

		//* Create a record in the openvz_ostemplate table
		$sql = "INSERT INTO `openvz_ostemplate` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `template_name`, `template_file`, `server_id`, `allservers`, `active`, `description`)
		VALUES(1, 1, 'riud', 'riud', '', ?, ?, ?, 'n', 'y', '')";
		$app->db->query($sql, $ostemplate_name, $ostemplate_name, $server_id);

		$app->tpl->setVar('msg', $wb['ostemplate_exec_txt']);
		$options['ostemplate_option_enabled'] = 'checked="checked"';
	}

} else {
	$error_msg = $app->lng('Unknown action');
	$app->error($error_msg);
}

$app->tpl->setVar($options);
$app->tpl->setVar('error', $error_msg);

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('openvz_action');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

$app->tpl_defaults();
$app->tpl->pparse();



?>
