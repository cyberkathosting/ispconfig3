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

class monitor_core_module {

	var $interval = 5; // do the monitoring every 5 minutes
	var $module_name = 'monitor_core_module';
	var $class_name = 'monitor_core_module';
	/* No actions at this time. maybe later... */
	var $actions_available = array();
	/** The Tools */
	private $_tools = null;
    //** time the script was called
    private $_run_time = null;

	/**
	 * This function is called during ispconfig installation to determine
	 * if a symlink shall be created for this plugin.
	 */
	public function onInstall() {
		global $conf;
		return true;
	}

	/**
	 * This function is called when the module is loaded
	 */
	public function onLoad() {
		global $app;
        
        //* store the running time
        $this->_run_time = time();
        
		/*
		 * Do the monitor every n minutes and write the result to the db
		 */
		$min = @date('i', $this->_run_time);
		if (($min % $this->interval) == 0) {
			$this->_doMonitor();
		}
	}

	/**
	 * This function is called when a change in one of the registered tables is detected.
	 * The function then raises the events for the plugins.
	 */
	public function process($tablename, $action, $data) {
		// not needed
	}

	/**
	 * This method is called every n minutes, when the module ist loaded.
	 * The method then does a system-monitoring
	 */
	// TODO: what monitoring is done should be a config-var
	private function _doMonitor() {
		global $app;
		/*
		 * We need the tools in almost every method, so initialize them once...
		 */
		$app->load('monitor_tools');
		$this->_tools = new monitor_tools();

		/*
		 * Calls the single Monitoring steps
		 */
		$this->_monitorEmailQuota();
		$this->_monitorHDQuota();
		$this->_monitorServer();
		$this->_monitorOsVer();
		$this->_monitorIspcVer();
		$this->_monitorDiskUsage();
		$this->_monitorMemUsage();
		$this->_monitorCpu();
		$this->_monitorServices();
		if (@file_exists('/proc/user_beancounters')) {
			$this->_monitorOpenVzHost();
			$this->_monitorOpenVzUserBeancounter();
		}
		$this->_monitorMailLog();
		$this->_monitorMailWarnLog();
		$this->_monitorMailErrLog();
		$this->_monitorMessagesLog();
		$this->_monitorISPCCronLog();
		$this->_monitorFreshClamLog();
		$this->_monitorClamAvLog();
		$this->_monitorIspConfigLog();
		$this->_monitorSystemUpdate();
		$this->_monitorMailQueue();
		$this->_monitorRaid();
		$this->_monitorRkHunter();
		$this->_monitorFail2ban();
		$this->_monitorMongoDB();
		$this->_monitorIPTables();
		$this->_monitorSysLog();
	}

    private function _monitorEmailQuota() {
        global $app, $conf;

        /*
		 *  This monitoring is expensive, so do it only every 15 minutes
		 */
		$min = @date('i', $this->_run_time);
		if ($min % 15 != 0) return;

		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		if($mail_config['mailbox_quota_stats'] == 'n') return;


		/*
         * First we get the Monitoring-data from the tools
         */
        $res = $this->_tools->monitorEmailQuota();

        /*
         * Insert the data into the database
         */
        $sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
                'VALUES (' .
                $res['server_id'] . ', ' .
                "'" . $app->dbmaster->quote($res['type']) . "', " .
                'UNIX_TIMESTAMP(), ' .
                "'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
                "'" . $res['state'] . "'" .
                ')';
        $app->dbmaster->query($sql);

        /* The new data is written, now we can delete the old one */
        $this->_delOldRecords($res['type'], $res['server_id']);
    }

