<?php
/*
Copyright (c) 2006, Till Brehm, Falko Timme, projektfarm Gmbh
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

$conf["server_id"] 		= "1";
$conf["app_version"] 	= "3.0.0";



//$conf["rootpath"]		= "F:\\server\\www\\ispconfig3\\server";
$conf["rootpath"]		= "D:\\www\\ispconfig3\\server";
//$conf["rootpath"]		= "/home/www/ispconfig3/web/cms";

$conf["fs_div"]			= "/"; // File system divider, \\ on windows and / on linux and unix
$conf["classpath"]		= $conf["rootpath"].$conf["fs_div"]."lib".$conf["fs_div"]."classes";
$conf["temppath"]		= $conf["rootpath"].$conf["fs_div"]."temp";

/*
		Logging
*/

$conf["log_file"]		= $conf["rootpath"].$conf["fs_div"]."ispconfig.log";
$conf["log_priority"]	= 0 // 0 = Debug, 1 = Warning, 2 = Error


/*
        Database Settings
*/

$conf["db_type"]		= 'mysql';
$conf["db_host"]		= 'localhost';
$conf["db_database"]	= 'ispconfig3';
$conf["db_user"]		= 'root';
$conf["db_password"]	= '';

/*
        Auto Load Modules
*/

$conf["start_db"]			= true;
$conf["load_server_config"]	= true;



define("LOGLEVEL_DEBUG",0);
define("LOGLEVEL_WARN",1);
define("LOGLEVEL_ERROR",2);

?>