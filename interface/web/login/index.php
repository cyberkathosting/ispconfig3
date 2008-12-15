<?php

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

//

class login_index {

	public $status = '';
	private $target = '';
	private $app;
	private $conf;
	
	public function render() {
		
		global $app, $conf;
		
		/* Redirect to page, if login form was NOT send */
		if(count($_POST) == 0) {
			if(isset($_SESSION['s']['user']) && is_array($_SESSION['s']['user']) && is_array($_SESSION['s']['module'])) {
				die('HEADER_REDIRECT:'.$_SESSION['s']['module']['startpage']);
			}
		}
		
		$app->uses('tpl');
		$app->tpl->newTemplate('form.tpl.htm');
	    
	    $error = '';    
	
	
		//* Login Form was send
		if(count($_POST) > 0) {
	
	        // iporting variables
	        $ip 	  = $app->db->quote(ip2long($_SERVER['REMOTE_ADDR']));
	        $username = $app->db->quote($_POST['username']);
	        $passwort = $app->db->quote($_POST['passwort']); 
	
	        if($username != '' and $passwort != '') {
				/*
				 *  Check, if there is a "login as" instead of a "normal" login
				 */
				if (isset($_SESSION['s']['user'])){
					/*
					 * only the admin can "login as" so if the user is NOT a admin, we
					 * open the startpage (after killing the old session), so the user
					 * is logout and has to start again!
					 */
					if ($_SESSION['s']['user']['typ'] != 'admin') {
						/*
						 * The actual user is NOT a admin, but maybe the admin
						 * has logged in as "normal" user bevore...
						 */
						if (isset($_SESSION['s_old'])&& ($_SESSION['s_old']['user']['typ'] == 'admin')){
							/* The "old" user is admin, so everything is ok */
						}
						else {
							die("You don't have the right to 'login as'!");
						}
					}
					$loginAs = true;
				}
				else {
					/* normal login */
					$loginAs = false;
				}

	        	//* Check if there already wrong logins
	        	$sql = "SELECT * FROM `attempts_login` WHERE `ip`= '{$ip}' AND  `login_time` < NOW() + INTERVAL 15 MINUTE LIMIT 1";
	        	$alreadyfailed = $app->db->queryOneRecord($sql);
	        	//* login to much wrong
	        	if($alreadyfailed['times'] > 5) {
	        		$error = $app->lng(1004);
	        	} else {
					if ($loginAs){
			        	$sql = "SELECT * FROM sys_user WHERE USERNAME = '$username' and PASSWORT = '". $passwort. "'";
					}
					else {
			        	$sql = "SELECT * FROM sys_user WHERE USERNAME = '$username' and ( PASSWORT = '".md5($passwort)."' or PASSWORT = password('$passwort') )";
					}
		            $user = $app->db->queryOneRecord($sql);
		            if($user) {
		                if($user['active'] == 1) {
		                	// User login right, so attempts can be deleted
		                	$sql = "DELETE FROM `attempts_login` WHERE `ip`='{$ip}'";
		                	$app->db->query($sql);
		                	$user = $app->db->toLower($user);
							if ($loginAs) $oldSession = $_SESSION['s'];
		                    $_SESSION = array();
							if ($loginAs) $_SESSION['s_old'] = $oldSession; // keep the way back!
		                    $_SESSION['s']['user'] = $user;
		                    $_SESSION['s']['user']['theme'] = isset($user['app_theme']) ? $user['app_theme'] : 'default';
		                    $_SESSION['s']['language'] = $user['language'];
							$_SESSION["s"]['theme'] = $_SESSION['s']['user']['theme'];
										
							if(is_file($_SESSION['s']['user']['startmodule'].'/lib/module.conf.php')) {
								include_once($_SESSION['s']['user']['startmodule'].'/lib/module.conf.php');
								$_SESSION['s']['module'] = $module;
							}
							echo 'HEADER_REDIRECT:'.$_SESSION['s']['module']['startpage'];
										
		                   	exit;
		             	} else {
		                	$error = $app->lng(1003);
		                }
		        	} else {
		        		if(!$alreadyfailed['times'] )
		        		{
		        			//* user login the first time wrong
		        			$sql = "INSERT INTO `attempts_login` (`ip`, `times`, `login_time`) VALUES ('{$ip}', 1, NOW())";
		        			$app->db->query($sql);
		        		} elseif($alreadyfailed['times'] >= 1) {
		        			//* update times wrong
		        			$sql = "UPDATE `attempts_login` SET `times`=`times`+1, `login_time`=NOW() WHERE `login_time` >= '{$time}' LIMIT 1";
		        			$app->db->query($sql);
		        		}
		            	//* Incorrect login - Username and password incorrect
		                $error = $app->lng(1002);
		                if($app->db->errorMessage != '') $error .= '<br />'.$app->db->errorMessage != '';
		           	}
	        	}
	      	} else {
	       		//* Username or password empty
	            $error = $app->lng(1001);
	        }
		}
		if($error != ''){
	  		$error = '<div class="box box_error"><h1>Error</h1>'.$error.'</div>';
		}
	
	
	
		$app->tpl->setVar('error', $error);
		$app->tpl->setInclude('content_tpl','login/templates/index.htm');
		$app->tpl_defaults();
		
		$this->status = 'OK';
		
		return $app->tpl->grab();
		
	} // << end function

} // << end class

?>