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

$tform_def_file = "form/mail_domain_catchall.tform.php";

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
	
	function onShowEnd() {
		global $app, $conf;
		
		$email = $this->dataRecord["source"];
		$email_parts = explode("@",$email);
		$app->tpl->setVar("email_local_part",$email_parts[0]);
		
		// Getting Domains of the user
		$sql = "SELECT domain FROM mail_domain WHERE ".$app->tform->getAuthSQL('r');
		$domains = $app->db->queryAllRecords($sql);
		$domain_select = '';
		if(is_array($domains)) {
			foreach( $domains as $domain) {
				$selected = ($domain["domain"] == $email_parts[1])?'SELECTED':'';
				$domain_select .= "<option value='$domain[domain]' $selected>$domain[domain]</option>\r\n";
			}
		}
		$app->tpl->setVar("email_domain",$domain_select);
		
		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		// Check if Domain belongs to user
		$domain = $app->db->queryOneRecord("SELECT server_id, domain FROM mail_domain WHERE domain = '".$app->db->quote($_POST["email_domain"])."' AND ".$app->tform->getAuthSQL('r'));
		if($domain["domain"] != $_POST["email_domain"]) $app->tform->errorMessage .= $app->tform->wordbook["no_domain_perm"];
		 		
		// compose the email field
		$this->dataRecord["source"] = "@".$_POST["email_domain"];
		// Set the server id of the mailbox = server ID of mail domain.
		$this->dataRecord["server_id"] = $domain["server_id"];
		
		//unset($this->dataRecord["email_local_part"]);
		unset($this->dataRecord["email_domain"]);
		
		parent::onSubmit();
	}
	
}

$page = new page_action;
$page->onLoad();

?>