<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

//* Set timezone
if(isset($conf['timezone']) && $conf['timezone'] != '') date_default_timezone_set($conf['timezone']);

class app {

	var $loaded_modules = array();
	var $loaded_plugins = array();
	var $_calling_script = '';
	/**
	 * @var db
	 */
	public $db;

	function __construct() {

		global $conf;

		if($conf['start_db'] == true) {
			$this->load('db_'.$conf['db_type']);
			try {
				$this->db = new db;
			} catch (Exception $e) {
				$this->db = false;
			}

			/*
					Initialize the connection to the master DB,
					if we are in a multiserver setup
					*/

			if($conf['dbmaster_host'] != '' && ($conf['dbmaster_host'] != $conf['db_host'] || ($conf['dbmaster_host'] == $conf['db_host'] && $conf['dbmaster_database'] != $conf['db_database']))) {
				try {
					$this->dbmaster = new db($conf['dbmaster_host'], $conf['dbmaster_user'], $conf['dbmaster_password'], $conf['dbmaster_database'], $conf['dbmaster_port'], $conf['dbmaster_client_flags']);
				} catch (Exception $e) {
					$this->dbmaster = false;
				}
			} else {
				$this->dbmaster = $this->db;
			}


		}

	}

	public function __get($name) {
		$valid_names = array('functions', 'getconf', 'letsencrypt', 'modules', 'plugins', 'services', 'system');
		if(!in_array($name, $valid_names)) {
			trigger_error('Undefined property ' . $name . ' of class app', E_USER_WARNING);
		}
		if(property_exists($this, $name)) {
			return $this->{$name};
		}
		$this->uses($name);
		if(property_exists($this, $name)) {
			return $this->{$name};
		} else {
			trigger_error('Undefined property ' . $name . ' of class app', E_USER_WARNING);
		}
	}
	
	function setCaller($caller) {
		$this->_calling_script = $caller;
	}
	
	function getCaller() {
		return $this->_calling_script;
	}
	
	function forceErrorExit($errmsg = 'undefined') {
		global $conf;
		
		if($this->_calling_script == 'server') {
			@unlink($conf['temppath'] . $conf['fs_div'] . '.ispconfig_lock');
		}
		die('Exiting because of error: ' . $errmsg);
	}

	function uses($classes) {

		global $conf;

		$cl = explode(',', $classes);
		if(is_array($cl)) {
			foreach($cl as $classname) {
				if(!@is_object($this->$classname)) {
					if(is_file($conf['classpath'].'/'.$classname.'.inc.php') && (DEVSYSTEM ||  !is_link($conf['classpath'].'/'.$classname.'.inc.php'))) {
						include_once $conf['classpath'].'/'.$classname.'.inc.php';
						$this->$classname = new $classname;
					}
				}
			}
		}
	}

	function load($classes) {

		global $conf;

		$cl = explode(',', $classes);
		if(is_array($cl)) {
			foreach($cl as $classname) {
				if(is_file($conf['classpath'].'/'.$classname.'.inc.php') && (DEVSYSTEM || !is_link($conf['classpath'].'/'.$classname.'.inc.php'))) {
					include_once $conf['classpath'].'/'.$classname.'.inc.php';
				} else {
					die('Unable to load: '.$conf['classpath'].'/'.$classname.'.inc.php');
				}
			}
		}
	}

	/*
         0 = DEBUG
         1 = WARNING
         2 = ERROR
        */

	function log($msg, $priority = 0, $dblog = true) {

		global $conf;

		switch ($priority) {
		case 0:
			$priority_txt = 'DEBUG';
			break;
		case 1:
			$priority_txt = 'WARNING';
			break;
		case 2:
			$priority_txt = 'ERROR';
			break;
		}
		$log_msg = @date('d.m.Y-H:i').' - '.$priority_txt.' - '. $msg;

		if($priority >= $conf['log_priority']) {
			//if (is_writable($conf["log_file"])) {
			if (!$fp = fopen($conf['log_file'], 'a')) {
				die('Unable to open logfile.');
			}

			if (!fwrite($fp, $log_msg."\r\n")) {
				die('Unable to write to logfile.');
			}

			echo $log_msg."\n";
			fclose($fp);

			// Log to database
			if($dblog === true && isset($this->dbmaster)) {
				$server_id = $conf['server_id'];
				$loglevel = $priority;
				$message = $msg;
				$datalog_id = (isset($this->modules->current_datalog_id) && $this->modules->current_datalog_id > 0)?$this->modules->current_datalog_id:0;
				if($datalog_id > 0) {
					$tmp_rec = $this->dbmaster->queryOneRecord("SELECT count(syslog_id) as number FROM sys_log WHERE datalog_id = ? AND loglevel = ?", $datalog_id, LOGLEVEL_ERROR);
					//* Do not insert duplicate errors into the web log.
					if($tmp_rec['number'] == 0) {
						$sql = "INSERT INTO sys_log (server_id,datalog_id,loglevel,tstamp,message) VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)";
						$this->dbmaster->query($sql, $server_id, $datalog_id, $loglevel, $message);
					}
				} else {
					$sql = "INSERT INTO sys_log (server_id,datalog_id,loglevel,tstamp,message) VALUES (?, 0, ?, UNIX_TIMESTAMP(), ?)";
					$this->dbmaster->query($sql, $server_id, $loglevel, $message);
				}
			}

			//} else {
			//    die("Unable to write to logfile.");
			//}


		} // if

		if(isset($conf['admin_notify_priority']) && $priority >= $conf['admin_notify_priority'] && $conf['admin_mail'] != '') {
			// send notification to admin
			$mailBody = $log_msg;
			$mailSubject = substr($log_msg, 0, 50).'...';
			$mailHeaders      = "MIME-Version: 1.0" . "\n";
			$mailHeaders     .= "Content-type: text/plain; charset=utf-8" . "\n";
			$mailHeaders     .= "Content-Transfer-Encoding: 8bit" . "\n";
			$mailHeaders     .= "From: ". $conf['admin_mail'] . "\n";
			$mailHeaders     .= "Reply-To: ". $conf['admin_mail'] . "\n";

			mail($conf['admin_mail'], $mailSubject, $mailBody, $mailHeaders);
		}
	} // func


	/*
         0 = DEBUG
         1 = WARNING
         2 = ERROR
        */

	function error($msg) {
		$this->log($msg, 3);
		die($msg);
	}

}

/*
 Initialize application (app) object
*/

$app = new app;

?>
