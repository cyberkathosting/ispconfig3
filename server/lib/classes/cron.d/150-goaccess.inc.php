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

		$sql = "SELECT domain_id, sys_groupid, domain, document_root, web_folder, type, system_user, system_group, parent_domain_id FROM web_domain WHERE (type = 'vhost' or type = 'vhostsubdomain' or type = 'vhostalias') and stats_type = 'goaccess' AND server_id = ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);

		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		if(is_array($records) && !empty($records)) {

	                /* Check if goaccess binary is in path/installed */
			if($app->system->is_installed('goaccess')) {

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
						$app->system->copy("/usr/local/ispconfig/server/conf-custom/goaccess.conf.master", $goaccess_conf);
					} elseif(!file_exists($goaccess_conf)) {

						/*
						 By default the goaccess.conf should get copied by the webserver plugin but in case it wasn't, or it got deleted by accident we gonna copy it again to the destination dir.
						 Also there was no /usr/local/ispconfig/server/conf-custom/goaccess.conf.master, so we gonna use /etc/goaccess.conf or /etc/goaccess/goaccess.conf as the base conf.
						*/

						$app->system->copy($goaccess_conf_main, $goaccess_conf);
						$content = $app->system->file_get_contents($goaccess_conf, true);
						$content = preg_replace('/^(#)?log-format COMBINED/m', "log-format COMBINED", $content);
						$app->system->file_put_contents($goaccess_conf, $content, true);
						unset($content);
					}

	                                $username = $rec['system_user'];
	                                $groupname = $rec['system_group'];
	                                $docroot = $rec['document_root'];

					if(!@is_dir($statsdir)) $app->system->mkdirpath($statsdir, 0755, $username, $groupname);

                                        $goa_db_dir = $docroot.'/log/goaccess_db';
					$output_html = $docroot.'/'.$web_folder.'/stats/goaindex.html';
		                        if(!@is_dir($goa_db_dir)) $app->system->mkdirpath($goa_db_dir);
	
		                        if(is_link('/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log')) $app->system->unlink('/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log');

					symlink($logfile, '/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log');
					$app->system->exec_safe('chown -R ?:? ?', $username, $groupname, $statsdir);
	
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
							 $app->system->mkdirpath($statsdirold, 0755, $username, $groupname);
						}

						$files = scandir($statsdir);
						if (($key = array_search('index.php', $files)) !== false) {
							unset($files[$key]);
						}

						foreach ($files as $file) {
							if (substr($file, 0, 1) != "." && !is_dir("$statsdir"."/"."$file") && substr($file, 0, 1) != "w" && substr($file, 0, 1) != "i") $app->system->move("$statsdir"."/"."$file", "$statsdirold"."$file");
						}
					}

					// Get the GoAccess version
					$match = array();
	
					$goaccess_version = $app->system->system_safe('goaccess --version 2>&1');

					if(preg_match('/[0-9]\.[0-9]{1,2}/', $goaccess_version, $match)) {
						$goaccess_version = $match[0];
					}


					$sql_user = "SELECT client_id FROM sys_group WHERE groupid = ?";
					$rec_user = $app->db->queryOneRecord($sql_user, $rec['sys_groupid']);
					$lang_query = "SELECT country,language FROM client WHERE client_id = ?";
					$lang_user = $app->db->queryOneRecord($lang_query, $rec_user['client_id']);
					$cust_lang = $lang_user['language']."_".strtoupper($lang_user['language']).".UTF-8";

					switch($lang_user['language'])
					{
						case 'en':
							$cust_lang = 'en_UK.UTF-8';
							break;
						case 'br':
							$cust_lang = 'pt_BR.UTF-8';
							break;
                                                case 'pt':
                                                        $cust_lang = 'pt_BR.UTF-8';
                                                        break;
						case 'ca':
							$cust_lang = 'en_US.UTF-8';
							break;
						case 'ja':
							$cust_lang = 'ja_JP.UTF-8';
							break;
						case 'ar':
							$cust_lang = 'es_ES.UTF-8';
							break;
						case 'el':
							$cust_lang = 'el_GR.UTF-8';
							break;
						case 'se':
							$cust_lang = 'sv_SE.UTF-8';
							break;
						case 'dk':
							$cust_lang = 'da_DK.UTF-8';
							break;
						case 'cz':
							$cust_lang = 'cs_CZ.UTF-8';
							break;
					}


                                        /*
                                         * GoAccess removed with 1.4 B+Tree support and supports from this version on only "In-Memory with On-Disk Persistance Storage".
                                         * For versions prior 1.4 you need GoAccess with B+Tree support compiled!
                                         */

					if(version_compare($goaccess_version,1.4) >= 0) {
						$app->system->exec_safe("LANG=? goaccess -f ? --config-file ? --restore --persist --db-path=? --output=?", $cust_lang, $logfile, $goaccess_conf, $goa_db_dir, $output_html);
					} else {
						$output = $app->system->system_safe('goaccess --help 2>&1');
						preg_match('/keep-db-files/', $output, $match);
						if($match[0] == "keep-db-files") {
							$app->system->exec_safe("LANG=? goaccess -f ? --config-file ? --load-from-disk --keep-db-files --db-path=? --output=?", $cust_lang, $logfile, $goaccess_conf, $goa_db_dir, $output_html);
						} else {
		                                        $app->log("Stats couldn't be generated. The GoAccess binary wasn't compiled with B+Tree support. Please recompile/reinstall GoAccess with B+Tree support, or install GoAccess version >= 1.4! (recommended)", LOGLEVEL_ERROR);
						}
		                                unset($output);
					}

					unset($cust_lang);
					unset($sql_user);
					unset($rec_user);
					unset($lang_query);
					unset($lang_user);
	
					if(!is_file($rec['document_root']."/".$web_folder."/stats/index.php")) {
						if(file_exists("/usr/local/ispconfig/server/conf-custom/goaccess_index.php.master")) {
							$app->system->copy("/usr/local/ispconfig/server/conf-custom/goaccess_index.php.master", $rec['document_root']."/".$web_folder."/stats/index.php");
						} else {
							$app->system->copy("/usr/local/ispconfig/server/conf/goaccess_index.php.master", $rec['document_root']."/".$web_folder."/stats/index.php");
						}
					}

		                        $app->log('Created GoAccess statistics for ' . $domain, LOGLEVEL_DEBUG);
		                        if(is_file($rec['document_root']."/".$web_folder."/stats/index.php")) {
		                                $app->system->chown($rec['document_root']."/".$web_folder."/stats/index.php", $rec['system_user']);
		                                $app->system->chgrp($rec['document_root']."/".$web_folder."/stats/index.php", $rec['system_group']);
		                        }

					$app->system->exec_safe('chown -R ?:? ?', $username, $groupname, $statsdir);
				}
			} else {
				$app->log("Stats couldn't be generated. The GoAccess binary couldn't be found. Make sure that GoAccess is installed and that it is in \$PATH", LOGLEVEL_ERROR);
			}

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
