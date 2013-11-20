<?php

/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
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

class session {

	private $session_array = array();
	private $db;
	private $timeout = 0;

	function __construct($session_timeout = 0) {
		$this->db = new db;
		$this->timeout = $session_timeout;
	}
	
	function set_timeout($session_timeout = 0) {
		$old_timeout = $this->timeout;
		$this->timeout = $session_timeout;
		return $old_timeout;
	}

	function open ($save_path, $session_name) {
		return true;
	}

	function close () {

		if (!empty($this->session_array)) {
			$result = $this->gc(ini_get('session.gc_maxlifetime'));
			return $result;
		}
		return false;
	}

	function read ($session_id) {
		
		if($this->timeout > 0) {
			$rec = $this->db->queryOneRecord("SELECT * FROM sys_session WHERE session_id = '".$this->db->quote($session_id)."' AND last_updated >= DATE_SUB(NOW(), INTERVAL " . intval($this->timeout) . " MINUTE)");
		} else {
			$rec = $this->db->queryOneRecord("SELECT * FROM sys_session WHERE session_id = '".$this->db->quote($session_id)."'");
		}

		if (is_array($rec)) {
			$this->session_array = $rec;
			return $this->session_array['session_data'];
		} else {
			return '';
		}
	}

	function write ($session_id, $session_data) {

		if (!empty($this->session_array) && $this->session_array['session_id'] != $session_id) {
			$this->session_array = array();
		}

		// Dont write session_data to DB if session data has not been changed after reading it.
		if(isset($this->session_array['session_data']) && $this->session_array['session_data'] != '' && $this->session_array['session_data'] == $session_data) {
			$session_id   = $this->db->quote($session_id);
			$last_updated = date('Y-m-d H:i:s');
			$this->db->query("UPDATE sys_session SET last_updated = '$last_updated' WHERE session_id = '$session_id'");
			return true;
		}


		if (@$this->session_array['session_id'] == '') {
			$session_id   = $this->db->quote($session_id);
			$date_created = date('Y-m-d H:i:s');
			$last_updated = date('Y-m-d H:i:s');
			$session_data = $this->db->quote($session_data);
			$sql = "INSERT INTO sys_session (session_id,date_created,last_updated,session_data) VALUES ('$session_id','$date_created','$last_updated','$session_data')";
			$this->db->query($sql);

		} else {
			$session_id   = $this->db->quote($session_id);
			$last_updated = date('Y-m-d H:i:s');
			$session_data = $this->db->quote($session_data);
			$sql = "UPDATE sys_session SET last_updated = '$last_updated', session_data = '$session_data' WHERE session_id = '$session_id'";
			$this->db->query($sql);

		}

		return true;
	}

	function destroy ($session_id) {

		$session_id   = $this->db->quote($session_id);
		$sql = "DELETE FROM sys_session WHERE session_id = '$session_id'";
		$this->db->query($sql);

		return true;
	}

	function gc ($max_lifetime) {

		/*if($this->timeout > 0) {
			$this->db->query("DELETE FROM sys_session WHERE last_updated < DATE_SUB(NOW(), INTERVAL " . intval($this->timeout) . " MINUTE)");
		} else {*/
			$real_now = date('Y-m-d H:i:s');
			$dt1 = strtotime("$real_now -$max_lifetime seconds");
			$dt2 = date('Y-m-d H:i:s', $dt1);

			$sql = "DELETE FROM sys_session WHERE last_updated < '$dt2'";
			$this->db->query($sql);
		//}

		return true;

	}

	function __destruct () {
		@session_write_close();

	}

}

?>
