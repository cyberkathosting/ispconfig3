<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/mail_user_filter.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {
	
	function onShowNew() {
		global $app, $conf;
		
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_mailfilter',"")) {
				$app->error($app->tform->lng("limit_mailfilter_txt"));
			}
			if(!$app->tform->checkResellerLimit('limit_mailfilter',"")) {
				$app->error('Reseller: '.$app->tform->lng("limit_mailfilter_txt"));
			}
		}
		
		parent::onShowNew();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		// Get the parent mail_user record
		$mailuser = $app->db->queryOneRecord("SELECT * FROM mail_user WHERE mailuser_id = '".intval($_REQUEST["mailuser_id"])."' AND ".$app->tform->getAuthSQL('r'));
		
		// Check if Domain belongs to user
		if($mailuser["mailuser_id"] != $_POST["mailuser_id"]) $app->tform->errorMessage .= $app->tform->wordbook["no_mailuser_perm"];
		
		// Set the mailuser_id
		$this->dataRecord["mailuser_id"] = $mailuser["mailuser_id"];
		
		// Remove leading dots
		if(substr($this->dataRecord['target'],0,1) == '.') $this->dataRecord['target'] = substr($this->dataRecord['target'],1);
		
		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_mailfilter FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Check if the user may add another filter
			if($this->id == 0 && $client["limit_mailfilter"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(filter_id) as number FROM mail_user_filter WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_mailfilter"]) {
					$app->tform->errorMessage .= $app->tform->lng("limit_mailfilter_txt")."<br>";
				}
				unset($tmp);
			}
		} // end if user is not admin
		
		parent::onSubmit();
	}
	
	/*
	function onAfterInsert() {
		global $app, $conf;
		
		$this->onAfterUpdate();
		
		$app->db->query("UPDATE mail_user_filter SET sys_groupid = ".$mailuser['sys_groupid']." WHERE filter_id = ".$this->id);
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		$mailuser = $app->db->queryOneRecord("SELECT custom_mailfilter FROM mail_user WHERE mailuser_id = ".$this->dataRecord["mailuser_id"]);
		$skip = false;
		$lines = explode("\n",$mailuser['custom_mailfilter']);
		$out = '';
		$found = false;
		
		foreach($lines as $line) {
			$line = rtrim($line);
			if($line == '### BEGIN FILTER_ID:'.$this->id) {
				$skip = true;
				$found = true;
			}
			if($skip == false && $line != '') $out .= $line ."\n";
			if($line == '### END FILTER_ID:'.$this->id) {
				$out .= $this->getRule();
				$skip = false;
			}
		}
		
		// We did not found our rule, so we add it now as first rule.
		if($found == false) {
			$new_rule = $this->getRule();
			$out = $new_rule . $out;
		}
		
		$out = $app->db->quote($out);
		$app->db->datalogUpdate('mail_user', "custom_mailfilter = '$out'", 'mailuser_id', $this->dataRecord["mailuser_id"]);
	
	}
	
	function getRule() {
		
		global $app,$conf;
		
		$app->uses("getconf");
		$mailuser_rec = $app->db->queryOneRecord("SELECT server_id FROM mail_user WHERE mailuser_id = ".intval($this->dataRecord["mailuser_id"]));
		$mail_config = $app->getconf->get_server_config(intval($mailuser_rec["server_id"]),'mail');
		
		if($mail_config['mail_filter_syntax'] == 'sieve') {
			
			// #######################################################
			// Filter in Sieve Syntax
			// #######################################################
			
			$content = '';
			$content .= '### BEGIN FILTER_ID:'.$this->id."\n";
			
			//$content .= 'require ["fileinto", "regex", "vacation"];'."\n";
			
			$content .= 'if header :regex    ["'.strtolower($this->dataRecord["source"]).'"] ["';
			
			$searchterm = preg_quote($this->dataRecord["searchterm"]);
			
			if($this->dataRecord["op"] == 'contains') {
				$content .= ".*".$searchterm;
			} elseif ($this->dataRecord["op"] == 'is') {
				$content .= $searchterm."$";
			} elseif ($this->dataRecord["op"] == 'begins') {
				$content .= " ".$searchterm."";
			} elseif ($this->dataRecord["op"] == 'ends') {
				$content .= ".*".$searchterm."$";
			}
			
			$content .= '"] {'."\n";
			
			if($this->dataRecord["action"] == 'move') {
				$content .= '    fileinto "'.$this->dataRecord["target"].'";' . "\n";
			} else {
				$content .= "    discard;\n";
			}
			
			$content .= "    stop;\n}\n";
			
			$content .= '### END FILTER_ID:'.$this->id."\n";
		
		} else {
		
			// #######################################################
			// Filter in Maildrop Syntax
			// #######################################################
			$content = '';
			$content .= '### BEGIN FILTER_ID:'.$this->id."\n";

			$TargetNoQuotes = $this->dataRecord["target"];
			$TargetQuotes = "\"$TargetNoQuotes\"";

			$TestChDirNoQuotes = '$DEFAULT/.'.$TargetNoQuotes;
			$TestChDirQuotes = "\"$TestChDirNoQuotes\"";

			$MailDirMakeNoQuotes = $TargetQuotes.' $DEFAULT';

			$EchoTargetFinal = $TargetNoQuotes;


			if($this->dataRecord["action"] == 'move') {

			$content .= "
`test -e ".$TestChDirQuotes." && exit 1 || exit 0`
if ( ".'$RETURNCODE'." != 1 )
{
	`maildirmake -f $MailDirMakeNoQuotes`
	`chmod -R 0700 ".$TestChDirQuotes."`
	`echo \"INBOX.$EchoTargetFinal\" >> ".'$DEFAULT'."/courierimapsubscribed`
}
";
			}

			$content .= "if (/^".$this->dataRecord["source"].":";

			$searchterm = preg_quote($this->dataRecord["searchterm"]);

			if($this->dataRecord["op"] == 'contains') {
				$content .= ".*".$searchterm."/:h)\n";
			} elseif ($this->dataRecord["op"] == 'is') {
				$content .= $searchterm."$/:h)\n";
			} elseif ($this->dataRecord["op"] == 'begins') {
				$content .= " ".$searchterm."/:h)\n";
			} elseif ($this->dataRecord["op"] == 'ends') {
				$content .= ".*".$searchterm."$/:h)\n";
			}

			$content .= "{\n";
			$content .= "exception {\n";

			if($this->dataRecord["action"] == 'move') {
				$content .= 'ID' . "$this->id" . 'EndFolder = "$DEFAULT/.' . $this->dataRecord['target'] . '/"' . "\n";
				$content .= "to ". '$ID' . "$this->id" . 'EndFolder' . "\n";
			} else {
				$content .= "to /dev/null\n";
			}

			$content .= "}\n";
			$content .= "}\n";
		
			//}
		
			$content .= '### END FILTER_ID:'.$this->id."\n";
		
		}
		
		return $content;
	}
	*/
	
}

$page = new page_action;
$page->onLoad();

?>