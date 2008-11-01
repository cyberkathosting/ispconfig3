<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

class monitor_core_module {
	
	/* TODO: this should be a config - var instead of a "constant" */
	var $interval = 5; // do the monitoring every 5 minutes
	
	var $module_name = 'monitor_core_module';
	var $class_name = 'monitor_core_module';
	/* No actions at this time. maybe later... */
	var $actions_available = array();
	
	/*
	 	This function is called when the module is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Annonce the actions that where provided by this module, so plugins 
		can register on them.
		*/
		/* none at them moment */
		//$app->plugins->announceEvents($this->module_name,$this->actions_available);
		
		/*
		As we want to get notified of any changes on several database tables,
		we register for them.
				
		The following function registers the function "functionname"
			to be executed when a record for the table "dbtable" is 
			processed in the sys_datalog. "classname" is the name of the
			class that contains the function functionname.
		*/
		/* none at them moment */
		//$app->modules->registerTableHook('mail_access','mail_module','process');
		
		/*
		Do the monitor every n minutes and write the result in the db
		*/
		$min = date('i');
		if (($min % $this->interval) == 0)
		{
			$this->doMonitor();
		}
	}
	
	/*
	 This function is called when a change in one of the registered tables is detected.
	 The function then raises the events for the plugins.
	*/
	function process($tablename, $action, $data) {
		//		global $app;
		//		
		//		switch ($tablename) {
		//			case 'mail_access':
		//				if($action == 'i') $app->plugins->raiseEvent('mail_access_insert',$data);
		//				if($action == 'u') $app->plugins->raiseEvent('mail_access_update',$data);
		//				if($action == 'd') $app->plugins->raiseEvent('mail_access_delete',$data);
		//				break;
		//		} // end switch
	} // end function
	
	/*
	This method is called every n minutes, when the module ist loaded.
	The method then does a system-monitoring
	*/
	// TODO: what monitoring is done should be a config-var
	function doMonitor()
	{
		/* Calls the single Monitoring steps */
		$this->monitorServer();
		$this->monitorDiskUsage();
		$this->monitorMemUsage();
		$this->monitorCpu();
		$this->monitorServices();
	}
	
	function monitorServer(){
		global $app;
		global $conf;
		
		/* the id of the server as int */
		$server_id = intval($conf["server_id"]);
		
		/** The type of the data */
		$type = 'server_load';	
		
		/* Delete Data older than 1 day */
		$this->_delOldRecords($type, 0, 0, 1);
		
		/*
		Fetch the data into a array
		*/
		$procUptime = shell_exec("cat /proc/uptime | cut -f1 -d' '");
		$data['up_days'] = floor($procUptime/86400);
		$data['up_hours'] = floor(($procUptime-$data['up_days']*86400)/3600);
		$data['up_minutes'] = floor(($procUptime-$data['up_days']*86400-$data['up_hours']*3600)/60);

		$data['uptime'] = shell_exec("uptime");
		
		$tmp = explode(",", $data['uptime'], 3);
		$tmpUser = explode(" ", trim($tmp[1]));
		$data['user_online'] = intval($tmpUser[0]);
		
		$loadTmp = explode(":" , trim($tmp[2]));
		$load = explode(",",  $loadTmp[1]);
		$data['load_1'] = floatval(trim($load[0]));
		$data['load_5'] = floatval(trim($load[1]));
		$data['load_15'] = floatval(trim($load[2]));
		
		// Todo: the state should be calculated. For example if the load is to heavy, the state is warning...
		$state = 'ok';
		
		/*
		Insert the data into the database
		*/
		$sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
			"VALUES (".
			$server_id . ", " .
			"'" . $app->db->quote($type) . "', " .
			time() . ", " .
			"'" . $app->db->quote(serialize($data)) . "', " .
			"'" . $state . "'" . 
			")";
		$app->db->query($sql);
	}
	
	function monitorDiskUsage() {
		global $app;
		global $conf;
		
		/* the id of the server as int */
		$server_id = intval($conf["server_id"]);
		
		/** The type of the data */
		$type = 'disk_usage';	
		
		/* Delete Data older than 10 minutes */
		$this->_delOldRecords($type, 10);
		
		/*
		Fetch the data into a array
		*/
		$dfData = shell_exec("df");
		
		// split into array
		$df = explode("\n", $dfData);
		// ignore the first line make a array of the rest
		for($i=1; $i <= sizeof($df); $i++){
			if ($df[$i] != '')
			{
				$s = preg_split ("/[\s]+/", $df[$i]);
				$data[$i]['fs'] = $s[0];
				$data[$i]['size'] = $s[1];
				$data[$i]['used'] = $s[2];
				$data[$i]['available'] = $s[3];
				$data[$i]['percent'] = $s[4];
				$data[$i]['mounted'] = $s[5];
			}
		}
		
		// Todo: the state should be calculated. For example if the load is to heavy, the state is warning...
		$state = 'ok';
		
		/*
		Insert the data into the database
		*/
		$sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
			"VALUES (".
			$server_id . ", " .
			"'" . $app->db->quote($type) . "', " .
			time() . ", " .
			"'" . $app->db->quote(serialize($data)) . "', " .
			"'" . $state . "'" . 
			")";
		$app->db->query($sql);
	}
		
		
	function monitorMemUsage()
	{
		global $app;
		global $conf;
		
		/* the id of the server as int */
		$server_id = intval($conf["server_id"]);
		
		/** The type of the data */
		$type = 'mem_usage';	
		
		/* Delete Data older than 10 minutes */
		$this->_delOldRecords($type, 10);
		
		/*
		Fetch the data into a array
		*/
		$miData = shell_exec("cat /proc/meminfo");
		
		$memInfo = explode("\n", $miData);
		
		foreach($memInfo as $line){
			$part = split(":", $line);
			$key = trim($part[0]);
			$tmp = explode(" ", trim($part[1]));
			$value = 0;
			if ($tmp[1] == 'kB') $value = $tmp[0] * 1024;
			$data[$key] = $value;
		}
		
		// Todo: the state should be calculated. For example if the load is to heavy, the state is warning...
		$state = 'ok';
		
		/*
		Insert the data into the database
		*/
		$sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
			"VALUES (".
			$server_id . ", " .
			"'" . $app->db->quote($type) . "', " .
			time() . ", " .
			"'" . $app->db->quote(serialize($data)) . "', " .
			"'" . $state . "'" . 
			")";
		$app->db->query($sql);
	}

		
	function monitorCpu()
	{
		global $app;
		global $conf;
		
		/* the id of the server as int */
		$server_id = intval($conf["server_id"]);
		
		/** The type of the data */
		$type = 'cpu_info';	
		
		/* There is only ONE CPU-Data, so delete the old one */
		$this->_delOldRecords($type, 0);
		
		/*
		Fetch the data into a array
		*/
		$cpuData = shell_exec("cat /proc/cpuinfo");
		$cpuInfo = explode("\n", $cpuData);
		
		foreach($cpuInfo as $line){
			$part = split(":", $line);
			$key = trim($part[0]);
			$value = trim($part[1]);
			$data[$key] = $value;
		}
		
		// Todo: the state should be calculated. For example if the load is to heavy, the state is warning...
		$state = 'ok';
		
		/*
		Insert the data into the database
		*/
		$sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
			"VALUES (".
			$server_id . ", " .
			"'" . $app->db->quote($type) . "', " .
			time() . ", " .
			"'" . $app->db->quote(serialize($data)) . "', " .
			"'" . $state . "'" . 
			")";
		$app->db->query($sql);
	}

		
	function monitorServices()
	{
		global $app;
		global $conf;
		
		/* the id of the server as int */
		$server_id = intval($conf["server_id"]);
		
		/** The type of the data */
		$type = 'services';	
		
		/* There is only ONE Service-Data, so delete the old one */
		$this->_delOldRecords($type, 0);
		
		// Checke Webserver
		if($this->_checkTcp('localhost',80)) {
			$data['webserver'] = true;
		} else {
			$data['webserver'] = false;
		}
		
		// Checke FTP-Server
		if($this->_checkFtp('localhost',21)) {
			$data['ftpserver'] = true;
		} else {
			$data['ftpserver'] = false;
		}
		
		// Checke SMTP-Server
		if($this->_checkTcp('localhost',25)) {
			$data['smtpserver'] = true;
		} else {
			$data['smtpserver'] = false;
		}
		// Checke POP3-Server
		if($this->_checkTcp('localhost',110)) {
			$data['pop3server'] = true;
		} else {
			$data['pop3server'] = false;
		}
		
		// Checke BIND-Server
		if($this->_checkTcp('localhost',53)) {
			$data['bindserver'] = true;
		} else {
			$data['bindserver'] = false;
		}
		
		// Checke MYSQL-Server
		if($this->_checkTcp('localhost',3306)) {
			$data['mysqlserver'] = true;
		} else {
			$data['mysqlserver'] = false;
		}
		
		// Todo: the state should be calculated. For example if the load is to heavy, the state is warning...
		$state = 'ok';
		
		/*
		Insert the data into the database
		*/
		$sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
			"VALUES (".
			$server_id . ", " .
			"'" . $app->db->quote($type) . "', " .
			time() . ", " .
			"'" . $app->db->quote(serialize($data)) . "', " .
			"'" . $state . "'" . 
			")";
		$app->db->query($sql);
		
	}
		
		
	function _checkTcp ($host,$port) {
			
			$fp = @fsockopen ($host, $port, &$errno, &$errstr, 2);
			
			if ($fp) {
				return true;
				fclose($fp);
			} else {
				return false;
				fclose($fp);
			}
		}
		
		function _checkUdp ($host,$port) {
			
			$fp = @fsockopen ('udp://'.$host, $port, &$errno, &$errstr, 2);
			
			if ($fp) {
				return true;
				fclose($fp);
			} else {
				return false;
				fclose($fp);
			}
		}
		
		function _checkFtp ($host,$port){
			
			$conn_id = @ftp_connect($host, $port);
			
			if($conn_id){
				@ftp_close($conn_id);
				return true;
			} else {
				@ftp_close($conn_id);
				return false;
			}
		}
	
	/*
	 Deletes Records older than n.
	*/
	function _delOldRecords($type, $min, $hour=0, $days=0) {
		global $app;
		
		$now = time();
		$old = $now - ($min * 60) - ($hour * 60 * 60) - ($days * 24 * 60 * 60);
		$sql = "DELETE FROM monitor_data " .
			"WHERE " .
			"type =" . "'" . $app->db->quote($type) . "' " .
			"AND " .	
			"created < " . $old;
		$app->db->query($sql);
	}
	
	
} // end class

?>