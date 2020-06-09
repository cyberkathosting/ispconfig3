<?php

/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
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

class mail_user_filter_plugin {

	var $plugin_name = 'mail_user_filter_plugin';
	var $class_name = 'mail_user_filter_plugin';

	/*
	 	This function is called when the plugin is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Register for the events
		*/

		$app->plugin->registerEvent('mail:mail_user_filter:on_after_insert', 'mail_user_filter_plugin', 'mail_user_filter_edit');
		$app->plugin->registerEvent('mail:mail_user_filter:on_after_update', 'mail_user_filter_plugin', 'mail_user_filter_edit');
		$app->plugin->registerEvent('mail:mail_user_filter:on_after_delete', 'mail_user_filter_plugin', 'mail_user_filter_del');
		$app->plugin->registerEvent('mailuser:mail_user_filter:on_after_insert', 'mail_user_filter_plugin', 'mail_user_filter_edit');
		$app->plugin->registerEvent('mailuser:mail_user_filter:on_after_update', 'mail_user_filter_plugin', 'mail_user_filter_edit');
		$app->plugin->registerEvent('mailuser:mail_user_filter:on_after_delete', 'mail_user_filter_plugin', 'mail_user_filter_del');

	}


	/*
	 *	Render the mail filter rule in the desired format and insert it into the custom rules
	 *	field when a new mail filter is added or modified.
	 */
	function mail_user_filter_edit($event_name, $page_form) {
		global $app, $conf;

		$mailuser = $app->db->queryOneRecord("SELECT custom_mailfilter FROM mail_user WHERE mailuser_id = ?", $page_form->dataRecord["mailuser_id"]);
		$skip = false;
		$lines = explode("\n", $mailuser['custom_mailfilter']);
		$out = '';
		$found = false;

		foreach($lines as $line) {
			$line = rtrim($line);
			if($line == '### BEGIN FILTER_ID:'.$page_form->id) {
				$skip = true;
				$found = true;
			}
			if($skip == false && $line != '') $out .= $line ."\n";
			if($line == '### END FILTER_ID:'.$page_form->id) {
				if($page_form->dataRecord["active"] == 'y') $out .= $this->mail_user_filter_get_rule($page_form);
				$skip = false;
			}
		}

		// We did not find our rule, so we add it now as first rule.
		if($found == false && $page_form->dataRecord["active"] == 'y') {
			$new_rule = $this->mail_user_filter_get_rule($page_form);
			$out = $new_rule . $out;
		}

		$app->db->datalogUpdate('mail_user', array("custom_mailfilter" => $out), 'mailuser_id', $page_form->dataRecord["mailuser_id"]);


	}

	/*
	 *	Remove the rendered filter from custom_mailfilter when a mail_user_filter is deleted.
	 */
	function mail_user_filter_del($event_name, $page_form) {
		global $app, $conf;

		$mailuser = $app->db->queryOneRecord("SELECT custom_mailfilter FROM mail_user WHERE mailuser_id = ?", $page_form->dataRecord["mailuser_id"]);
		$skip = false;
		$lines = explode("\n", $mailuser['custom_mailfilter']);
		$out = '';

		foreach($lines as $line) {
			$line = trim($line);
			if($line == '### BEGIN FILTER_ID:'.$page_form->id) {
				$skip = true;
			}
			if($skip == false && $line != '') $out .= $line ."\n";
			if($line == '### END FILTER_ID:'.$page_form->id) {
				$skip = false;
			}
		}

		$app->db->datalogUpdate('mail_user', array("custom_mailfilter" => $out), 'mailuser_id', $page_form->dataRecord["mailuser_id"]);
	}


	/*
		private function to create the mail filter rules in maildrop or sieve format.
	*/
	private function mail_user_filter_get_rule($page_form) {

		global $app, $conf;

		$app->uses("getconf");
		$mailuser_rec = $app->db->queryOneRecord("SELECT server_id FROM mail_user WHERE mailuser_id = ?", $page_form->dataRecord["mailuser_id"]);
		$mail_config = $app->getconf->get_server_config($app->functions->intval($mailuser_rec["server_id"]), 'mail');

		if($mail_config['mail_filter_syntax'] == 'sieve') {

			// #######################################################
			// Filter in Sieve Syntax
			// #######################################################

			$content = '';
			$content .= '### BEGIN FILTER_ID:'.$page_form->id."\n";

			//$content .= 'require ["fileinto", "regex", "vacation"];'."\n";
			
			if($page_form->dataRecord["op"] == 'domain') {
				$content .= 'if address :domain :is "'.strtolower($page_form->dataRecord["source"]).'" "'.$page_form->dataRecord["searchterm"].'" {'."\n";
			} elseif ($page_form->dataRecord["op"] == 'localpart') {
				$content .= 'if address :localpart :is "'.strtolower($page_form->dataRecord["source"]).'" "'.$page_form->dataRecord["searchterm"].'" {'."\n";
			} elseif ($page_form->dataRecord["source"] == 'Size') {
				if(substr(trim($page_form->dataRecord["searchterm"]),-1) == 'k' || substr(trim($page_form->dataRecord["searchterm"]),-1) == 'K') {
					$unit = 'k';
				} else {
					$unit = 'm';
				}
				$content .= 'if size :over '.intval($page_form->dataRecord["searchterm"]).$unit.' {'."\n";
			} else {
			
				if($page_form->dataRecord["source"] == 'Header') {
					$parts = explode(':',trim($page_form->dataRecord["searchterm"]));
					$page_form->dataRecord["source"] = trim($parts[0]);
					unset($parts[0]);
					$page_form->dataRecord["searchterm"] = trim(implode(':',$parts));
					unset($parts);
				}

				$content .= 'if header :regex    ["'.strtolower($page_form->dataRecord["source"]).'"] ["';

				$searchterm = preg_quote($page_form->dataRecord["searchterm"]);
				$searchterm = str_replace(
					array(
						'"',
						'\\[',
						'\\]'
					),
					array(
						'\\"',
						'\\\\[',
						'\\\\]'
					), $searchterm);

				if($page_form->dataRecord["op"] == 'contains') {
					$content .= ".*".$searchterm;
				} elseif ($page_form->dataRecord["op"] == 'is') {
					$content .= "^".$searchterm."$";
				} elseif ($page_form->dataRecord["op"] == 'begins') {
					$content .= "^".$searchterm."";
				} elseif ($page_form->dataRecord["op"] == 'ends') {
					$content .= ".*".$searchterm."$";
				}

				$content .= '"] {'."\n";
			}

			if($page_form->dataRecord["action"] == 'move') {
				$content .= '    fileinto "'.$page_form->dataRecord["target"].'";' . "\n    stop;\n";
			} elseif ($page_form->dataRecord["action"] == 'keep') {
				$content .= "    keep;\n";
			} elseif ($page_form->dataRecord["action"] == 'stop') {
				$content .= "    stop;\n";
			} elseif ($page_form->dataRecord["action"] == 'reject') {
				$content .= '    reject "'.$page_form->dataRecord["target"].'";    stop;\n\n';
			} else {
				$content .= "    discard;\n    stop;\n";
			}

			$content .= "}\n";

			$content .= '### END FILTER_ID:'.$page_form->id."\n";

		} else {

			// #######################################################
			// Filter in Maildrop Syntax
			// #######################################################
			$content = '';
			$content .= '### BEGIN FILTER_ID:'.$page_form->id."\n";

			$TargetNoQuotes = $page_form->dataRecord["target"];
			$TargetQuotes = "\"$TargetNoQuotes\"";

			$TestChDirNoQuotes = '$DEFAULT/.'.$TargetNoQuotes;
			$TestChDirQuotes = "\"$TestChDirNoQuotes\"";

			$MailDirMakeNoQuotes = $TargetQuotes.' $DEFAULT';

			$EchoTargetFinal = $TargetNoQuotes;


			if($page_form->dataRecord["action"] == 'move') {

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

			$content .= "if (/^".$page_form->dataRecord["source"].": ";

			$searchterm = preg_quote($page_form->dataRecord["searchterm"]);

			if($page_form->dataRecord["op"] == 'contains') {
				$content .= ".*".$searchterm."/:h)\n";
			} elseif ($page_form->dataRecord["op"] == 'is') {
				$content .= $searchterm."$/:h)\n";
			} elseif ($page_form->dataRecord["op"] == 'begins') {
				$content .= $searchterm."/:h)\n";
			} elseif ($page_form->dataRecord["op"] == 'ends') {
				$content .= ".*".$searchterm."$/:h)\n";
			}

			$content .= "{\n";
			$content .= "exception {\n";

			if($page_form->dataRecord["action"] == 'move') {
				$content .= 'ID' . "$page_form->id" . 'EndFolder = "$DEFAULT/.' . $page_form->dataRecord['target'] . '/"' . "\n";
				$content .= "xfilter \"/usr/bin/formail -A \\\"X-User-Mail-Filter-ID"."$page_form->id".": Yes\\\"\"" . "\n";
				$content .= "to ". '$ID' . "$page_form->id" . 'EndFolder' . "\n";
			} else {
				$content .= "to /dev/null\n";
			}

			$content .= "}\n";
			$content .= "}\n";

			//}

			$content .= '### END FILTER_ID:'.$page_form->id."\n";

		}

		return $content;
	}


} // end class



?>
