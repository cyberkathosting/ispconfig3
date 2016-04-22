<?php

/*
  Copyright (c) 2007-2011, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

class rescue_core_module {

	var $module_name = 'rescue_core_module';
	var $class_name = 'rescue_core_module';
	/* No actions at this time. maybe later... */
	var $actions_available = array();
	/**
	 * The monitoring-Data of this module.
	 * [0] are the actual data, [1] are the data 1 minnute ago [2] are teh data 2 minuntes...
	 */


	private $_monitoringData = array();

	/** The rescue-Data of this module. */
	private $_rescueData = array();


	/**
	 *  This function is called during ispconfig installation to determine
	 *  if a symlink shall be created for this plugin.
	 */
	function onInstall() {
		return true;
	}


	/**
	 * This function is called when the module is loaded
	 */
	function onLoad() {
		$this->_doRescue();
	}


	/**
	 * This function is called when a change in one of the registered tables is detected.
	 * The function then raises the events for the plugins.
	 */
	function process($tablename, $action, $data) {
		// not needed
	}

	/**
	 * This Method tries to rescue the server if a service is down.
	 */
	private function _doRescue() {
		/*
		 * do nothing, if the rescue-system is not enabled
		 */
		global $conf;
		if ((!isset($conf['serverconfig']['rescue']['try_rescue'])) ||
			((isset($conf['serverconfig']['rescue']['try_rescue'])) && ($conf['serverconfig']['rescue']['try_rescue'] !='y'))){
			return;
		}

		/*
		 * First we get the monitoring data needed for rescuing the system
		 */
		$this->_monitoringData = $this->_getMonitoringData();

		/*
		 * Next we get the rescue data needed for rescuing the system
		 */
		$this->_rescueData = $this->_getRescueData();

		/*
		 * rescue MongoDB if needed
		 */
//		$this->_rescueMongoDB();

		/*
		 * rescue mysql if needed (maybe httpd depends on mysql, so try this first!)
		 */
		$this->_rescueMySql();

		/*
		 * rescue httpd if needed
		 */
		$this->_rescueHttpd();

		/*
		 * The last step is to save the rescue-data
		 */
		$this->_saveRescueData();
	}

	/**
	 * This gets the Monitoring-Data, needed for rescuing the system.
	 * Because we can not be 100% sure, that the mysql-DB is up and running, so we use the
	 * file-system (this is no problem, because this module is the only one using this data,
	 * so we do not have parallel access.
	 */
	private function _getMonitoringData() {
		global $app;

		$dataFilename = dirname(__FILE__) . "/../temp/rescue_module_monitoringdata.ser.txt";

		/*
		 * If the file containing the data is too old (older than 5 minutes) it is better to
		 * delete it, because it could be, that the server was down for some times and so the data
		 * are outdated
		 */
		if (file_exists($dataFilename) && (filemtime($dataFilename) < (time() - 5 * 60))) {
			unlink($dataFilename);
		}

		/*
		 * Get the monitoring-data
		 */
		if (file_exists($dataFilename)) {
			$data = unserialize(file_get_contents($dataFilename));
		} else {
			$data = array();
		}

		/*
		 * $temp[0] was the data of the last monitoring (means 1 minute ago), $temp[1] is the data
		 * 2 minutes ago and so on. Now we have make place for the newest data...
		 */
		$max = sizeof($data);
		if ($max > 10){
			$max = 10; // not more than 10 histories
		}
		for ($i = $max; $i > 0; $i--){
			$data[$i] = $data[$i -1];
		}

		/*
		 * we need the monitoring tools
		 */
		$app->load('monitor_tools');
		$tools = new monitor_tools();

		/*
		 * Call the needed Monitoring-step and get the data
		 */
		$tmp[0] = $tools->monitorServices();

		/* Add the data at the FIRST position of the history */
		$data[0] = $tmp;

		/*
		 * We have the newest monitoring data. Save it!
		 * (and protect it, because there may be sensible data in it)
		 */
		file_put_contents($dataFilename, serialize($data));
		chmod($dataFilename, 0600);

		/* Thats it */
		return $data;
	}

	/**
	 * This gets the rescue-Data, needed for rescuing the system.
	 * Because we can not be 100% sure, that the mysql-DB is up and running, so we use the
	 * file-system (this is no problem, because this module is the only one using this data,
	 * so we do not have parallel access.
	 */
	private function _getRescueData() {
		$dataFilename = dirname(__FILE__) . "/../temp/rescue_module_rescuedata.ser.txt";

		/*
		 * If the file containing the data is too old (older than 5 minutes) it is better to
		 * delete it, because it could be, that the server was down for some times and so the data
		 * are outdated
		 */
		if (file_exists($dataFilename) && (filemtime($dataFilename) < (time() - 5 * 60))) {
			unlink($dataFilename);
		}

		/*
		 * Get the rescue-data
		 */
		if (file_exists($dataFilename)) {
			$data = unserialize(file_get_contents($dataFilename));
		} else {
			$data = array();
		}

		/* Thats it */
		return $data;
	}

	/**
	 * Writes the rescue data to disk.
	 * Because we can not be 100% sure, that the mysql-DB is up and running, so we use the
	 * file-system (this is no problem, because this module is the only one using this data,
	 * so we do not have parallel access.
	 */
	private function _saveRescueData() {
		$dataFilename = dirname(__FILE__) . "/../temp/rescue_module_rescuedata.ser.txt";
		/*
		 * We have the newest data. Save it!
		 * (and protect it, because there may be sensible data in it)
		 */
		file_put_contents($dataFilename, serialize($this->_rescueData));
		chmod($dataFilename, 0600);
	}

	/**
	 * restarts httpd, if needed
	 */
	private function _rescueHttpd(){
		global $app, $conf;

		/*
		 * do nothing, if it is not allowed to rescue httpd
		 */
		if ((isset($conf['serverconfig']['rescue']['do_not_try_rescue_httpd']) && ($conf['serverconfig']['rescue']['do_not_try_rescue_httpd']) == 'y')){
			return;
		}

		/*
		 * if the service is up and running, or the service is not installed there is nothing to do...
		 */
		if ($this->_monitoringData[0][0]['data']['webserver'] != 0){
			/* Clear the try counter, because we do not have to try to rescue the service */
			$this->_rescueData['webserver']['try_counter'] = 0;
			return;
		}

		/*
		 * OK, the service is installed and down.
		 * Maybe this is because of a restart of the service by the admin.
		 * This means, we check the data 1 minute ago
		 */
		if ((!isset($this->_monitoringData[1][0]['data']['webserver'])) ||
			((isset($this->_monitoringData[1][0]['data']['webserver'])) && ($this->_monitoringData[1][0]['data']['webserver'] != 0))){
			/*
			 * We do NOT have this data or we have this data, but the webserver was not down 1 minute ago.
			 * This means, it could be, that the admin is restarting the server.
			 * We wait one more minute...
			 */
			return;
		}

		/*#####
		 * The service is down and it was down 1 minute ago.
		 * We try to rescue it
		 *#####*/

		/* Get the try counter */
		$tryCount = (!isset($this->_rescueData['webserver']['try_counter']))? 1 : $this->_rescueData['webserver']['try_counter'] + 1;

		/* Set the new try counter */
		$this->_rescueData['webserver']['try_counter'] = $tryCount;
		
		if ($tryCount > 2 && $conf['serverconfig']['web']['server_type'] != 'nginx') {
			if($app->system->is_user('apache')) {
				$app->log("Clearing semaphores table for user apache.",LOGLEVEL_WARN);
				exec("ipcs -s | grep apache | awk '{ print $2 }' | xargs ipcrm sem");
			}
			if($app->system->is_user('www-data')) {
				$app->log("Clearing semaphores table for user apache.",LOGLEVEL_WARN);
				exec("ipcs -s | grep www-data | awk '{ print $2 }' | xargs ipcrm sem");
			}
		}

		/* if 5 times will not work, we have to give up... */
		if ($tryCount > 5){
			$app->log('httpd is down! Rescue will not help!', LOGLEVEL_ERROR);
			return;
		}


		$app->log('httpd is down! Try rescue httpd (try:' . $tryCount . ')...', LOGLEVEL_WARN);

		if($conf['serverconfig']['web']['server_type'] == 'nginx'){
			$daemon = 'nginx';
		} else {
			if(is_file($conf['init_scripts'] . '/' . 'httpd')) {
				$daemon = 'httpd';
			} elseif(is_file($conf['init_scripts'] . '/' . 'httpd2')){
				$daemon = 'httpd2';
			} else {
				$daemon = 'apache2';
			}
		}

		$this->_rescueDaemon($daemon);
	}


	/**
	 * restarts MongoDB, if needed
	 */
