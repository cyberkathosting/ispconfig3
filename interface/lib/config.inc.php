<?php
/*
Copyright (c) 2007, Till Brehm, Falko Timme, projektfarm Gmbh
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

error_reporting(E_ALL|E_NOTICE);

header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, max-age=0, must-revalidate');
header('Content-Type: text/html; charset=utf-8');
//* TODO: Js caching - pedro

//** Key paramaters
define('ISPC_APP_TITLE', 'ISPConfig');
$conf['app_title'] = 'ISPConfig';
define('ISPC_APP_VERSION', '3.0.0');
$conf['app_version'] = '3.0.0';
$conf['modules_available'] 	= 'admin,mail,sites,monitor,client,dns';

//** The main ROOT is the parent directory to this file, ie Interface/. NO trailing slashes.
define('ISPC_ROOT_PATH',   realpath(dirname(__FILE__).'/../'));
define('ISPC_LIB_PATH',    ISPC_ROOT_PATH.'/lib');
define('ISPC_CLASS_PATH',  ISPC_ROOT_PATH.'/lib/classes');
define('ISPC_WEB_PATH',    ISPC_ROOT_PATH.'/web');
define('ISPC_THEMES_PATH', ISPC_ROOT_PATH.'/web/themes');

define('ISPC_WEB_TEMP_PATH',   ISPC_WEB_PATH.'/temp'); // Path for downloads, accessible via browser
define('ISPC_CACHE_PATH',  ISPC_ROOT_PATH.'/cache');

define('ISPC_INTERFACE_MODULES_ENABLED', 'mail,sites,dns,tools');

//********************************************************************************
//** Future Code idea  - pedro - rfc
//** >>>>
/*  Database connection
	The only time paramaters are needed is to connect, otherwise the variables
	are not required "around" the application. ie Connected and done.
	Prefered is an array as $DB in capitals, ie
	$DB['engine'] = 'type'; 
	$DB['host'] = 'ip';
	$DB['user'] = 'me';
	$DB['password'] = 'secret';
	$DB['database'] = 'db_name';
	
	The connection paramaters are all contained within one array structure
	With this array structure the connection can be passed around, to functions
	However it can also leak so it can be destroyed eg
	$dbClass->connect($DB);
	unset($DB); // only the paranoid survive
		
	Also there is a scenario where we are devloping and using this file
	and the database paramaters might leak into svn etc.
    (This idea is borrowed from the tikiwiki.org project)
	To resolve this there is a file called db_local.php.skel which is not detected
	rename this to db_local.php and edit the paramaters.
*/

//* Detect the local database settings ie $DB array()
//* Copy db_local.php.skel for and change for local development
if(file_exists(dirname(__FILE__).'/db_local.php')){
	require_once(dirname(__FILE__).'/db_local.php');
	$conf['db_type']			= $DB['type'];
	$conf['db_host']			= $DB['host'];
	$conf['db_user']			= $DB['user'];
	$conf['db_password']		= $DB['password'];	
    $conf['db_database']        = $DB['database'];
}else{
	//** Database Settings
	$conf['db_type']			= 'mysql';
	$conf['db_host']			= 'localhost';
	$conf['db_user']			= 'root';
	$conf['db_password']		= '';
    $conf['db_database']        = 'ispconfig3';
}

//** Database Settings
/* See above
$conf['db_type']            = 'mysql';
$conf['db_host']            = 'localhost';
$conf['db_user']            = 'root';
$conf['db_password']        = '';
$conf['db_database']        = 'ispconfig3';
*/



//**  External programs
//$conf["programs"]["convert"]	= "/usr/bin/convert";
$conf['programs']['wput']		= ISPC_ROOT_PATH."\\tools\\wput\\wput.exe";


//** Themes
$conf['theme']					= 'default';
$conf['html_content_encoding']	= 'text/html; charset=utf-8';

//** Default Language
$conf['language']       = 'en';

//**  Auto Load Modules
$conf['start_db']		= true;
$conf['start_session']	= true;

/*
        Misc.
*/

$conf["interface_logout_url"] 	= ""; // example: http://www.domain.tld/


//** DNS Settings

//* Automatically create PTR records?
$conf['auto_create_ptr'] 	 = 1; 
//* must be set if $conf['auto_create_ptr'] is 1. Don't forget the trailing dot!
$conf['default_ns'] 		 = 'ns1.example.com.'; 
//* Admin email address. Must be set if $conf['auto_create_ptr'] is 1. Replace "@" with ".". Don't forget the trailing dot!
$conf['default_mbox'] 		 = 'admin.example.com.'; 
$conf['default_ttl'] 		 = 86400;
$conf['default_refresh'] 	 = 28800;
$conf['default_retry'] 		 = 7200;
$conf['default_expire'] 	 = 604800;
$conf['default_minimum_ttl'] = 86400;

?>