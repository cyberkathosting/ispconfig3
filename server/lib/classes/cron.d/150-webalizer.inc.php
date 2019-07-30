<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
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

class cronjob_webalizer extends cronjob {

	// job schedule
	protected $_schedule = '0 0 * * *';

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;

		//######################################################################################################
		// Create webalizer statistics
		//######################################################################################################

		function setConfigVar( $filename, $varName, $varValue, $append = 0 ) {
			if($lines = @file($filename)) {
				$out = '';
				$found = 0;
				foreach($lines as $line) {
					@list($key, $value) = preg_split('/[\t= ]+/', $line, 2);
					if($key == $varName) {
						$out .= $varName.' '.$varValue."\n";
						$found = 1;
					} else {
						$out .= $line;
					}
				}
				if($found == 0) {
					//* add \n if the last line does not end with \n or \r
					if(substr($out, -1) != "\n" && substr($out, -1) != "\r") $out .= "\n";
					//* add the new line at the end of the file
					if($append == 1) $out .= $varName.' '.$varValue."\n";
				}

				file_put_contents($filename, $out);
			}
		}


		$sql = "SELECT domain_id, domain, document_root, web_folder, type, parent_domain_id, system_user, system_group FROM web_domain WHERE (type = 'vhost' or type = 'vhostsubdomain' or type = 'vhostalias') and stats_type = 'webalizer' AND server_id = ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);

		foreach($records as $rec) {
			//$yesterday = date('Ymd',time() - 86400);
			$yesterday = date('Ymd', strtotime("-1 day", time()));

			$log_folder = 'log';
			if($rec['type'] == 'vhostsubdomain' || $rec['type'] == 'vhostalias') {
				$tmp = $app->db->queryOneRecord('SELECT `domain` FROM web_domain WHERE domain_id = ?', $rec['parent_domain_id']);
				$subdomain_host = preg_replace('/^(.*)\.' . preg_quote($tmp['domain'], '/') . '$/', '$1', $rec['domain']);
				if($subdomain_host == '') $subdomain_host = 'web'.$rec['domain_id'];
				$log_folder .= '/' . $subdomain_host;
				unset($tmp);
			}
			$logfile = $rec['document_root'].'/' . $log_folder . '/'.$yesterday.'-access.log';
			if(!@is_file($logfile)) {
				$logfile = $rec['document_root'].'/' . $log_folder . '/'.$yesterday.'-access.log.gz';
				if(!@is_file($logfile)) {
					continue;
				}
			}

			$domain = $rec['domain'];
			$statsdir = $rec['document_root'].'/'.(($rec['type'] == 'vhostsubdomain' || $rec['type'] == 'vhostalias') ? $rec['web_folder'] : 'web').'/stats';
			$webalizer = '/usr/bin/webalizer';
			$webalizer_conf_main = '/etc/webalizer/webalizer.conf';
			$webalizer_conf = $rec['document_root'].'/log/webalizer.conf';

			if(is_file($statsdir.'/index.php')) unlink($statsdir.'/index.php');

			if(!@is_file($webalizer_conf)) {
				copy($webalizer_conf_main, $webalizer_conf);
			}

			if(@is_file($webalizer_conf)) {
				setConfigVar($webalizer_conf, 'Incremental', 'yes');
				setConfigVar($webalizer_conf, 'IncrementalName', $statsdir.'/webalizer.current');
				setConfigVar($webalizer_conf, 'HistoryName', $statsdir.'/webalizer.hist');
			}


			if(!@is_dir($statsdir)) mkdir($statsdir);
			$username = $rec['system_user'];
			$groupname = $rec['system_group'];
			chown($statsdir, $username);
			chgrp($statsdir, $groupname);
			$app->system->exec_safe("$webalizer -c ? -n ? -s ? -r ? -q -T -p -o ? ?", $webalizer_conf, $domain, $domain, $domain, $statsdir, $logfile);
			
			exec('chown -R ?:? ?', $username, $groupname, $statsdir);
		}



		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
