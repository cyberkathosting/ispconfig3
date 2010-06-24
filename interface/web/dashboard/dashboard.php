<?php
/*
Copyright (c) 2010 Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('dashboard');

//* Loading Template
$app->uses('tpl');
$app->tpl->newTemplate("templates/dashboard.htm");

//* load language file
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'.lng';
include($lng_file);
$app->tpl->setVar($wb);

//* set Default - Values
$app->tpl_defaults();

/*
 * Let the user welcome
*/
$welcome = sprintf($wb['welcome_user_txt'], $_SESSION['s']['user']['username']);
$app->tpl->setVar('welcome_user', $welcome);


/*
 * ToDo: Display errors, warnings and hints
*/
///*
// * If there is any error to display, do it...
//*/
//$error = array();
//
//$error[] = array('error_msg' => 'EClaus1');
//$error[] = array('error_msg' => 'EEClaus2');
//$error[] = array('error_msg' => 'EClaus3');
//$error[] = array('error_msg' => 'EClaus4');
//
//$app->tpl->setloop('error', $error);
//
///*
// * If there is any warning to display, do it...
//*/
//$warning = array();
//
//$warning[] = array('warning_msg' => 'WClaus1');
//$warning[] = array('warning_msg' => 'WWClaus2');
//$warning[] = array('warning_msg' => 'WClaus3');
//$warning[] = array('warning_msg' => 'WClaus4');
//
//$app->tpl->setloop('warning', $warning);
//


/*
 * If there is any information to display, do it...
*/
$info = array();

/*
 * Check the ISPConfig-Version (only for the admin)
*/
if($_SESSION["s"]["user"]["typ"] == 'admin') {
	$new_version = @file_get_contents('http://www.ispconfig.org/downloads/ispconfig3_version.txt');
	$new_version = trim($new_version);
	if($new_version != ISPC_APP_VERSION) {
		$info[] = array('info_msg' => 'There is a new Version of ISPConfig 3 available! <a href="http://www.ispconfig.org/ispconfig-3/download">See more...</a>');
	}
}

$app->tpl->setloop('info', $info);

/*
 * Show all modules, the user is allowed to use
*/
$modules = explode(',', $_SESSION['s']['user']['modules']);
$mod = array();
if(is_array($modules)) {
	foreach($modules as $mt) {
		if(is_file('../' . $mt . '/lib/module.conf.php')) {
			if(!preg_match("/^[a-z]{2,20}$/i", $mt)) die('module name contains unallowed chars.');
			include_once('../' . $mt.'/lib/module.conf.php');
			/* We don't want to show the dashboard */
			if ($mt != 'dashboard') {
				$mod[] = array(	'modules_title' 	=> $app->lng($module['title']),
						'modules_startpage'	=> $module['startpage'],
						'modules_name'  	=> $module['name']);
			}
		}
	}

	$app->tpl->setloop('modules', $mod);
}

//* Do Output
$app->tpl->pparse();

?>