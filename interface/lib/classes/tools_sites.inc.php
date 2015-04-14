<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

class tools_sites {

	function replacePrefix($name, $dataRecord) {
		// No input -> no possible output -> go out!
		if ($name=="") return "";

		// Array containing keys to search
		$keywordlist=array('CLIENTNAME', 'CLIENTID', 'DOMAINID');

		// Try to match the key within the string
		foreach ($keywordlist as $keyword) {
			if (substr_count($name, '['.$keyword.']') > 0) {
				switch ($keyword) {
				case 'CLIENTNAME':
					$name=str_replace('['.$keyword.']', $this->getClientName($dataRecord), $name);
					break;
				case 'CLIENTID':
					$name=str_replace('['.$keyword.']', $this->getClientID($dataRecord), $name);
					break;
				case 'DOMAINID':
					$name=str_replace('['.$keyword.']', $dataRecord['parent_domain_id'] ? $dataRecord['parent_domain_id'] : '[DOMAINID]', $name);
					break;
				}
			}
		}
		return $name;
	}

	function removePrefix($name, $currentPrefix, $globalPrefix) {
		if($name == "") return "";

		if($currentPrefix === '') return $name; // empty prefix, do not change name
		if($currentPrefix === '#') $currentPrefix = $globalPrefix; // entry has no prefix set, maybe it was created before this function was introduced

		if($currentPrefix === '') return $name; // no current prefix and global prefix is empty -> nothing to remove here.

		return preg_replace('/^' . preg_quote($currentPrefix, '/') . '/', '', $name); // return name without prefix
	}

	function getPrefix($currentPrefix, $userPrefix, $adminPrefix = false) {
		global $app;

		if($currentPrefix !== '#') return $currentPrefix; // return the currently set prefix for this entry (# = no prefix set yet)

		if($adminPrefix === false) $adminPrefix = $userPrefix;

		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) return $adminPrefix;
		else return $userPrefix;
	}

	function getClientName($dataRecord) {
		global $app, $conf;
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			// Get the group-id of the user if the logged in user is neither admin nor reseller
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
		} else {
			// Get the group-id from the data itself
			if(isset($dataRecord['client_group_id'])) {
				$client_group_id = $dataRecord['client_group_id'];
			} elseif (isset($dataRecord['parent_domain_id'])) {
				$tmp = $app->db->queryOneRecord("SELECT sys_groupid FROM web_domain WHERE domain_id = ?", $dataRecord['parent_domain_id']);
				$client_group_id = $tmp['sys_groupid'];
			} elseif(isset($dataRecord['sys_groupid'])) {
				$client_group_id = $dataRecord['sys_groupid'];
			} else {
				return '[CLIENTNAME]';
			}
		}

		$tmp = $app->db->queryOneRecord("SELECT name FROM sys_group WHERE groupid = ?", $client_group_id);
		$clientName = $tmp['name'];
		if ($clientName == "") $clientName = 'default';
		$clientName = $this->convertClientName($clientName);
		return $clientName;
	}

	function getClientID($dataRecord) {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			// Get the group-id of the user
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
		} else {
			// Get the group-id from the data itself
			if(isset($dataRecord['client_group_id'])) {
				$client_group_id = $dataRecord['client_group_id'];
			} elseif (isset($dataRecord['parent_domain_id']) && $dataRecord['parent_domain_id'] != 0) {
				$tmp = $app->db->queryOneRecord("SELECT sys_groupid FROM web_domain WHERE domain_id = ?", $dataRecord['parent_domain_id']);
				$client_group_id = $tmp['sys_groupid'];
			} elseif(isset($dataRecord['sys_groupid'])) {
				$client_group_id = $dataRecord['sys_groupid'];
			} else {
				return '[CLIENTID]';
			}
		}
		$tmp = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = ?", $client_group_id);
		$clientID = $tmp['client_id'];
		if ($clientID == '') $clientID = '0';
		return $clientID;
	}

	function convertClientName($name){
		$allowed = 'abcdefghijklmnopqrstuvwxyz0123456789_';
		$res = '';
		$name = strtolower(trim($name));
		for ($i=0; $i < strlen($name); $i++){
			if ($name[$i] == ' ') continue;
			if (strpos($allowed, $name[$i]) !== false){
				$res .= $name[$i];
			}
			else {
				$res .= '_';
			}
		}
		return $res;
	}

	/* TODO: rewrite SQL */
	function getDomainModuleDomains($not_used_in_table = null, $selected_domain = null) {
		global $app;

		$sql = "SELECT domain_id, domain FROM domain WHERE";
		if ($not_used_in_table) {
			if (strpos($not_used_in_table, 'dns') !== false) {
				$field = "origin";
				$select = "SUBSTRING($field, 1, CHAR_LENGTH($field) - 1)";
			} else {
				$field = "domain";
				$select = $field;
			}
			$sql .= " domain NOT IN (SELECT $select FROM ?? WHERE $field != ?) AND";
		}
		if ($_SESSION["s"]["user"]["typ"] == 'admin') {
			$sql .= " 1";
		} else {
			$groups = ( $_SESSION["s"]["user"]["groups"] ) ? $_SESSION["s"]["user"]["groups"] : 0;
			$sql .= " sys_groupid IN (".$groups.")";
		}
		$sql .= " ORDER BY domain";
		return $app->db->queryAllRecords($sql, $not_used_in_table, $selected_domain);
	}

	/* TODO: rewrite SQL */
	function checkDomainModuleDomain($domain_id) {
		global $app;

		$sql = "SELECT domain_id, domain FROM domain WHERE domain_id = " . $app->functions->intval($domain_id);
		if ($_SESSION["s"]["user"]["typ"] != 'admin') {
			$groups = ( $_SESSION["s"]["user"]["groups"] ) ? $_SESSION["s"]["user"]["groups"] : 0;
			$sql .= " AND sys_groupid IN (".$groups.")";
		}
		$domain = $app->db->queryOneRecord($sql);
		if(!$domain || !$domain['domain_id']) return false;
		return $domain['domain'];
	}
	
	/* TODO: rewrite SQL */
	function getClientIdForDomain($domain_id) {
		global $app;

		$sql = "SELECT sys_groupid FROM domain WHERE domain_id = " . $app->functions->intval($domain_id);
		if ($_SESSION["s"]["user"]["typ"] != 'admin') {
			$groups = ( $_SESSION["s"]["user"]["groups"] ) ? $_SESSION["s"]["user"]["groups"] : 0;
			$sql .= " AND sys_groupid IN (".$groups.")";
		}
		$domain = $app->db->queryOneRecord($sql);
		if(!$domain || !$domain['sys_groupid']) return false;
		return $domain['sys_groupid'];
	}

}

?>
