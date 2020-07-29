<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
Copyright (c) 2020, Michael Seevogel
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

class cronjob_goaccess extends cronjob {

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
		// Create goaccess statistics
		//######################################################################################################

		$sql = "SELECT domain_id, domain, document_root, web_folder, type, system_user, system_group, parent_domain_id FROM web_domain WHERE (type = 'vhost' or type = 'vhostsubdomain' or type = 'vhostalias') and stats_type = 'goaccess' AND server_id = ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);

		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

                $goaccess_conf_locs = array('/etc/goaccess.conf', '/etc/goaccess/goaccess.conf');
                $count = 0;

                foreach($goaccess_conf_locs as $goa_loc) {
                        if(is_file($goa_loc) && (filesize($goa_loc) > 0)) {
                                $goaccess_conf_main = $goa_loc;
                                break;
                        } else {
                                $count++;
                                if($count == 2) {
                                        $app->log("No GoAccess base config found. Make sure that GoAccess is installed and that the goaccess.conf does exist in /etc or /etc/goaccess", LOGLEVEL_ERROR);
                                }
                        }
                }


                /* Check wether the goaccess binary is in path */
                system("type goaccess 2>&1>/dev/null", $retval);
		if ($retval === 0) {

			foreach($records as $rec) {
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

				$web_folder = (($rec['type'] == 'vhostsubdomain' || $rec['type'] == 'vhostalias') ? $rec['web_folder'] : 'web');
				$domain = $rec['domain'];
				$statsdir = $rec['document_root'].'/'.$web_folder.'/stats';
				$goaccess_conf = $rec['document_root'].'/log/goaccess.conf';

				/*
				 In case that you use a different log format, you should use a custom goaccess.conf which you'll have to put into /usr/local/ispconfig/server/conf-custom/.
				 By default the originally, with GoAccess shipped goaccess.conf from /etc/ or /etc/goaccess will be used along with the log-format value COMBINED. 
				*/

				if(file_exists("/usr/local/ispconfig/server/conf-custom/goaccess.conf.master") && (!file_exists($goaccess_conf))) {
					copy("/usr/local/ispconfig/server/conf-custom/goaccess.conf.master", $goaccess_conf);
				} elseif(!file_exists($goaccess_conf)) {
					/*
					 By default the goaccess.conf should get copied by the webserver plugin but in case it wasn't, or it got deleted by accident we gonna copy it again to the destination dir.
					 Also there was no /usr/local/ispconfig/server/conf-custom/goaccess.conf.master, so we gonna use /etc/goaccess.conf as the base conf.
					*/
	                        	copy($goaccess_conf_main, $goaccess_conf);
		                        file_put_contents($goaccess_conf, preg_replace('/^(#)?log-format COMBINED/m', "log-format COMBINED", file_get_contents($goaccess_conf)));
				}

				/* Update the primary domain name in the title, it could occasionally change */
				if(is_file($goaccess_conf) && (filesize($goaccess_conf) > 0)) {
					$goaccess_content = file_get_contents($goaccess_conf);
					file_put_contents($goaccess_conf, preg_replace('/^(#)?html-report-title(.*)?/m', "html-report-title $domain", file_get_contents($goaccess_conf)));
					unset($goaccess_content);
				}



				if(!@is_dir($statsdir)) mkdir($statsdir);
				$username = $rec['system_user'];
				$groupname = $rec['system_group'];
				$docroot = $rec['document_root'];

				$goa_db_dir = $docroot.'/'.$web_folder.'/stats/.db/';
				$output_html = $docroot.'/'.$web_folder.'/stats/goaindex.html';
	                        if(!@is_dir($goa_db_dir)) mkdir($goa_db_dir);
	
	                        if(is_link('/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log')) unlink('/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log');
	                        symlink($logfile, '/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log');


				chown($statsdir, $username);
				chgrp($statsdir, $groupname);

				$goamonth = date("n");
				$goayear = date("Y");

				if (date("d") == 1) {
					$goamonth = date("m")-1;
					if (date("m") == 1) {
						$goayear = date("Y")-1;
						$goamonth = "12";
					}
				}

	
				if (date("d") == 2) {
					$goamonth = date("m")-1;
					if (date("m") == 1) {
						$goayear = date("Y")-1;
						$goamonth = "12";
					}

					$statsdirold = $statsdir."/".$goayear."-".$goamonth."/";

					if(!is_dir($statsdirold)) {
						mkdir($statsdirold);
					}

					// don't rotate db files per month
					//rename($goa_db_dir, $statsdirold.'db');
	                                //mkdir($goa_db_dir);

					$files = scandir($statsdir);

					foreach ($files as $file) {
						if (substr($file, 0, 1) != "." && !is_dir("$statsdir"."/"."$file") && substr($file, 0, 1) != "w" && substr($file, 0, 1) != "i") copy("$statsdir"."/"."$file", "$statsdirold"."$file");
					}
				}


				$output = shell_exec('goaccess --help');

				if(preg_match('/keep-db-files/', $output)) {
					$app->system->exec_safe("goaccess -f ? --config-file ? --load-from-disk --keep-db-files --db-path=? --output=?", $logfile, $goaccess_conf, $goa_db_dir, $output_html);

					if(!is_file($rec['document_root']."/".$web_folder."/stats/index.php")) {
						if(file_exists("/usr/local/ispconfig/server/conf-custom/goaccess_index.php.master")) {
							copy("/usr/local/ispconfig/server/conf-custom/goaccess_index.php.master", $rec['document_root']."/".$web_folder."/stats/index.php");
						} else {
							copy("/usr/local/ispconfig/server/conf/goaccess_index.php.master", $rec['document_root']."/".$web_folder."/stats/index.php");
						}
					}

		                        $app->log('Created GoAccess statistics for ' . $domain, LOGLEVEL_DEBUG);
		                        if(is_file($rec['document_root']."/".$web_folder."/stats/index.php")) {
		                                chown($rec['document_root']."/".$web_folder."/stats/index.php", $rec['system_user']);
		                                chgrp($rec['document_root']."/".$web_folder."/stats/index.php", $rec['system_group']);
		                        }

		                        $app->system->exec_safe('chown -R ?:? ?', $username, $groupname, $statsdir);

				} else {
			                $app->log("Stats not generated. The GoAccess binary was not compiled with btree support. Please recompile/reinstall GoAccess with btree support!", LOGLEVEL_ERROR);
				}

				unset($output);

		}
	} else {
		$app->log("Stats not generated. The GoAccess binary couldn't be found. Make sure that GoAccess is installed and that it is in \$PATH", LOGLEVEL_ERROR);
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
