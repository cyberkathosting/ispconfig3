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
$app->uses('tpl');
$app->tpl->newTemplate("listpage.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/aps_packagedetails_show.htm');

// Load the language file
$lngfile = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_aps.lng';
require_once $lngfile;
$app->tpl->setVar($wb);

$gui = new ApsGUIController($app);
$pkg_id = (isset($_GET['id'])) ? $_GET['id'] : '';

// Check if a newer version is available for the current package
// Note: It's intended that here is no strict ID check (see below)
/*if(isset($pkg_id))
{
	$newest_pkg_id = $gui->getNewestPackageID($pkg_id);
	if($newest_pkg_id != 0) $pkg_id = $newest_pkg_id;
}*/

// Make sure an integer ID is given
$adminflag = ($_SESSION['s']['user']['typ'] == 'admin') ? true : false;
if(!isset($pkg_id) || !$gui->isValidPackageID($pkg_id, $adminflag))
	$app->error($app->lng('Invalid ID'));

// Get package details
$details = $gui->getPackageDetails($pkg_id);
if(isset($details['error'])) $app->error($details['error']);

// Set the active and default tab
$next_tab = 'details';
if(isset($_POST['next_tab']) || isset($_GET['next_tab']))
{
	$tab = (isset($_POST['next_tab']) ? $_POST['next_tab'] : $_GET['next_tab']);
	switch($tab)
	{
	case 'details': $next_tab = 'details'; break;
	case 'settings': $next_tab = 'settings'; break;
	case 'changelog': $next_tab = 'changelog'; break;
	case 'screenshots': $next_tab = 'screenshots'; break;
	default: $next_tab = 'details';
	}
}
$app->tpl->setVar('next_tab', $next_tab);

// Parse the package details to the template
foreach($details as $key => $value)
{
	if(!is_array($value)) $app->tpl->setVar('pkg_'.str_replace(' ', '_', strtolower($key)), $value);
	else // Special cases
		{
		if($key == 'Changelog') $app->tpl->setLoop('pkg_changelog', $details['Changelog']);
		elseif($key == 'Screenshots') $app->tpl->setLoop('pkg_screenshots', $details['Screenshots']);
		elseif($key == 'Requirements PHP settings') $app->tpl->setLoop('pkg_requirements_php_settings', $details['Requirements PHP settings']);
	}
}
//print_r($details['Requirements PHP settings']);

$app->tpl_defaults();
$app->tpl->pparse();
?>
