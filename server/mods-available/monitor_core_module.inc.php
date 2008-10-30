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
			$conf["server_id"] . ", " .
			"'" . $app->db->quote(serialize($type)) . "', " .
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
		$fd = popen ("df", "r");
		$buffer = '';
		while (!feof($fd)) {
			$buffer .= fgets($fd, 4096);
		}
		
		// split into array
		$df = split("\n", $buffer);
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
			$conf["server_id"] . ", " .
			"'" . $app->db->quote(serialize($type)) . "', " .
			time() . ", " .
			"'" . $app->db->quote(serialize($data)) . "', " .
			"'" . $state . "'" . 
			")";
		$app->db->query($sql);
	}
	//	
	//	
	//	function show_memusage ()
	//	{
	//		global $app;
	//		
	//		$html_out .= '<table id="system_memusage">';
	//		
	//		$fd = fopen ("/proc/meminfo", "r");
	//		while (!feof($fd)) {
	//			$buffer .= fgets($fd, 4096);
	//		}
	//		fclose($fd);
	//		
	//		$meminfo = split("\n",$buffer);
	//		
	//		foreach($meminfo as $mline){
	//			if($x > 2 and trim($mline) != "") {
	//				
	//				$mpart = split(":",$mline);
	//				
	//				$html_out .= '<tr>
	//						<td>'.$mpart[0].':</td>
	//						<td>'.$mpart[1].'</td>
	//						</tr>';
	//			}
	//			
	//			$x++;
	//		}
	//		$html_out .= '</table>';
	//		return $html_out;
	//	}
	//	
	//	function show_cpu ()
	//	{
	//		global $app;
	//		
	//		$html_out .= '<table id="system_cpu">';
	//		
	//		$n = 0;
	//		if(is_readable("/proc/cpuinfo")) {
	//			if($fd = fopen ("/proc/cpuinfo", "r")) {
	//				while (!feof($fd)) {
	//					$buffer .= fgets($fd, 4096);
	//					$n++;
	//					if($n > 100) break;
	//				}
	//				fclose($fd);
	//			}
	//		}
	//		
	//		$meminfo = split("\n",$buffer);
	//		
	//		if(is_array($meminfo)) {
	//			foreach($meminfo as $mline){
	//				if(trim($mline) != "") {
	//					
	//					$mpart = split(":",$mline);
	//					
	//					$html_out .= '<tr>
	//							<td>'.$mpart[0].':</td>
	//							<td>'.$mpart[1].'</td>
	//							</tr>';
	//				}
	//			}
	//			
	//			$x++;
	//		}
	//		$html_out .= '</table></div>';
	//		
	//		
	//		return $html_out;
	//	}
	//	
	//	function show_services ()
	//	{
	//		global $app;
	//		
	//		$html_out .= '<table id="system_services">';
	//		
	//		// Checke Webserver
	//		if(_check_tcp('localhost',80)) {
	//			$status = '<span class="online">Online</span>';
	//		} else {
	//			$status = '<span class="offline">Offline</span>';
	//		}
	//		$html_out .= '<tr>
	//				<td>Web-Server:</td>
	//				<td>'.$status.'</td>
	//				</tr>';
	//		
	//		
	//		// Checke FTP-Server
	//		if(_check_ftp('localhost',21)) {
	//			$status = '<span class="online">Online</span>';
	//		} else {
	//			$status = '<span class="offline">Offline</span>';
	//		}
	//		$html_out .= '<tr>
	//				<td>FTP-Server:</td>
	//				<td>'.$status.'</td>
	//				</tr>';
	//		
	//		// Checke SMTP-Server
	//		if(_check_tcp('localhost',25)) {
	//			$status = '<span class="online">Online</span>';
	//		} else {
	//			$status = '<span class="offline">Offline</span>';
	//		}
	//		$html_out .= '<tr>
	//				<td>SMTP-Server:</td>
	//				<td>'.$status.'</td>
	//				</tr>';
	//		
	//		// Checke POP3-Server
	//		if(_check_tcp('localhost',110)) {
	//			$status = '<span class="online">Online</span>';
	//		} else {
	//			$status = '<span class="offline">Offline</span>';
	//		}
	//		$html_out .= '<tr>
	//				<td>POP3-Server:</td>
	//				<td>'.$status.'</td>
	//				</tr>';
	//		
	//		// Checke BIND-Server
	//		if(_check_tcp('localhost',53)) {
	//			$status = '<span class="online">Online</span>';
	//		} else {
	//			$status = '<span class="offline">Offline</span>';
	//		}
	//		$html_out .= '<tr>
	//				<td>DNS-Server:</td>
	//				<td>'.$status.'</td>
	//				</tr>';
	//		
	//		// Checke MYSQL-Server
	//		//if($this->_check_tcp('localhost',3306)) {
	//		$status = '<span class="online">Online</span>';
	//		//} else {
	//		//$status = '<span class="offline">Offline</span>';
	//		//}
	//		$html_out .= '<tr>
	//				<td>mySQL-Server:</td>
	//				<td>'.$status.'</td>
	//				</tr>';
	//		
	//		
	//		$html_out .= '</table></div>';
	//		
	//		
	//		return $html_out;
	//	}
	//	
	//	function _check_tcp ($host,$port) {
	//		
	//		$fp = @fsockopen ($host, $port, &$errno, &$errstr, 2);
	//		
	//		if ($fp) {
	//			return true;
	//			fclose($fp);
	//		} else {
	//			return false;
	//			fclose($fp);
	//		}
	//	}
	//	
	//	function _check_udp ($host,$port) {
	//		
	//		$fp = @fsockopen ('udp://'.$host, $port, &$errno, &$errstr, 2);
	//		
	//		if ($fp) {
	//			return true;
	//			fclose($fp);
	//		} else {
	//			return false;
	//			fclose($fp);
	//		}
	//	}
	//	
	//	function _check_ftp ($host,$port){
	//		
	//		$conn_id = @ftp_connect($host, $port);
	//		
	//		if($conn_id){
	//			@ftp_close($conn_id);
	//			return true;
	//		} else {
	//			@ftp_close($conn_id);
	//			return false;
	//		}
	//	}
	
	/*
	 Deletes Records older than n.
	*/
	function _delOldRecords($type, $min, $hour=0, $days=0) {
		global $app;
		
		$now = time();
		$old = $now - ($min * 60) - ($hour * 60 * 60) - ($days * 24 * 60 * 60);
		$sql = "DELETE FROM monitor_data " .
			"WHERE " .
			"type =" . "'" . $app->db->quote(serialize($type)) . "' " .
			"AND " .	
			"created < " . $old;
		$app->db->query($sql);
	}
	
	
} // end class

?>