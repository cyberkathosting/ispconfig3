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

	public function __construct() {
		global $conf;

		if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']) || isset($_REQUEST['s']) || isset($_REQUEST['s_old']) || isset($_REQUEST['conf'])) {
			die('Internal Error: var override attempt detected');
		}

		$this->_conf = $conf;
		if($this->_conf['start_db'] == true) {
			$this->load('db_'.$this->_conf['db_type']);
			$this->db = new db;
		}

		//* Start the session
		if($this->_conf['start_session'] == true) {

			$this->uses('session');
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

		$this->uses('functions'); // we need this before all others!
		$this->uses('auth,plugin');
	}

	public function __destruct() {
		session_write_close();
	}

	public function uses($classes) {
		$cl = explode(',', $classes);
		if(is_array($cl)) {
			foreach($cl as $classname) {
				$classname = trim($classname);
				//* Class is not loaded so load it
				if(!array_key_exists($classname, $this->_loaded_classes)) {
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

	/** Priority values are: 0 = DEBUG, 1 = WARNING,  2 = ERROR */


	public function log($msg, $priority = 0) {
		global $conf;
		if($priority >= $this->_conf['log_priority']) {
			// $server_id = $conf["server_id"];
			$server_id = 0;
			$priority = $this->functions->intval($priority);
			$tstamp = time();
			$msg = $this->db->quote('[INTERFACE]: '.$msg);
			$this->db->query("INSERT INTO sys_log (server_id,datalog_id,loglevel,tstamp,message) VALUES ($server_id,0,$priority,$tstamp,'$msg')");
			/*
			if (is_writable($this->_conf['log_file'])) {
				if (!$fp = fopen ($this->_conf['log_file'], 'a')) {
					$this->error('Unable to open logfile.');
				}
				if (!fwrite($fp, date('d.m.Y-H:i').' - '. $msg."\r\n")) {
					$this->error('Unable to write to logfile.');
				}
				fclose($fp);
			} else {
				$this->error('Unable to write to logfile.');
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
		if(!empty($this->_wb[$text])) {
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

		$this->tpl->setVar('theme', $_SESSION['s']['theme']);
		$this->tpl->setVar('html_content_encoding', $this->_conf['html_content_encoding']);

		$this->tpl->setVar('delete_confirmation', $this->lng('delete_confirmation'));
		//print_r($_SESSION);
		if(isset($_SESSION['s']['module']['name'])) {
			$this->tpl->setVar('app_module', $_SESSION['s']['module']['name']);
		}
		if(isset($_SESSION['s']['user']) && $_SESSION['s']['user']['typ'] == 'admin') {
			$this->tpl->setVar('is_admin', 1);
		}
		if(isset($_SESSION['s']['user']) && $this->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$this->tpl->setVar('is_reseller', 1);
		}
		/* Show username */
		if(isset($_SESSION['s']['user'])) {
			$this->tpl->setVar('cpuser', $_SESSION['s']['user']['username']);
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

} // end class

//** Initialize application (app) object
//* possible future =  new app($conf);
$app = new app();

?>