	private function _monitorHDQuota() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorHDQuota();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorServer() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorServer();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorOsVer() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorOsVer();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorIspcVer() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorIspcVer();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorDiskUsage() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorDiskUsage();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorMemUsage() {
		global $app;
		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorMemUsage();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorCpu() {
		global $app;
		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorCpu();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorServices() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorServices();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorOpenVzHost() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorOpenVzHost();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorOpenVzUserBeancounter() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorOpenVzUserBeancounter();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorSystemUpdate() {
		/*
		 *  This monitoring is expensive, so do it only once an hour
		 */
		$min = @date('i', $this->_run_time);
		if ($min != 0)
			return;

		/*
		 * OK - here we go...
		 */
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorSystemUpdate();

		//* Ensure that output is encoded so that it does not break the serialize
		//$res['data']['output'] = htmlentities($res['data']['output']);
		$res['data']['output'] = htmlentities($res['data']['output'],ENT_QUOTES,'UTF-8');

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorMailQueue() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorMailQueue();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorRaid() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorRaid();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorRkHunter() {
		/*
		 *  This monitoring is expensive, so do it only once a day
		 */
		$min = @date('i', $this->_run_time);
		$hour = @date('H', $this->_run_time);
		if (!($min == 0 && $hour == 23))
			return;
		/*
		 * OK . here we go...
		 */
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorRkHunter();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorFail2ban() {
        global $app;

        /*
         * First we get the Monitoring-data from the tools
         */
        $res = $this->_tools->monitorFail2ban();

        /*
         * Insert the data into the database
         */
        $sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
                'VALUES (' .
                $res['server_id'] . ', ' .
                "'" . $app->dbmaster->quote($res['type']) . "', " .
                'UNIX_TIMESTAMP(), ' .
                "'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
                "'" . $res['state'] . "'" .
                ')';
        $app->dbmaster->query($sql);

        /* The new data is written, now we can delete the old one */
        $this->_delOldRecords($res['type'], $res['server_id']);
    }

	private function _monitorMongoDB() {
	global $app;

        /*
         * First we get the Monitoring-data from the tools
         */
        $res = $this->_tools->monitorMongoDB();

        /*
         * Insert the data into the database
         */
        $sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
                'VALUES (' .
                $res['server_id'] . ', ' .
                "'" . $app->dbmaster->quote($res['type']) . "', " .
                'UNIX_TIMESTAMP(), ' .
                "'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
                "'" . $res['state'] . "'" .
                ')';
        $app->dbmaster->query($sql);

        /* The new data is written, now we can delete the old one */
        $this->_delOldRecords($res['type'], $res['server_id']);
    }


	private function _monitorIPTables() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorIPTables();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorSysLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorSysLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorMailLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorMailLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorMailWarnLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorMailWarnLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorMailErrLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorMailErrLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorMessagesLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorMessagesLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorISPCCronLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorISPCCronLog();

		//* Ensure that output is encoded so that it does not break the serialize
		if(is_array($res) && isset($res['data'])) $res['data'] = htmlentities($res['data']);

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorFreshClamLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorFreshClamLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorClamAvLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorClamAvLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	private function _monitorIspConfigLog() {
		global $app;

		/*
		 * First we get the Monitoring-data from the tools
		 */
		$res = $this->_tools->monitorIspConfigLog();

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (' .
				$res['server_id'] . ', ' .
				"'" . $app->dbmaster->quote($res['type']) . "', " .
				'UNIX_TIMESTAMP(), ' .
				"'" . $app->dbmaster->quote(serialize($res['data'])) . "', " .
				"'" . $res['state'] . "'" .
				')';
		$app->dbmaster->query($sql);

		/* The new data is written, now we can delete the old one */
		$this->_delOldRecords($res['type'], $res['server_id']);
	}

	/**
	 * Deletes Records older than 4 minutes.
	 * The monitor writes new data every 5 minutes or longer (4 hour, 1 day).
	 * So if i delete all Date older than 4 minutes i can be sure, that all old data
	 * are deleted...
	 */
	private function _delOldRecords($type, $serverId) {
		global $app;

		// $now = time();
		// $old = $now - (4 * 60); // 4 minutes
		$old = 'UNIX_TIMESTAMP() - 240';

		/*
		 * ATTENTION if i do NOT pay attention of the server id, i delete all data (of the type)
		 * of ALL servers. This means, if i have a multiserver-environment and a server has a
		 * time not synced with the others (for example, all server has 11:00 and ONE server has
		 * 10:45) then the actual data of this server (with the time-stamp 10:45) get lost
		 * even though it is the NEWEST data of this server. To avoid this i HAVE to include
		 * the server-id!
		 */
		$sql = 'DELETE FROM monitor_data ' .
				'WHERE ' .
				'  type =' . "'" . $app->dbmaster->quote($type) . "' " .
				'AND ' .
				'  created < ' . $old . ' ' .
				'AND ' .
				'  server_id = ' . $serverId;
		$app->dbmaster->query($sql);
	}

}

?>