//	private function _rescueMongoDB(){
//		global $app, $conf;

		/*
		 * do nothing, if it is not allowed to rescue mysql
		 */
//		if ((isset($conf['serverconfig']['rescue']['do_not_try_rescue_mongodb']) && ($conf['serverconfig']['rescue']['do_not_try_rescue_mongodb']) == 'y')){
//			return;
//		}

		/*
		 * if the service is up and running, or the service is not installed there is nothing to do...
		 */
//		if ($this->_monitoringData[0][0]['data']['mongodbserver'] != 0){
//			/* Clear the try counter, because we do not have to try to rescue the service */
//			$this->_rescueData['mongodbserver']['try_counter'] = 0;
//			return;
//		}

		/*
		 * OK, the service is installed and down.
		 * Maybe this is because of a restart of the service by the admin.
		 * This means, we check the data 1 minute ago
		 */
//		if ((!isset($this->_monitoringData[1][0]['data']['mongodbserver'])) ||
//			((isset($this->_monitoringData[1][0]['data']['mongodbserver'])) && ($this->_monitoringData[1][0]['data']['mongodbserver'] != 0))){
			/*
			 * We do NOT have this data or we have this data, but the webserver was not down 1 minute ago.
			 * This means, it could be, that the admin is restarting the server.
			 * We wait one more minute...
			 */
