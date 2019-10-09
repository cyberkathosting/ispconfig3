<?php

/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_software_packages');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

// Check CSRF Token
if(count($_POST) > 0) {
	$app->auth->csrf_token_check('POST');
} else {
	$app->auth->csrf_token_check('GET');
}

$package_name = $_REQUEST['package'];
$install_server_id = $app->functions->intval($_REQUEST['server_id']);
$install_key = trim($_REQUEST['install_key']);

$package = $app->db->queryOneRecord("SELECT * FROM software_package WHERE package_name = ?", $package_name);

$install_key_verified = false;
$message_err = '';
$message_ok = '';

//* verify the key
if($package['package_installable'] == 'key' && $install_key != '') {

	$repo = $app->db->queryOneRecord("SELECT * FROM software_repo WHERE software_repo_id = ?", $package['software_repo_id']);

	$client = new SoapClient(null, array('location' => $repo['repo_url'],
			'uri'      => $repo['repo_url']));

	$install_key_verified = $client->check_installable($package_name, $install_key, $repo['repo_username'], $repo['repo_password']);

	if($install_key_verified == false) {
		//$install_key = '';
		$message_err = 'Verification of the key failed.';
	} else {
		// Store the verified key into the database
		$app->db->datalogUpdate('software_package', array("package_key" => $install_key), 'package_id', $package['package_id']);
	}
} else {
	$message_ok = 'Please enter the software key for the package.';
}

//* Install packages, if all requirements are fullfilled.
if($install_server_id > 0 && $package_name != '' && ($package['package_installable'] == 'yes' || $install_key_verified == true)) {
	$sql = "SELECT software_update_id, package_name, update_title FROM software_update WHERE type = 'full' AND package_name = ? ORDER BY v1 DESC, v2 DESC, v3 DESC, v4 DESC LIMIT 0,1";
	$tmp = $app->db->queryOneRecord($sql, $package_name);
	$software_update_id = $tmp['software_update_id'];

	//* if package requires a DB and there is no data for a db in config, then we create this data now
	if($package['package_requires_db'] == 'mysql') {
		$app->uses('ini_parser,getconf');

		$package_config_array = array();
		if(trim($package['package_config']) != '') {
			$package_config_array = $app->ini_parser->parse_ini_string(stripslashes($package['package_config']));
		}

		if(!isset($package_config_array['mysql'])) {
			$package_config_array['mysql'] = array( 'database_name' => 'ispapp'.$package['package_id'],
				'database_user' => 'ispapp'.$package['package_id'],
				'database_password' => md5(mt_rand()),
				'database_host' => 'localhost');
			$package_config_str = $app->ini_parser->get_ini_string($package_config_array);
			$package['package_config'] = $package_config_str;
			$app->db->datalogUpdate('software_package', array("package_config" => $package_config_str), 'package_id', $package['package_id']);
		}
	}

	//* If the packages requires a remote user
	if($package['package_remote_functions'] != '') {

		if(trim($package['package_config']) != '') {
			$package_config_array = $app->ini_parser->parse_ini_string(stripslashes($package['package_config']));
		}

		if(!isset($package_config_array['remote_api'])) {
			$remote_user = 'ispapp'.$package['package_id'];
			$remote_password = md5(mt_rand());
			$remote_functions = $package['package_remote_functions'];

			$package_config_array['remote_api'] = array(
				'remote_hostname' => $_SERVER['HTTP_HOST'],
				'remote_user'   => $remote_user,
				'remote_password'  => $remote_password
			);

			$package_config_str = $app->ini_parser->get_ini_string($package_config_array);
			$package['package_config'] = $package_config_str;
			$remote_password_md5 = md5($remote_password);
			$app->db->datalogUpdate('software_package', array("package_config" => $package_config_str), 'package_id', $package['package_id']);

			$sql = "INSERT INTO `remote_user` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `remote_username`, `remote_password`, `remote_functions`) VALUES
					(1, 1, 'riud', 'riud', '', ?, ?, ?)";
			$app->db->query($sql, $remote_user, $remote_password_md5, $remote_functions);

		}

	}

	//* Add the record to start the install process
	$insert_data = array(
		"package_name" => $package_name,
		"server_id" => $install_server_id,
		"software_update_id" => $software_update_id,
		"status" => 'installing'
	);
	$app->db->datalogInsert('software_update_inst', $insert_data, 'software_update_inst_id');
	$message_ok = 'Starting package installation '."<a href=\"#\" onclick=\"ISPConfig.submitForm('pageForm','admin/software_package_list.php');\">".$app->lng('next')."</a>";

}

if(count($_POST) > 2 && $install_key == '') {
	$message_ok = 'Please enter the software key.';
}

//* Show key input form
if($package['package_installable'] == 'key' && !$install_key_verified) {
	$insert_key = true;
} else {
	$insert_key = false;
}

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/software_package_install.htm');

$app->tpl->setVar('message_ok', $message_ok);
$app->tpl->setVar('message_err', $message_err);
$app->tpl->setVar('insert_key', $insert_key);
$app->tpl->setVar('install_key', $install_key);
$app->tpl->setVar('package_name', $package_name);
$app->tpl->setVar('server_id', $install_server_id);


include_once 'lib/lang/en_software_package_install.lng';
$app->tpl->setVar($wb);


$app->tpl_defaults();
$app->tpl->pparse();

?>
