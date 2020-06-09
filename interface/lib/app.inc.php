<?php

/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
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

//* Enable gzip compression for the interface
ob_start('ob_gzhandler');

//* Set timezone
if(isset($conf['timezone']) && $conf['timezone'] != '') date_default_timezone_set($conf['timezone']);

//* Set error reporting level when we are not on a developer system
if(DEVSYSTEM == 0) {
	@ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

/*
    Application Class
*/
class app {

	private $_language_inc = 0;
	private $_wb;
	private $_loaded_classes = array();
	private $_conf;
	private $_security_config;
	
	public $loaded_plugins = array();

	public function __construct() {
		global $conf;

		if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']) || isset($_REQUEST['s']) || isset($_REQUEST['s_old']) || isset($_REQUEST['conf'])) {
			die('Internal Error: var override attempt detected');
		}
		
		$this->_conf = $conf;
		if($this->_conf['start_db'] == true) {
			$this->load('db_'.$this->_conf['db_type']);
			try {
				$this->db = new db;
			} catch (Exception $e) {
				$this->db = false;
			}
		}
		$this->uses('functions'); // we need this before all others!
		$this->uses('auth,plugin,ini_parser,getconf');
		
	}

	public function __get($prop) {
		if(property_exists($this, $prop)) return $this->{$prop};
		
		$this->uses($prop);
		if(property_exists($this, $prop)) return $this->{$prop};
		else trigger_error('Undefined property ' . $prop . ' of class app', E_USER_WARNING);
	}
	
	public function __destruct() {
		session_write_close();
	}
	
	public function initialize_session() {
		//* Start the session
		if($this->_conf['start_session'] == true) {
			session_name('ISPCSESS');
			$this->uses('session');
			$sess_timeout = $this->conf('interface', 'session_timeout');
			$cookie_domain = $this->get_cookie_domain();
			$this->log("cookie_domain is ".$cookie_domain,0);
			$cookie_domain = '';
			$cookie_secure = ($_SERVER["HTTPS"] == 'on')?true:false;
			if($sess_timeout) {
				/* check if user wants to stay logged in */
				if(isset($_POST['s_mod']) && isset($_POST['s_pg']) && $_POST['s_mod'] == 'login' && $_POST['s_pg'] == 'index' && isset($_POST['stay']) && $_POST['stay'] == '1') {
					/* check if staying logged in is allowed */
					$this->uses('ini_parser');
					$tmp = $this->db->queryOneRecord('SELECT config FROM sys_ini WHERE sysini_id = 1');
					$tmp = $this->ini_parser->parse_ini_string(stripslashes($tmp['config']));
					if(!isset($tmp['misc']['session_allow_endless']) || $tmp['misc']['session_allow_endless'] != 'y') {
						$this->session->set_timeout($sess_timeout);
						session_set_cookie_params(3600 * 24 * 365,'/',$cookie_domain,$cookie_secure,true); // cookie timeout is never updated, so it must not be short
					} else {
						// we are doing login here, so we need to set the session data
						$this->session->set_permanent(true);
						$this->session->set_timeout(365 * 24 * 3600,'/',$cookie_domain,$cookie_secure,true); // one year
						session_set_cookie_params(3600 * 24 * 365,'/',$cookie_domain,$cookie_secure,true); // cookie timeout is never updated, so it must not be short
					}
				} else {
					$this->session->set_timeout($sess_timeout);
					session_set_cookie_params(3600 * 24 * 365,'/',$cookie_domain,$cookie_secure,true); // cookie timeout is never updated, so it must not be short
				}
			} else {
				session_set_cookie_params(0,'/',$cookie_domain,$cookie_secure,true); // until browser is closed
			}
			
			session_set_save_handler( array($this->session, 'open'),
				array($this->session, 'close'),
				array($this->session, 'read'),
				array($this->session, 'write'),
				array($this->session, 'destroy'),
				array($this->session, 'gc'));

			session_start();
			
			//* Initialize session variables
			if(!isset($_SESSION['s']['id']) ) $_SESSION['s']['id'] = session_id();
			if(empty($_SESSION['s']['theme'])) $_SESSION['s']['theme'] = $conf['theme'];
			if(empty($_SESSION['s']['language'])) $_SESSION['s']['language'] = $conf['language'];
		}

	}
	
	public function uses($classes) {
		$cl = explode(',', $classes);
		if(is_array($cl)) {
			foreach($cl as $classname) {
				$classname = trim($classname);
				//* Class is not loaded so load it
				if(!array_key_exists($classname, $this->_loaded_classes) && is_file(ISPC_CLASS_PATH."/$classname.inc.php")) {
					include_once ISPC_CLASS_PATH."/$classname.inc.php";
					$this->$classname = new $classname();
					$this->_loaded_classes[$classname] = true;
				}
			}
		}
	}

	public function load($files) {
		$fl = explode(',', $files);
		if(is_array($fl)) {
			foreach($fl as $file) {
				$file = trim($file);
				include_once ISPC_CLASS_PATH."/$file.inc.php";
			}
		}
	}
	
	public function conf($plugin, $key, $value = null) {
		if(is_null($value)) {
			$tmpconf = $this->db->queryOneRecord("SELECT `value` FROM `sys_config` WHERE `group` = ? AND `name` = ?", $plugin, $key);
			if($tmpconf) return $tmpconf['value'];
			else return null;
		} else {
			if($value === false) {
				$this->db->query("DELETE FROM `sys_config` WHERE `group` = ? AND `name` = ?", $plugin, $key);
				return null;
			} else {
				$this->db->query("REPLACE INTO `sys_config` (`group`, `name`, `value`) VALUES (?, ?, ?)", $plugin, $key, $value);
				return $value;
			}
		}
	}

	/** Priority values are: 0 = DEBUG, 1 = WARNING,  2 = ERROR */


	public function log($msg, $priority = 0) {
		global $conf;
		if($priority >= $this->_conf['log_priority']) {
			// $server_id = $conf["server_id"];
			$server_id = 0;
			$priority = $this->functions->intval($priority);
			$tstamp = time();
			$msg = '[INTERFACE]: '.$msg;
			$this->db->query("INSERT INTO sys_log (server_id,datalog_id,loglevel,tstamp,message) VALUES (?, 0, ?, ?, ?)", $server_id, $priority,$tstamp,$msg);
			/*
			if (is_writable($this->_conf['log_file'])) {
				if (!$fp = fopen ($this->_conf['log_file'], 'a')) {
					$this->error('Unable to open logfile: ' . $this->_conf['log_file']);
				}
				if (!fwrite($fp, date('d.m.Y-H:i').' - '. $msg."\r\n")) {
					$this->error('Unable to write to logfile: ' . $this->_conf['log_file']);
				}
				fclose($fp);
			} else {
				$this->error('Unable to write to logfile: ' . $this->_conf['log_file']);
			}
			*/
		}
	}

	/** Priority values are: 0 = DEBUG, 1 = WARNING,  2 = ERROR */
	public function error($msg, $next_link = '', $stop = true, $priority = 1) {
		//$this->uses("error");
		//$this->error->message($msg, $priority);
		if($stop == true) {
			/*
			 * We always have a error. So it is better not to use any more objects like
			 * the template or so, because we don't know why the error occours (it could be, that
			 * the error occours in one of these objects..)
			 */
			/*
			 * Use the template inside the user-template - Path. If it is not found, fallback to the
			 * default-template (the "normal" behaviour of all template - files)
			 */
			if (file_exists(dirname(__FILE__) . '/../web/themes/' . $_SESSION['s']['theme'] . '/templates/error.tpl.htm')) {
				$content = file_get_contents(dirname(__FILE__) . '/../web/themes/' . $_SESSION['s']['theme'] . '/templates/error.tpl.htm');
			} else {
				$content = file_get_contents(dirname(__FILE__) . '/../web/themes/default/templates/error.tpl.htm');
			}
			if($next_link != '') $msg .= '<a href="'.$next_link.'">Next</a>';
			$content = str_replace('###ERRORMSG###', $msg, $content);
			die($content);
		} else {
			echo $msg;
			if($next_link != '') echo "<a href='$next_link'>Next</a>";
		}
	}

	/** Translates strings in current language */
	public function lng($text) {
		global $conf;
		if($this->_language_inc != 1) {
			$language = (isset($_SESSION['s']['language']))?$_SESSION['s']['language']:$conf['language'];
			//* loading global Wordbook
			$this->load_language_file('lib/lang/'.$language.'.lng');
			//* Load module wordbook, if it exists
			if(isset($_SESSION['s']['module']['name'])) {
				$lng_file = 'web/'.$_SESSION['s']['module']['name'].'/lib/lang/'.$language.'.lng';
				if(!file_exists(ISPC_ROOT_PATH.'/'.$lng_file)) $lng_file = '/web/'.$_SESSION['s']['module']['name'].'/lib/lang/en.lng';
				$this->load_language_file($lng_file);
			}
			$this->_language_inc = 1;
		}
		if(isset($this->_wb[$text]) && $this->_wb[$text] !== '') {
			$text = $this->_wb[$text];
		} else {
			if($this->_conf['debug_language']) {
				$text = '#'.$text.'#';
			}
		}
		return $text;
	}

	//** Helper function to load the language files.
	public function load_language_file($filename) {
		$filename = ISPC_ROOT_PATH.'/'.$filename;
		if(substr($filename, -4) != '.lng') $this->error('Language file has wrong extension.');
		if(file_exists($filename)) {
			@include $filename;
			if(is_array($wb)) {
				if(is_array($this->_wb)) {
					$this->_wb = array_merge($this->_wb, $wb);
				} else {
					$this->_wb = $wb;
				}
			}
		}
	}

	public function tpl_defaults() {
		$this->tpl->setVar('app_title', $this->_conf['app_title']);
		if(isset($_SESSION['s']['user'])) {
			$this->tpl->setVar('app_version', $this->_conf['app_version']);
			// get pending datalog changes
			$datalog = $this->db->datalogStatus();
			$this->tpl->setVar('datalog_changes_txt', $this->lng('datalog_changes_txt'));
			$this->tpl->setVar('datalog_changes_end_txt', $this->lng('datalog_changes_end_txt'));
			$this->tpl->setVar('datalog_changes_count', $datalog['count']);
			$this->tpl->setLoop('datalog_changes', $datalog['entries']);
		} else {
			$this->tpl->setVar('app_version', '');
		}
		$this->tpl->setVar('app_link', $this->_conf['app_link']);
		/*
		if(isset($this->_conf['app_logo']) && $this->_conf['app_logo'] != '' && @is_file($this->_conf['app_logo'])) {
			$this->tpl->setVar('app_logo', '<img src="'.$this->_conf['app_logo'].'">');
		} else {
			$this->tpl->setVar('app_logo', '&nbsp;');
		}
		*/
		$this->tpl->setVar('app_logo', $this->_conf['logo']);

		$this->tpl->setVar('phpsessid', session_id());

		$this->tpl->setVar('theme', $_SESSION['s']['theme'], true);
		$this->tpl->setVar('html_content_encoding', $this->_conf['html_content_encoding']);

		$this->tpl->setVar('delete_confirmation', $this->lng('delete_confirmation'));
		//print_r($_SESSION);
		if(isset($_SESSION['s']['module']['name'])) {
			$this->tpl->setVar('app_module', $_SESSION['s']['module']['name'], true);
			$this->tpl->setVar('session_module', $_SESSION['s']['module']['name'], true);
		}
		if(isset($_SESSION['s']['user']) && $_SESSION['s']['user']['typ'] == 'admin') {
			$this->tpl->setVar('is_admin', 1);
		}
		if(isset($_SESSION['s']['user']) && $this->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$this->tpl->setVar('is_reseller', 1);
		}
		/* Show username */
		if(isset($_SESSION['s']['user'])) {
			$this->tpl->setVar('cpuser', $_SESSION['s']['user']['username'], true);
			$this->tpl->setVar('logout_txt', $this->lng('logout_txt'));
			/* Show search field only for normal users, not mail users */
			if(stristr($_SESSION['s']['user']['username'], '@')){
				$this->tpl->setVar('usertype', 'mailuser');
			} else {
				$this->tpl->setVar('usertype', 'normaluser');
			}
		}

		/* Global Search */
		$this->tpl->setVar('globalsearch_resultslimit_of_txt', $this->lng('globalsearch_resultslimit_of_txt'));
		$this->tpl->setVar('globalsearch_resultslimit_results_txt', $this->lng('globalsearch_resultslimit_results_txt'));
		$this->tpl->setVar('globalsearch_noresults_text_txt', $this->lng('globalsearch_noresults_text_txt'));
		$this->tpl->setVar('globalsearch_noresults_limit_txt', $this->lng('globalsearch_noresults_limit_txt'));
		$this->tpl->setVar('globalsearch_searchfield_watermark_txt', $this->lng('globalsearch_searchfield_watermark_txt'));
	}

	public function is_under_maintenance() {
		$system_config_misc = $this->getconf->get_global_config('misc');
		$maintenance_mode = 'n';
		$maintenance_mode_exclude_ips = [];

		if (!empty($system_config_misc['maintenance_mode'])) {
			$maintenance_mode = $system_config_misc['maintenance_mode'];
		}

		if (!empty($system_config_misc['maintenance_mode_exclude_ips'])) {
			$maintenance_mode_exclude_ips = array_map('trim', explode(',', $system_config_misc['maintenance_mode_exclude_ips']));
		}

		return 'y' === $maintenance_mode && !in_array($_SERVER['REMOTE_ADDR'], $maintenance_mode_exclude_ips);
	}
	
	private function get_cookie_domain() {
		$sec_config = $this->getconf->get_security_config('permissions');
		$proxy_panel_allowed = $sec_config['reverse_proxy_panel_allowed'];
		if ($proxy_panel_allowed == 'all') {
			return '';
		}
		/*
		 * See ticket #5238: It should be ensured, that _SERVER_NAME is always set.
		 * Otherwise the security improvement doesn't work with nginx. If this is done,
		 * the check for HTTP_HOST and workaround for nginx is obsolete.
		 */
		$cookie_domain = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST']);
		// Workaround for Nginx servers
		if($cookie_domain == '_') {
			$tmp = explode(':',$_SERVER["HTTP_HOST"]);
			$cookie_domain = $tmp[0];
			unset($tmp);
		}
		if($proxy_panel_allowed == 'sites') {
			$forwarded_host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : null );
			if($forwarded_host !== null && $forwarded_host !== $cookie_domain) {
				// Just check for complete domain name and not auto subdomains
				$sql = "SELECT domain_id from web_domain where domain = ?";
				$recs = $this->db->queryOneRecord($sql, $forwarded_host);
				if($recs !== null) {
					$cookie_domain = $forwarded_host;
				}
				unset($forwarded_host);
			}
		}
		
		return $cookie_domain;
	}

} // end class

//** Initialize application (app) object
//* possible future =  new app($conf);
$app = new app();
/* 
   split session creation out of constructor is IMHO better.
   otherwise we have some circular references to global $app like in
   getconfig property of App - RA
*/
$app->initialize_session();

// load and enable PHP Intrusion Detection System (PHPIDS)
$ids_security_config = $app->getconf->get_security_config('ids');
		
if(is_dir(ISPC_CLASS_PATH.'/IDS') && !defined('REMOTE_API_CALL') && ($ids_security_config['ids_anon_enabled'] == 'yes' || $ids_security_config['ids_user_enabled'] == 'yes' || $ids_security_config['ids_admin_enabled'] == 'yes')) {
	$app->uses('ids');
	$app->ids->start();
}
unset($ids_security_config);

?>
