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

class auth {
	var $client_limits = null;

	public function get_user_id()
	{
		return $_SESSION['s']['user']['userid'];
	}
	
	public function is_admin() {
		if($_SESSION['s']['user']['typ'] == 'admin') {
			return true;
		} else {
			return false;
		}
	}	
	
	public function has_clients($userid) {
		global $app, $conf;
		
		$userid = intval($userid);
		$client = $app->db->queryOneRecord("SELECT client.limit_client FROM sys_user, client WHERE sys_user.userid = $userid AND sys_user.client_id = client.client_id");
		if($client['limit_client'] > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	//** This function adds a given group id to a given user.
	public function add_group_to_user($userid,$groupid) {
		global $app;
		
		$userid = intval($userid);
		$groupid = intval($groupid);
		
		if($userid > 0 && $groupid > 0) {
			$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE userid = $userid");
			$groups = explode(',',$user['groups']);
			if(!in_array($groupid,$groups)) $groups[] = $groupid;
			$groups_string = implode(',',$groups);
			$sql = "UPDATE sys_user SET groups = '$groups_string' WHERE userid = $userid";
			$app->db->query($sql);
			return true;
		} else {
			return false;
		}
	}

	//** This function returns given client limit as integer, -1 means no limit
	public function get_client_limit($userid, $limitname)
	{
		global $app;
		
		// simple query cache
		if($this->client_limits===null) 
			$this->client_limits = $app->db->queryOneRecord("SELECT client.* FROM sys_user, client WHERE sys_user.userid = $userid AND sys_user.client_id = client.client_id");
		
		// isn't client -> no limit
		if(!$this->client_limits)
			return -1;
		
		if(isset($this->client_limits['limit_'.$limitname])) {
			return $this->client_limits['limit_'.$limitname];
		}		
	}	
	
	//** This function removes a given group id from a given user.
	public function remove_group_from_user($userid,$groupid) {
		global $app;
		
		$userid = intval($userid);
		$groupid = intval($groupid);
		
		if($userid > 0 && $groupid > 0) {
			$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE userid = $userid");
			$groups = explode(',',$user['groups']);
			$key = array_search($groupid,$groups);
			unset($groups[$key]);
			$groups_string = implode(',',$groups);
			$sql = "UPDATE sys_user SET groups = '$groups_string' WHERE userid = $userid";
			$app->db->query($sql);
			return true;
		} else {
			return false;
		}
	}
	
	public function check_module_permissions($module) {
		// Check if the current user has the permissions to access this module
		if(!stristr($_SESSION["s"]["user"]["modules"],$module)) {
			// echo "LOGIN_REDIRECT:/index.php";
			header("Location: /index.php");
			exit;
		}
	}
		
}

?>