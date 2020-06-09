<?php
/*
Copyright (c) 2012, ISPConfig UG
Contributors: web wack creations,  http://www.web-wack.at
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
//require_once('classes/class.guicontroller.php');
$app->load('aps_guicontroller');

// Check the module permissions
$app->auth->check_module_permissions('sites');

// Load needed classes
$app->uses('tpl,tform');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/aps_install_package.htm');

// Load the language file
$lngfile = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_aps.lng';
require_once $lngfile;
$app->tpl->setVar($wb);
$app->load_language_file('web/sites/'.$lngfile);

// we will check only users, not admins
if($_SESSION["s"]["user"]["typ"] == 'user') {
	$app->tform->formDef['db_table_idx'] = 'id';
	$app->tform->formDef['db_table'] = 'aps_instances';
	if(!$app->tform->checkClientLimit('limit_aps')) {
		$app->error($app->lng("limit_aps_txt"));
	}
	if(!$app->tform->checkResellerLimit('limit_aps')) {
		$app->error('Reseller: '.$wb["limit_aps_txt"]);
	}
}


$adminflag = ($_SESSION['s']['user']['typ'] == 'admin') ? true : false;
$gui = new ApsGUIController($app);
$pkg_id = (isset($_GET['id'])) ? $_GET['id'] : '';

// Check if a newer version is available for the current package
// Note: It's intended that here is no strict ID check (see below)
if(isset($pkg_id))
{
	$newest_pkg_id = $gui->getNewestPackageID($pkg_id);
	if($newest_pkg_id != 0) $pkg_id = $newest_pkg_id;
}

// Make sure an integer ID is given
if(!isset($pkg_id) || !$gui->isValidPackageID($pkg_id, $adminflag))
	$app->error($app->lng('Invalid ID'));

// Get package details
$details = $gui->getPackageDetails($pkg_id);
if(isset($details['error'])) $app->error($details['error']);
$settings = $gui->getPackageSettings($pkg_id);
if(isset($settings['error'])) $app->error($settings['error']);

// Get domain list
$domains = array();
$domain_for_user = '';
if(!$adminflag) $domain_for_user = "AND (sys_userid = ? OR sys_groupid = ?)";
$domains_assoc = $app->db->queryAllRecords("SELECT domain FROM web_domain WHERE document_root != '' AND (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias') AND active = 'y' ".$domain_for_user." ORDER BY domain", $_SESSION['s']['user']['userid'], $_SESSION['s']['user']['default_group']);
if(!empty($domains_assoc)) foreach($domains_assoc as $domain) $domains[] = $domain['domain'];

	// If data has been submitted, validate it
	$result['input'] = array();
if(count($_POST) > 1)
{
	// Check CSRF Token
	$app->auth->csrf_token_check();
	
	$result = $gui->validateInstallerInput($_POST, $details, $domains, $settings);
	if(empty($result['error']))
	{
		$gui->createPackageInstance($result['input'], $pkg_id);
		@header('Location:aps_installedpackages_list.php');
	}
	else
	{
		$app->tpl->setVar('error', implode('<br />', $result['error']));

		// Set memorized values (license, db password, install location)
		if(!empty($result['input']))
			foreach($result['input'] as $key => $value) $app->tpl->setVar('inp_'.$key, $value);
	}
}
else $app->tpl->setVar('inp_main_database_password', ucfirst(substr(md5(crypt(rand(0, 10))), 0, 16)));

// Pass the package details to the template
foreach($details as $key => $value)
{
	if(!is_array($value)) $app->tpl->setVar('pkg_'.str_replace(' ', '_', strtolower($key)), $value);
	else if($key == 'Requirements PHP settings') $app->tpl->setLoop('pkg_requirements_php_settings', $details['Requirements PHP settings']);
}

// get new csrf token
$csrf_token = $app->auth->csrf_token_get('aps_install_package');
$app->tpl->setVar('_csrf_id', $csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key', $csrf_token['csrf_key']);

// Parse the template as far as possible, then do the rest manually
$app->tpl_defaults();
$parsed_tpl = $app->tpl->grab();


// Print the domain list
$domains_tpl = '';
if(!empty($domains))
{
	$set = array();
	$set[] = '<select name="main_domain" id="main_domain" class="form-control" style="width:100%; height:100%; min-width:170px;">';
	foreach($domains as $domain)
	{
		$selected = '';
		if((count($_POST) > 1)
			&& (isset($result['input']['main_domain']))
			&& ($result['input']['main_domain'] == $domain))
			$selected = ' selected ';
		$set[] = '<option value="'.$domain.'" '.$selected.'>'.$domain.'</option>';
	}
	$set[] = '</select>';

	$domains_tpl = implode("\n", $set);
}
$parsed_tpl = str_replace('DOMAIN_LIST_SPACE', $domains_tpl, $parsed_tpl);

// Print the packgae settings
$settings_tpl = '';
if(!empty($settings))
{
	$set = array();
	$set[] = '<legend>'.$app->lng('package_settings_txt').'</legend>';
	foreach($settings as $setting)
	{
		$set[] = '<div class="form-group">';
		$set[] = '<label for="'.$setting['SettingID'].'" class="col-sm-3 control-label">'.$setting['SettingName'].'</label>';
		if($setting['SettingInputType'] == 'string' || $setting['SettingInputType'] == 'password')
		{
			$input_type = ($setting['SettingInputType'] == 'string') ? 'text' : 'password';

			$input_value = '';
			if((count($_POST) > 1)
				&& (isset($result['input'][$setting['SettingID']])))
				$input_value = $result['input'][$setting['SettingID']];
			else $input_value = @$setting['SettingDefaultValue'];

			$set[] = '<div class="col-sm-9"><input type="'.$input_type.'" class="form-control" name="'.$setting['SettingID'].'" maxlength="'.$setting['SettingMaxLength'].'" id="'.$setting['SettingID'].'" value="'.$input_value.'" />
                <p class="formHint">'.$setting['SettingDescription'].'</p></div>';
		}
		else if($setting['SettingInputType'] == 'checkbox')
			{
				$checked = '';
				if((count($_POST) > 1)
					&& (isset($result['input'][$setting['SettingID']])
						&& ($result['input'][$setting['SettingID']] == 'true')))
					$checked = 'checked ';
				else if($setting['SettingDefaultValue'] == '1') $checked = 'checked ';

					$set[] = '<div class="col-sm-9"><input type="checkbox" id="'.$setting['SettingID'].'" name="'.$setting['SettingID'].'" '.$checked.'/>
                <p class="formHint">'.$setting['SettingDescription'].'</p></div>';
			}
		else if($setting['SettingInputType'] == 'select')
			{
				$set[] =  '<div class="col-sm-9"><select size="1" class="form-control" name="'.$setting['SettingID'].'">';
				foreach($setting['SettingChoices'] as $choice)
				{
					$selected = '';
					if((count($_POST) > 1)
						&& (isset($result['input'][$setting['SettingID']])))
					{
						if($result['input'][$setting['SettingID']] == $choice['EnumID'])
							$selected = 'selected ';
					}
					else if($setting['SettingDefaultValue'] == $choice['EnumID']) $selected = 'selected ';

						$set[] = '<option value="'.$choice['EnumID'].'" '.$selected.'>'.$choice['EnumName'].'</option>';
				}
				$set[] = '</select>
                <p class="formHint">'.$setting['SettingDescription'].'</p></div>';
			}

		$set[] = '</div>';
	}
	$settings_tpl = implode("\n", $set);
}
$parsed_tpl = str_replace('PKG_SETTINGS_SPACE', $settings_tpl, $parsed_tpl);

echo $parsed_tpl;
?>
