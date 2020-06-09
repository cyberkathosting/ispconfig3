<?php

/*
	Copyright (c) 2007-2011, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

class monitor_tools {

	//** Get distribution identifier
	//** IMPORTANT!
	//   This is the same code as in install/lib/install.lib.php
	//   So if you change it here, you also have to change it in there!
	//   Please do not forget to remove the swriteln(); - lines here at this file
	function get_distname() {

	$distname = '';
	$distver = '';
	$distid = '';
	$distbaseid = '';

	//** Debian or Ubuntu
	if(file_exists('/etc/debian_version')) {
		
		// Check if this is Ubuntu and not Debian
		if (strstr(trim(file_get_contents('/etc/issue')), 'Ubuntu') || (is_file('/etc/os-release') && stristr(file_get_contents('/etc/os-release'), 'Ubuntu'))) {
			
			$issue = file_get_contents('/etc/issue');
			
			// Use content of /etc/issue file
			if(strstr($issue,'Ubuntu')) {
				if (strstr(trim($issue), 'LTS')) {
					$lts=" LTS";
				} else {
					$lts="";
				}

				$distname = 'Ubuntu';
				$distid = 'debian40';
				$distbaseid = 'debian';
				$ver = explode(' ', $issue);
				$ver = array_filter($ver);
				$ver = next($ver);
				$mainver = explode('.', $ver);
				$mainver = array_filter($mainver);
				$mainver = current($mainver).'.'.next($mainver);
			// Use content of /etc/os-release file
			} else {
				$os_release = file_get_contents('/etc/os-release');
				if (strstr(trim($os_release), 'LTS')) {
					$lts = " LTS";
				} else {
					$lts = "";
				}
				
				$distname = 'Ubuntu';
				$distid = 'debian40';
				$distbaseid = 'debian';

				preg_match("/.*VERSION=\"(.*)\".*/ui", $os_release, $ver);
				$ver = str_replace("LTS", "", $ver[1]);
				$ver = explode(" ", $ver, 2);
				$ver = reset($ver);
				$mainver = $ver;
			}
			switch ($mainver){
			case "18.04":
				$relname = "(Bionic Beaver)";
				$distconfid = 'ubuntu1804';
				break;
			case "17.10":
				$relname = "(Artful Aardvark)";
				$distconfid = 'ubuntu1710';
				break;
			case "17.04":
				$relname = "(Zesty Zapus)";
				$distconfid = 'ubuntu1604';
				break;
			case "16.10":
				$relname = "(Yakkety Yak)";
				$distconfid = 'ubuntu1604';
				break;
			case "16.04":
				$relname = "(Xenial Xerus)";
				$distconfid = 'ubuntu1604';
				break;
			case "15.10":
				$relname = "(Wily Werewolf)";
				break;
			case "15.04":
				$relname = "(Vivid Vervet)";
				break;
			case "14.10":
				$relname = "(Utopic Unicorn)";
				break;
			case "14.04":
				$relname = "(Trusty Tahr)";
				break;
			case "13.10":
				$relname = "(Saucy Salamander)";
				break;
			case "13.04":
				$relname = "(Raring Ringtail)";
				break;
			case "12.10":
				$relname = "(Quantal Quetzal)";
				break;
			case "12.04":
				$relname = "(Precise Pangolin)";
				break;
			case "11.10":
				$relname = "(Oneiric Ocelot)";
				break;
			case "11.14":
				$relname = "(Natty Narwhal)";
				break;
			case "10.10":
				$relname = "(Maverick Meerkat)";
				break;
			case "10.04":
				$relname = "(Lucid Lynx)";
				break;
			case "9.10":
				$relname = "(Karmic Koala)";
				break;
			case "9.04":
				$relname = "(Jaunty Jackpole)";
				break;
			case "8.10":
				$relname = "(Intrepid Ibex)";
				break;
			case "8.04":
				$relname = "(Hardy Heron)";
				break;
			case "7.10":
				$relname = "(Gutsy Gibbon)";
				break;
			case "7.04":
				$relname = "(Feisty Fawn)";
				break;
			case "6.10":
				$relname = "(Edgy Eft)";
				break;
			case "6.06":
				$relname = "(Dapper Drake)";
				break;
			case "5.10":
				$relname = "(Breezy Badger)";
				break;
			case "5.04":
				$relname = "(Hoary Hedgehog)";
				break;
			case "4.10":
				$relname = "(Warty Warthog)";
				break;
			default:
				$relname = "UNKNOWN";
			}
			$distver = $ver.$lts." ".$relname;
		} elseif(trim(file_get_contents('/etc/debian_version')) == '4.0') {
			$distname = 'Debian';
			$distver = '4.0';
			$distid = 'debian40';
			$distbaseid = 'debian';
		} elseif(strstr(trim(file_get_contents('/etc/debian_version')), '5.0')) {
			$distname = 'Debian';
			$distver = 'Lenny';
			$distid = 'debian40';
			$distbaseid = 'debian';
		} elseif(strstr(trim(file_get_contents('/etc/debian_version')), '6.0') || trim(file_get_contents('/etc/debian_version')) == 'squeeze/sid') {
			$distname = 'Debian';
			$distver = 'Squeeze/Sid';
			$distid = 'debian60';
			$distbaseid = 'debian';
		} elseif(strstr(trim(file_get_contents('/etc/debian_version')), '7.0') || substr(trim(file_get_contents('/etc/debian_version')),0,2) == '7.' || trim(file_get_contents('/etc/debian_version')) == 'wheezy/sid') {
			$distname = 'Debian';
			$distver = 'Wheezy/Sid';
			$distid = 'debian60';
			$distbaseid = 'debian';
		} elseif(substr(trim(file_get_contents('/etc/debian_version')),0,1) == '8') {
			$distname = 'Debian';
			$distver = 'Jessie';
			$distid = 'debian60';
			$distbaseid = 'debian';
		} elseif(substr(trim(file_get_contents('/etc/debian_version')),0,1) == '9') {
			$distname = 'Debian';
			$distver = 'Stretch';
			$distconfid = 'debian90';
			$distid = 'debian60';
			$distbaseid = 'debian';
		} elseif(substr(trim(file_get_contents('/etc/debian_version')),0,2) == '10') {
			$distname = 'Debian';
			$distver = 'Buster';
			$distconfid = 'debian100';
			$distid = 'debian60';
			$distbaseid = 'debian';
		} elseif(strstr(trim(file_get_contents('/etc/debian_version')), '/sid')) {
			$distname = 'Debian';
			$distver = 'Testing';
			$distid = 'debian60';
			$distconfid = 'debiantesting';
			$distbaseid = 'debian';
		} else {
			$distname = 'Debian';
			$distver = 'Unknown';
			$distid = 'debian60';
			$distconfid = 'debian100';
			$distbaseid = 'debian';
		}
	}

	//** OpenSuSE
	elseif(file_exists('/etc/SuSE-release')) {
		if(stristr(file_get_contents('/etc/SuSE-release'), '11.0')) {
			$distname = 'openSUSE';
			$distver = '11.0';
			$distid = 'opensuse110';
			$distbaseid = 'opensuse';
		} elseif(stristr(file_get_contents('/etc/SuSE-release'), '11.1')) {
			$distname = 'openSUSE';
			$distver = '11.1';
			$distid = 'opensuse110';
			$distbaseid = 'opensuse';
		} elseif(stristr(file_get_contents('/etc/SuSE-release'), '11.2')) {
			$distname = 'openSUSE';
			$distver = '11.2';
			$distid = 'opensuse112';
			$distbaseid = 'opensuse';
		} elseif(stristr(file_get_contents('/etc/os-release'), 'opensuse')) {
			$content = file_get_contents('/etc/os-release');
            preg_match_all('/NAME=\"([\w ]+)\"/m', $content, $name);
            preg_match_all('/VERSION_ID=\"([0-9]{1,2})\.?([0-9]{0,2})\.?([0-9]*).$/m', $content, $version);
			$distname = is_array($name) ? $name[1][0] : 'openSUSE';
			$distver = is_array($version) ? implode('.', array_filter(array($version[1][0],$version[2][0],$version[3][0]),'strlen')) : 'Unknown';
			$distid = 'opensuse112';
			$distbaseid = 'opensuse';
		}  else {
			$distname = 'openSUSE';
			$distver = 'Unknown';
			$distid = 'opensuse112';
			$distbaseid = 'opensuse';
		}
	}


	//** Redhat
	elseif(file_exists('/etc/redhat-release')) {

		$content = file_get_contents('/etc/redhat-release');

		if(stristr($content, 'Fedora release 9 (Sulphur)')) {
			$distname = 'Fedora';
			$distver = '9';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
		} elseif(stristr($content, 'Fedora release 10 (Cambridge)')) {
			$distname = 'Fedora';
			$distver = '10';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
		} elseif(stristr($content, 'Fedora release 10')) {
			$distname = 'Fedora';
			$distver = '11';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
		} elseif(stristr($content, 'CentOS release 5.2 (Final)')) {
			$distname = 'CentOS';
			$distver = '5.2';
			$distid = 'centos52';
			$distbaseid = 'fedora';
		} elseif(stristr($content, 'CentOS release 5.3 (Final)')) {
			$distname = 'CentOS';
			$distver = '5.3';
			$distid = 'centos53';
			$distbaseid = 'fedora';
		} elseif(stristr($content, 'CentOS release 5')) {
			$distname = 'CentOS';
			$distver = 'Unknown';
			$distid = 'centos53';
			$distbaseid = 'fedora';
		} elseif(stristr($content, 'CentOS Linux release 6') || stristr($content, 'CentOS release 6')) {
			$distname = 'CentOS';
			$distver = 'Unknown';
			$distid = 'centos53';
			$distbaseid = 'fedora';
		} elseif(stristr($content, 'CentOS Linux release 7')) {
			preg_match_all('/([0-9]{1,2})\.?([0-9]{0,2})\.?([0-9]*)/', $content, $version);
			$distname = 'CentOS';
			$distver = is_array($version)? implode('.', array_filter(array($version[1][0],$version[2][0],$version[3][0]),'strlen')) :'Unknown';
			$distbaseid = 'fedora';
			$var=explode(" ", $content);
			$var=explode(".", $var[3]);
			$var=$var[0].".".$var[1];
			if($var=='7.0' || $var=='7.1') {
				$distid = 'centos70';
			} else {
				$distid = 'centos72';
			}
                } elseif(stristr($content, 'CentOS Linux release 8')) {
                        preg_match_all('/([0-9]{1,2})\.?([0-9]{0,2})\.?([0-9]*)/', $content, $version);
                        $distname = 'CentOS';
                        $distver = is_array($version)? implode('.', array_filter(array($version[1][0],$version[2][0],$version[3][0]),'strlen')) :'Unknown';
                        $distbaseid = 'fedora';
                        $var=explode(" ", $content);
                        $var=explode(".", $var[3]);
                        $var=$var[0].".".$var[1];
		} else {
			$distname = 'Redhat';
			$distver = 'Unknown';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
		}
	}

	//** Gentoo
	elseif(file_exists('/etc/gentoo-release')) {

		$content = file_get_contents('/etc/gentoo-release');

		preg_match_all('/([0-9]{1,2})/', $content, $version);
		$distname = 'Gentoo';
		$distver = $version[0][0].$version[0][1];
		$distid = 'gentoo';
		$distbaseid = 'gentoo';

	} else {
		die('Unrecognized GNU/Linux distribution');
	}
	
	// Set $distconfid to distid, if no different id for the config is defined
	if(!isset($distconfid)) $distconfid = $distid;

	return array('name' => $distname, 'version' => $distver, 'id' => $distid, 'confid' => $distconfid, 'baseid' => $distbaseid);
	}

	// this function remains in the tools class, because it is used by cron AND rescue
	public function monitorServices() {
		global $app;
		global $conf;

		/** the id of the server as int */


		$server_id = intval($conf['server_id']);

		/**  get the "active" Services of the server from the DB */
		$services = $app->db->queryOneRecord('SELECT * FROM server WHERE server_id = ?', $server_id);
		/*
		 * If the DB is down, we have to set the db to "yes".
		 * If we don't do this, then the monitor will NOT monitor, that the db is down and so the
		 * rescue-module can not try to rescue the db
		 */
		if ($services == null) {
			$services['db_server'] = 1;
		}

		/* The type of the Monitor-data */
		$type = 'services';

		/** the State of the monitoring */
		/* ok, if ALL active services are running,
		 * error, if not
		 * There is no other state!
		 */
		$state = 'ok';

		/* Monitor Webserver */
		$data['webserver'] = -1; // unknown - not needed
		if ($services['web_server'] == 1) {
			if ($this->_checkTcp('localhost', 80)) {
				$data['webserver'] = 1;
			} else {
				$data['webserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor FTP-Server */
		$data['ftpserver'] = -1; // unknown - not needed
		if ($services['file_server'] == 1) {
			if ($this->_checkFtp('localhost', 21)) {
				$data['ftpserver'] = 1;
			} else {
				$data['ftpserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor SMTP-Server */
		$data['smtpserver'] = -1; // unknown - not needed
		if ($services['mail_server'] == 1) {
			if ($this->_checkTcp('localhost', 25)) {
				$data['smtpserver'] = 1;
			} else {
				$data['smtpserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor POP3-Server */
		$data['pop3server'] = -1; // unknown - not needed
		if ($services['mail_server'] == 1) {
			if ($this->_checkTcp('localhost', 110)) {
				$data['pop3server'] = 1;
			} else {
				$data['pop3server'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor IMAP-Server */
		$data['imapserver'] = -1; // unknown - not needed
		if ($services['mail_server'] == 1) {
			if ($this->_checkTcp('localhost', 143)) {
				$data['imapserver'] = 1;
			} else {
				$data['imapserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor BIND-Server */
		$data['bindserver'] = -1; // unknown - not needed
		if ($services['dns_server'] == 1) {
			if ($this->_checkUdp('localhost', 53)) {
				$data['bindserver'] = 1;
			} else {
				$data['bindserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor MySQL Server */
		$data['mysqlserver'] = -1; // unknown - not needed
		if ($services['db_server'] == 1) {
			if ($this->_checkTcp('localhost', 3306)) {
				$data['mysqlserver'] = 1;
			} else {
				$data['mysqlserver'] = 0;
				$state = 'error'; // because service is down
			}
		}
/*
		$data['mongodbserver'] = -1;
		if ($this->_checkTcp('localhost', 27017)) {
			$data['mongodbserver'] = 1;
		} else {
			$data['mongodbserver'] = 0;
*/
			//$state = 'error'; // because service is down
			/* TODO!!! check if this is a mongodbserver at all, otherwise it will always throw an error state!!! */
//		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function _getLogData($log) {
		global $conf;

		$dist = '';
		$logfile = '';

		if (@is_file('/etc/debian_version')) {
			$dist = 'debian';
		} elseif (@is_file('/etc/redhat-release')) {
			$dist = 'redhat';
		} elseif (@is_file('/etc/SuSE-release')) {
			$dist = 'suse';
		} elseif (@is_file('/etc/gentoo-release')) {
			$dist = 'gentoo';
		}

		switch ($log) {
		case 'log_mail':
			if ($dist == 'debian') {
				$logfile = '/var/log/mail.log';
			} elseif ($dist == 'redhat') {
				$logfile = '/var/log/maillog';
			} elseif ($dist == 'suse') {
				$logfile = '/var/log/mail.info';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/maillog';
			}
			break;
		case 'log_mail_warn':
			if ($dist == 'debian') {
				$logfile = '/var/log/mail.warn';
			} elseif ($dist == 'redhat') {
				$logfile = '/var/log/maillog';
			} elseif ($dist == 'suse') {
				$logfile = '/var/log/mail.warn';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/maillog';
			}
			break;
		case 'log_mail_err':
			if ($dist == 'debian') {
				$logfile = '/var/log/mail.err';
			} elseif ($dist == 'redhat') {
				$logfile = '/var/log/maillog';
			} elseif ($dist == 'suse') {
				$logfile = '/var/log/mail.err';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/maillog';
			}
			break;
		case 'log_messages':
			if ($dist == 'debian') {
				$logfile = '/var/log/syslog';
			} elseif ($dist == 'redhat') {
				$logfile = '/var/log/messages';
			} elseif ($dist == 'suse') {
				$logfile = '/var/log/messages';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/messages';
			}
			break;
		case 'log_ispc_cron':
			if ($dist == 'debian') {
				$logfile = $conf['ispconfig_log_dir'] . '/cron.log';
			} elseif ($dist == 'redhat') {
				$logfile = $conf['ispconfig_log_dir'] . '/cron.log';
			} elseif ($dist == 'suse') {
				$logfile = $conf['ispconfig_log_dir'] . '/cron.log';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/cron';
			}
			break;
		case 'log_freshclam':
			if ($dist == 'debian') {
				$logfile = '/var/log/clamav/freshclam.log';
			} elseif ($dist == 'redhat') {
				$logfile = (is_file('/var/log/clamav/freshclam.log') ? '/var/log/clamav/freshclam.log' : '/var/log/freshclam.log');
			} elseif ($dist == 'suse') {
				$logfile = '/var/log/freshclam.log';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/clamav/freshclam.log';
			}
			break;
		case 'log_clamav':
			if ($dist == 'debian') {
				$logfile = '/var/log/clamav/clamav.log';
			} elseif ($dist == 'redhat') {
				$logfile = (is_file('/var/log/clamav/clamd.log') ? '/var/log/clamav/clamd.log' : '/var/log/maillog');
			} elseif ($dist == 'suse') {
				$logfile = '/var/log/clamd.log';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/clamav/clamd.log';
			}
			break;
		case 'log_fail2ban':
			if ($dist == 'debian') {
				$logfile = '/var/log/fail2ban.log';
			} elseif ($dist == 'redhat') {
				$logfile = '/var/log/fail2ban.log';
			} elseif ($dist == 'suse') {
				$logfile = '/var/log/fail2ban.log';
			} elseif ($dist == 'gentoo') {
				$logfile = '/var/log/fail2ban.log';
			}
			break;
		case 'log_mongodb':
			$logfile = '/var/log/mongodb/mongodb.log';
			break;
		case 'log_ispconfig':
			if ($dist == 'debian') {
				$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
			} elseif ($dist == 'redhat') {
				$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
			} elseif ($dist == 'suse') {
				$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
			} elseif ($dist == 'gentoo') {
				$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
			}
			break;
		default:
			$logfile = '';
			break;
		}

		// Getting the logfile content
		if ($logfile != '') {
			if (stristr($logfile, ';') or substr($logfile, 0, 9) != '/var/log/' or stristr($logfile, '..')) {
				$log = 'Logfile path error.';
			} else {
				$log = '';
				if (is_readable($logfile)) {
					$fd = popen('tail -n 100 ' . escapeshellarg($logfile), 'r');
					if ($fd) {
						while (!feof($fd)) {
							$log .= fgets($fd, 4096);
							$n++;
							if ($n > 1000)
								break;
						}
						fclose($fd);
					}
				} else {
					$log = 'Unable to read ' . $logfile;
				}
			}
		}

		return $log;
	}

	private function _checkTcp($host, $port) {
		/* Try to open a connection */
		$fp = @fsockopen($host, $port, $errno, $errstr, 2);

		if ($fp) {
			/*
			 * We got a connection, this means, everything is O.K.
			 * But maybe we are able to do more deep testing?
			 */
			if ($port == 80) {
				/*
				 * Port 80 means, testing APACHE
				 * So we can do a deepter test and try to get data over this connection.
				 * (if apache hangs, we get a connection but a timeout by trying to GET the data!)
				 */
				// fwrite($fp, "GET / HTTP/1.0\r\n\r\n");
				$out = "GET / HTTP/1.1\r\n";
				$out .= "Host: localhost\r\n";
				$out .= "User-Agent: Mozilla/5.0 (ISPConfig monitor)\r\n";
				$out .= "Accept: application/xml,application/xhtml+xml,text/html\r\n";
				$out .= "Connection: Close\r\n\r\n";
				fwrite($fp, $out);
				stream_set_timeout($fp, 5); // Timeout after 5 seconds
				$res = fread($fp, 10);  // try to get 10 bytes (enough to test!)
				$info = stream_get_meta_data($fp);
				if ($info['timed_out']) {
					return false; // Apache was not able to send data over this connection
				}
			}

			/* The connection is no longer needed */
			fclose($fp);
			/* We are able to establish a connection */
			return true;
		} else {
			/* We are NOT able to establish a connection */
			return false;
		}
	}

	private function _checkUdp($host, $port) {

		$fp = @fsockopen('udp://' . $host, $port, $errno, $errstr, 2);

		if ($fp) {
			fclose($fp);
			return true;
		} else {
			return false;
		}
	}

	private function _checkFtp($host, $port) {

		$conn_id = @ftp_connect($host, $port);

		if ($conn_id) {
			@ftp_close($conn_id);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set the state to the given level (or higher, but not lesser).
	 * * If the actual state is critical and you call the method with ok,
	 *   then the state is critical.
	 *
	 * * If the actual state is critical and you call the method with error,
	 *   then the state is error.
	 */
	public function _setState($oldState, $newState) {
		/*
		 * Calculate the weight of the old state
		 */
		switch ($oldState) {
		case 'no_state': $oldInt = 0;
			break;
		case 'ok': $oldInt = 1;
			break;
		case 'unknown': $oldInt = 2;
			break;
		case 'info': $oldInt = 3;
			break;
		case 'warning': $oldInt = 4;
			break;
		case 'critical': $oldInt = 5;
			break;
		case 'error': $oldInt = 6;
			break;
		}
		/*
		 * Calculate the weight of the new state
		 */
		switch ($newState) {
		case 'no_state': $newInt = 0;
			break;
		case 'ok': $newInt = 1;
			break;
		case 'unknown': $newInt = 2;
			break;
		case 'info': $newInt = 3;
			break;
		case 'warning': $newInt = 4;
			break;
		case 'critical': $newInt = 5;
			break;
		case 'error': $newInt = 6;
			break;
		}

		/*
		 * Set to the higher level
		 */
		if ($newInt > $oldInt) {
			return $newState;
		} else {
			return $oldState;
		}
	}

	/**
	 * Deletes Records older than 4 minutes.
	 * The monitor writes new data every 5 minutes or longer (4 hour, 1 day).
	 * So if i delete all Date older than 4 minutes i can be sure, that all old data
	 * are deleted...
	 */
	public function delOldRecords($type, $serverId) {
		global $app;

		// $now = time();
		// $old = $now - (4 * 60); // 4 minutes
		$old = 240; //seconds

		/*
		 * ATTENTION if i do NOT pay attention of the server id, i delete all data (of the type)
		 * of ALL servers. This means, if i have a multiserver-environment and a server has a
		 * time not synced with the others (for example, all server has 11:00 and ONE server has
		 * 10:45) then the actual data of this server (with the time-stamp 10:45) get lost
		 * even though it is the NEWEST data of this server. To avoid this i HAVE to include
		 * the server-id!
		 */
		$sql = 'DELETE FROM `monitor_data` WHERE `type` = ? AND `created` < UNIX_TIMESTAMP() - ? AND `server_id` = ?';
		$app->dbmaster->query($sql, $type, $old, $serverId);
	}

	public function send_notification_email($template, $placeholders, $recipients) {
		global $conf;

		if(!is_array($recipients) || count($recipients) < 1) return false;
		if(!is_array($placeholders)) $placeholders = array();

		if(file_exists($conf['rootpath'].'/conf-custom/mail/' . $template . '_'.$conf['language'].'.txt')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/' . $template . '_'.$conf['language'].'.txt');
		} elseif(file_exists($conf['rootpath'].'/conf-custom/mail/' . $template . '_en.txt')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/' . $template . '_en.txt');
		} elseif(file_exists($conf['rootpath'].'/conf/mail/' . $template . '_'.$conf['language'].'.txt')) {
			$lines = file($conf['rootpath'].'/conf/mail/' . $template . '_'.$conf['language'].'.txt');
		} else {
			$lines = file($conf['rootpath'].'/conf/mail/' . $template . '_en.txt');
		}

		//* get mail headers, subject and body
		$mailHeaders = '';
		$mailBody = '';
		$mailSubject = '';
		$inHeader = true;
		for($l = 0; $l < count($lines); $l++) {
			/* Trim only in headers */
			if($inHeader && trim($lines[$l]) == '') {
				$inHeader = false;
				continue;
			}
			if($inHeader == true) {
				$parts = explode(':', $lines[$l], 2);
				if(strtolower($parts[0]) == 'subject') {
					$mailSubject = trim($parts[1]);
					continue;
				}
				unset($parts);
				$mailHeaders .= trim($lines[$l]) . "\n";
			} else {
				$mailBody .= trim($lines[$l]) . "\n";
			}
		}
		$mailBody = trim($mailBody);

		//* Replace placeholders
		$mailHeaders = strtr($mailHeaders, $placeholders);
		$mailSubject = strtr($mailSubject, $placeholders);
		$mailBody = strtr($mailBody, $placeholders);

		for($r = 0; $r < count($recipients); $r++) {
			mail($recipients[$r], $mailSubject, $mailBody, $mailHeaders);
		}

		unset($mailSubject);
		unset($mailHeaders);
		unset($mailBody);
		unset($lines);

		return true;
	}

}

?>
