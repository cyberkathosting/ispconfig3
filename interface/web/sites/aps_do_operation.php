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
$app->load('aps_guicontroller');

// Check the module permissions
$app->auth->check_module_permissions('sites');

$gui = new ApsGUIController($app);

// An action and ID are required in any case
if(!isset($_GET['action'])) die('No action');

// List of operations which can be performed
if($_GET['action'] == 'change_status')
{
	// Only admins can perform this operation
	if($_SESSION['s']['user']['typ'] != 'admin') die('For admin use only.');

	// Make sure a valid package ID is given
	if(!$gui->isValidPackageID($_GET['id'], true)) die($app->lng('Invalid ID'));

	// Change the existing status to the opposite
	$get_status = $app->db->queryOneRecord("SELECT package_status FROM aps_packages WHERE id = ?", $_GET['id']);
	if($get_status['package_status'] == strval(PACKAGE_LOCKED))
	{
		$app->db->query("UPDATE aps_packages SET package_status = ? WHERE id = ?", PACKAGE_ENABLED, $_GET['id']);
		echo '<div class="swap" id="ir-Yes"><span>'.$app->lng('Yes').'</span></div>';
	}
	else
	{
		$app->db->query("UPDATE aps_packages SET Package_status = ? WHERE id = ?", PACKAGE_LOCKED, $_GET['id']);
		echo '<div class="swap" id="ir-No"><span>'.$app->lng('No').'</span></div>';
	}
}
else if($_GET['action'] == 'delete_instance')
	{
		// Check CSRF Token
		$app->auth->csrf_token_check('GET');
		
		// Make sure a valid package ID is given (also corresponding to the calling user)
		$client_id = 0;
		$is_admin = ($_SESSION['s']['user']['typ'] == 'admin') ? true : false;
		if(!$is_admin)
		{
			$cid = $app->db->queryOneRecord("SELECT client_id FROM client WHERE username = ?", $_SESSION['s']['user']['username']);
			$client_id = $cid['client_id'];
		}

		// Assume that the given instance belongs to the currently calling client_id. Unimportant if status is admin
		if(!$gui->isValidInstanceID($_GET['id'], $client_id, $is_admin)) die($app->lng('Invalid ID'));

		// Only delete the instance if the status is "installed" or "flawed"
		$check = $app->db->queryOneRecord("SELECT id FROM aps_instances
        WHERE id = ? AND
        (instance_status = ? OR instance_status = ?)", $_GET['id'], INSTANCE_SUCCESS, INSTANCE_ERROR);
		if($check['id'] > 0) $gui->deleteInstance($_GET['id']);
		//echo $app->lng('Installation_remove');
		@header('Location:aps_installedpackages_list.php');
	}
?>
