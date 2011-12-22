<?php
/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

//* Security checkpoint
if($_SESSION['s']['user']['typ'] != 'admin'){
    die('Admin permissions required.');
}

//* Check permissions for module
$app->auth->check_module_permissions('designer');

//* Load template
$app->uses('tpl');
$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/module_nav_edit.htm');

// TODO: Check module and nav_id for malicius chars, nav_id can be empty or any number, even 0
$module_name = $_REQUEST['module_name'];
$nav_id = $_REQUEST['nav_id'];

//** Sanity checks of module
if(!preg_match('/^[A-Za-z0-9_]{1,50}$/', $module_name)){
    die('module_name contains invalid chars.');
}
if(!preg_match('/^[A-Za-z0-9_]{0,50}$/', $nav_id)){
    die('nav_id contains invalid chars.');
}
if(empty($module_name)){
    die('module is empty.');
}

if(count($_POST) > 0) {
	//* Determine Action
	$action = ($nav_id != '') ? 'UPDATE' : 'INSERT';
	$error = '';
	
	// TODO: Check variables
	
	if($error == '') {
	
		$filename = '../'.$module_name.'/lib/module.conf.php';
		
		if(!@is_file($filename)){
            die("File not found: $filename");
        }
		include_once($filename);
		
        $items = ($action == 'UPDATE') ?  $module['nav'][$nav_id]['items'] : array();
		
		$tmp = array('title' => $_POST['nav']['title'],
					 'open' =>  1,
					 'items' => $items);
        
		if($action == 'UPDATE') {
			$module['nav'][$nav_id] = $tmp;
		} else {
			$module['nav'][] = $tmp;
		}
		
		$m = "<?php\r\n".'$module = '.var_export($module,true)."\r\n?>";
				
		//* writing module.conf
		if (!$handle = fopen($filename, 'w')) { 
			die("Cannot open file ($filename)"); 
		} 

		if (!fwrite($handle, $m)) { 
			die("Cannot write to file ($filename)"); 
		} 
    
		fclose($handle);
		
		
		//* Jump to list
    	header('Location: module_show.php?id='.urlencode($module_name));
        exit;
			
	} else {
		$app->tpl->setVar('error', '<b>Fehler:</b><br>'.$error);
		$app->tpl->setVar($_POST);
	}
}

if($nav_id != '') {
    //* Data record exists
	if($error == '') {
		include_once('../'.$module_name.'/lib/module.conf.php');
		$record = $module['nav'][$nav_id];
	} else {
		//* error
		$record = $_POST;
	}
	//$record["readonly"] = 'style="background-color: #EEEEEE;" readonly';
} else {
    //* New data record
	if($error == '') {
		//* es liegt kein Fehler vor
	} else {
		//* error
		$record = $_POST;
		
	}
	//$record["readonly"] = '';
}

$record['nav_id'] = $nav_id;
$record['module_name'] = $module_name;

$app->tpl->setVar($record);

include_once('lib/lang/'.$_SESSION['s']['language'].'_module_nav_edit.lng');
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();

?>