//			return;
//		}

		/*#####
		 * The service is down and it was down 1 minute ago.
		 * We try to rescue it
		 *#####*/

		/* Get the try counter */
//		$tryCount = (!isset($this->_rescueData['mongodbserver']['try_counter']))? 1 : $this->_rescueData['mongodbserver']['try_counter'] + 1;

		/* Set the new try counter */
//		$this->_rescueData['mongodbserver']['try_counter'] = $tryCount;

		/* if 5 times will not work, we have to give up... */
//		if ($tryCount > 5){
//			$app->log('MongoDB is down! Rescue will not help!', LOGLEVEL_ERROR);
//			return;
//		}


//		$app->log('MongoDB is down! Try rescue MongoDB (try:' . $tryCount . ')...', LOGLEVEL_WARN);

//		if(is_file($conf['init_scripts'] . '/' . 'mongodb')) {
//			$daemon = 'mongodb';
//		} else {
//			$daemon = 'mongodb';
//		}

//		$this->_rescueDaemon($daemon);
//	}

	/**
	 * restarts mysql, if needed
	 */
	private function _rescueMySql(){
		global $app, $conf;

		/*
		 * do nothing, if it is not allowed to rescue mysql
		 */
		if ((isset($conf['serverconfig']['rescue']['do_not_try_rescue_mysql']) && ($conf['serverconfig']['rescue']['do_not_try_rescue_mysql']) == 'y')){
			return;
		}

		/*
		 * if the service is up and running, or the service is not installed there is nothing to do...
		 */
		if ($this->_monitoringData[0][0]['data']['mysqlserver'] != 0){
			/* Clear the try counter, because we do not have to try to rescue the service */
			$this->_rescueData['mysqlserver']['try_counter'] = 0;
			return;
		}

		/*
		 * OK, the service is installed and down.
		 * Maybe this is because of a restart of the service by the admin.
		 * This means, we check the data 1 minute ago
		 */
		if ((!isset($this->_monitoringData[1][0]['data']['mysqlserver'])) ||
			((isset($this->_monitoringData[1][0]['data']['mysqlserver'])) && ($this->_monitoringData[1][0]['data']['mysqlserver'] != 0))){
			/*
			 * We do NOT have this data or we have this data, but the webserver was not down 1 minute ago.
			 * This means, it could be, that the admin is restarting the server.
			 * We wait one more minute...
			 */
			return;
		}

		/*#####
		 * The service is down and it was down 1 minute ago.
		 * We try to rescue it
		 *#####*/

		/* Get the try counter */
		$tryCount = (!isset($this->_rescueData['mysqlserver']['try_counter']))? 1 : $this->_rescueData['mysqlserver']['try_counter'] + 1;

		/* Set the new try counter */
		$this->_rescueData['mysqlserver']['try_counter'] = $tryCount;

		/* if 5 times will not work, we have to give up... */
		if ($tryCount > 5){
			$app->log('MySQL is down! Rescue will not help!', LOGLEVEL_ERROR);
			return;
		}


		$app->log('MySQL is down! Try rescue mysql (try:' . $tryCount . ')...', LOGLEVEL_WARN);

		if(is_file($conf['init_scripts'] . '/' . 'mysqld')) {
			$daemon = 'mysqld';
		} else {
			$daemon = 'mysql';
		}

		$this->_rescueDaemon($daemon);
	}

	/**
	 * Tries to stop and then restart the daemon
	 *
	 * @param type $daemon the name of the daemon
	 */
	private function _rescueDaemon($daemon){
		global $app, $conf;

		$app->uses('system');
		// if you need to find all restarts search for "['init_scripts']"
		/*
		 * First we stop the running service "normally"
		 */

		/*
		 * ATTENTION!
		 * The service hangs. this means it could be, that "stop" will hang also.
		 * So we have to try to stop but if this will not work, we have to kill the stopping
		 * of the service
		 */
		exec($app->system->getinitcommand($daemon, 'stop').' && (sleep 3; kill $!; sleep 2; kill -9 $!) &> /dev/null');

		/*
		 * OK, we tryed to stop it normally, maybe this worked maybe not. So we have to look
		 * if the service is already running or not. If so, we have to kill them hard
		 */
		exec("kill -9 `ps -A | grep " . $daemon . "| grep -v grep | awk '{print $1}'` &> /dev/null");

		/*
		 * There are no more zombies left. Lets start the service..
		 */
		exec($app->system->getinitcommand($daemon, 'start'));
	}

}

?>
