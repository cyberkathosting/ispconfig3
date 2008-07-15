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
	
	function onSubmit() {
		global $app, $conf;
		
		// Get the parent mail_user record
		$mailuser = $app->db->queryOneRecord("SELECT * FROM mail_user WHERE mailuser_id = '".intval($_REQUEST["mailuser_id"])."' AND ".$app->tform->getAuthSQL('r'));
		
		// Check if Domain belongs to user
		if($mailuser["mailuser_id"] != $_POST["mailuser_id"]) $app->tform->errorMessage .= $app->tform->wordbook["no_mailuser_perm"];
		
		// Set the mailuser_id
		$this->dataRecord["mailuser_id"] = $mailuser["mailuser_id"];
		
		parent::onSubmit();
	}
	
	function onAfterInsert() {
		global $app, $conf;
		
		$mailuser = $app->db->queryOneRecord("SELECT custom_mailfilter FROM mail_user WHERE mailuser_id = ".$this->dataRecord["mailuser_id"]);
		$rule_content = $mailuser['custom_mailfilter']."\n".$app->db->quote($this->getRule());
		$app->db->datalogUpdate('mail_user', "custom_mailfilter = '$rule_content'", 'mailuser_id', $this->dataRecord["mailuser_id"]);
	
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		$mailuser = $app->db->queryOneRecord("SELECT custom_mailfilter FROM mail_user WHERE mailuser_id = ".$this->dataRecord["mailuser_id"]);
		$skip = false;
		$lines = explode("\n",$mailuser['custom_mailfilter']);
		$out = '';
		$found = false;
		
		foreach($lines as $line) {
			$line = trim($line);
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
		
		// We did not found our rule, so we add it now.
		if($found == false) {
			$out .= $this->getRule();
		}
		
		$out = addslashes($out);
		$app->db->datalogUpdate('mail_user', "custom_mailfilter = '$out'", 'mailuser_id', $this->dataRecord["mailuser_id"]);
	
	}
	
	function getRule() {
		
		$content = '';
		$content .= '### BEGIN FILTER_ID:'.$this->id."\n";
		
		if($this->dataRecord["action"] == 'move') {
		
			$content .= "
`test -e ".'$DEFAULT/.'.$this->dataRecord["target"]."`
if ( ".'$RETURNCODE'." != 0 )
{
  `maildirmake -f ".$this->dataRecord["target"].' $DEFAULT'."`
  `chmod -R 0700 ".'$DEFAULT/'.$this->dataRecord["target"]."`
  `echo INBOX.".$this->dataRecord["target"]." >> ".'$DEFAULT'."/courierimapsubscribed`
}
";		
		}
		
		$content .= "if (/^".$this->dataRecord["source"].":";
		
		if($this->dataRecord["op"] == 'contains') {
			$content .= ".*".$this->dataRecord["searchterm"]."/:h)\n";
		} elseif ($this->dataRecord["op"] == 'is') {
			$content .= $this->dataRecord["searchterm"]."$/:h)\n";
		} elseif ($this->dataRecord["op"] == 'begins') {
			$content .= $this->dataRecord["searchterm"]."/:h)\n";
		} elseif ($this->dataRecord["op"] == 'ends') {
			$content .= ".*".$this->dataRecord["searchterm"]."$/:h)\n";
		}
		
		$content .= "{\n";
		$content .= "exception {\n";
		
		if($this->dataRecord["action"] == 'move') {
			$content .= 'to $DEFAULT/.'.$this->dataRecord["target"]."/\n";
		} else {
			$content .= "to /dev/null\n";
		}
		
		$content .= "}\n";
		$content .= "}\n";
		
		$content .= '### END FILTER_ID:'.$this->id."\n";
		
		return $content;
	}
	
}

$page = new page_action;
$page->onLoad();

?>