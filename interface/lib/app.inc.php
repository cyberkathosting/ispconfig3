<?php
/**
 * Application Class
 * 
 * @author Till Brehm
 * @copyright  2005, Till Brehm, projektfarm Gmbh
 * @version 0.1
 * @package ISPConfig
 */

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

ob_start('ob_gzhandler');

class app {

	private $_language_inc = 0;
	private $_wb;
	private $_loaded_classes = array();
    private $_conf;

	public function __construct()
    {
		global $conf;
		$this->_conf = $conf;
		if($this->_conf['start_db'] == true) {
				$this->load('db_'.$this->_conf['db_type']);
				$this->db = new db;
		}
		
		//* Start the session
		if($this->_conf['start_session'] == true) {
			session_start();
			
			//* Initialize session variables
			if(!isset($_SESSION['s']['id']) ) $_SESSION['s']['id'] = session_id();
			if(empty($_SESSION['s']['theme'])) $_SESSION['s']['theme'] = $conf['theme'];
			if(empty($_SESSION['s']['language'])) $_SESSION['s']['language'] = $conf['language'];
		}
		
		$this->uses('auth');
	}

	public function uses($classes)
    {	
        $cl = explode(',', $classes);
		if(is_array($cl)) {
			foreach($cl as $classname){
				$classname = trim($classname);
                //* Class is not loaded so load it
				if(!array_key_exists($classname, $this->_loaded_classes)){
					include_once(ISPC_CLASS_PATH."/$classname.inc.php");
					$this->$classname = new $classname();
					$this->_loaded_classes[$classname] = true;
				}
			}
		}
	}

	public function load($files)
    {	
		$fl = explode(',', $files);
		if(is_array($fl)) {
			foreach($fl as $file){
				$file = trim($file);
				include_once(ISPC_CLASS_PATH."/$file.inc.php");
			}
		}
	}

	/** Priority values are: 0 = DEBUG, 1 = WARNING,  2 = ERROR */
	public function log($msg, $priority = 0)
    {	
		if($priority >= $this->_conf['log_priority']) {
			if (is_writable($this->_conf['log_file'])) {
				if (!$fp = fopen ($this->_conf['log_file'], 'a')) {
					$this->error('Logfile konnte nicht ge�ffnet werden.');
				}
				if (!fwrite($fp, date('d.m.Y-H:i').' - '. $msg."\r\n")) {
					$this->error('Schreiben in Logfile nicht m�glich.');
				}
				fclose($fp);
			} else {
				$this->error('Logfile ist nicht beschreibbar.');
			}
		} 
	} 

    /** Priority values are: 0 = DEBUG, 1 = WARNING,  2 = ERROR */
	public function error($msg, $next_link = '', $stop = true, $priority = 1)
    {
		//$this->uses("error");
		//$this->error->message($msg, $priority);
		if($stop == true){
			$msg = '<html>
<head>
<title>Error</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../themes/default/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<br><br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="2">
<tr>
<td class="error"><b>Error:</b><br>'.$msg;
		if($next_link != '') $msg .= '<a href="'.$next_link.'">Next</a><br>';
		$msg .= '</td>
</tr>
</table>
</body>
</html>';
			die($msg);
		} else {
			echo $msg;
			if($next_link != '') echo "<a href='$next_link'>Next</a>";
		}
	}

    /** Loads language */
    public function lng($text)
    {
		if($this->_language_inc != 1) {
			//* loading global and module Wordbook
            // TODO: this need to be made clearer somehow - pedro
			@include_once(ISPC_ROOT_PATH.'/lib/lang/'.$_SESSION['s']['language'].'.lng');
			@include_once(ISPC_ROOT_PATH.'/web/'.$_SESSION['s']['module']['name'].'/lib/lang/'.$_SESSION['s']['language'].'.lng');
			$this->_wb = $wb;
			$this->_language_inc = 1;
		}		
		if(!empty($this->_wb[$text])) {
			$text = $this->_wb[$text];
		}
		return $text;
	}

    public function tpl_defaults()
    {	
		$this->tpl->setVar('theme', $_SESSION['s']['theme']);
		$this->tpl->setVar('phpsessid', session_id());
		$this->tpl->setVar('html_content_encoding', $this->_conf['html_content_encoding']);
		if(isset($this->_conf['logo']) && $this->_conf['logo'] != '' && @is_file($this->_conf['logo'])){
			$this->tpl->setVar('logo', '<img src="'.$this->_conf['logo'].'" border="0" alt="">');
		} else {
			$this->tpl->setVar('logo', '&nbsp;');
		}
		$this->tpl->setVar('app_title', $this->_conf['app_title']);
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
    }
    
} // end class

//** Initialize application (app) object
//* possible future =  new app($conf);
$app = new app();

?>