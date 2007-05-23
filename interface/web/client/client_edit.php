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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/client.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

// Checking module permissions
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
	header("Location: ../index.php");
	exit;
}

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {
	
	/*
	 This function is called automatically right after
	 the data was successful inserted in the database.
	*/
	function onAfterInsert() {
		global $app;
		// Create the group for the client
		$sql = "INSERT INTO sys_group (name,description,client_id) VALUES ('".addslashes($this->dataRecord["username"])."','',".$this->id.")";
		$app->db->query($sql);
		$groupid = $app->db->insertID();
		
		$username = addslashes($this->dataRecord["username"]);
		$password = addslashes($this->dataRecord["password"]);
		$modules = 'mail';
		$startmodule = 'mail';
		$usertheme = addslashes($this->dataRecord["usertheme"]);
		$type = 'user';
		$active = 1;
		$language = addslashes($this->dataRecord["language"]);
		
		// Create the controlpaneluser for the client
		$sql = "INSERT INTO sys_user (username,passwort,modules,startmodule,app_theme,typ,active,language,groups,default_group,client_id)
		VALUES ('$username',md5('$password'),'$modules','$startmodule','$usertheme','$type','$active','$language',$groupid,$groupid,".$this->id.")";
		$app->db->query($sql);
	}
	
	
	/*
	 This function is called automatically right after
	 the data was successful updated in the database.
	*/
	function onAfterUpdate() {
		global $app;
		
		// username changed
		if(isset($app->tform->diffrec['username'])) {
			$username = addslashes($this->dataRecord["username"]);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET username = '$username' WHERE client_id = $client_id";
			$app->db->query($sql);
			$sql = "UPDATE sys_group SET name = '$username' WHERE client_id = $client_id";
			$app->db->query($sql);
		}
		
		// password changed
		if($this->dataRecord["password"] != '') {
			$password = addslashes($this->dataRecord["password"]);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET passwort = md5('$password') WHERE client_id = $client_id";
			$app->db->query($sql);
		}
		
		
		
	}
	
	
}

$page = new page_action;
$page->onLoad();

?>