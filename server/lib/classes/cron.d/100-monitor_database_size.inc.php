<?php

/**
 Copyright (c) 2013, Marius Cramer, pixcept KG
 Copyright (c) 2013, Florian Schaal, info@schaal-24.de
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


class cronjob_monitor_database_size extends cronjob {

	// job schedule
	protected $_schedule = '*/5 * * * *';
	protected $_run_at_new = true;

	private $_tools = null;



	/**
	 * this function is optional if it contains no custom code
	 */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}



	/**
	 * this function is optional if it contains no custom code
	 */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;

		/* used for all monitor cronjobs */
		$app->load('monitor_tools');
		$this->_tools = new monitor_tools();
		/* end global section for monitor cronjobs */

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'database_size';

		/** The state of the database-usage */
		$state = 'ok';

		/** Fetch the data of all databases into an array */
		$databases = $app->db->queryAllRecords("SELECT database_id, database_name, sys_groupid, database_quota, quota_exceeded FROM web_database WHERE server_id = ? GROUP BY sys_groupid, database_name ASC", $server_id);

		if(is_array($databases) && !empty($databases)) {

			$data = array();

			for ($i = 0; $i < sizeof($databases); $i++) {
				$rec = $databases[$i];
				
				$data[$i]['database_name']= $rec['database_name'];
				$data[$i]['size'] = $app->db->getDatabaseSize($rec['database_name']);
				$data[$i]['sys_groupid'] = $rec['sys_groupid'];

				$quota = $rec['database_quota'] * 1024 * 1024;
				if(!is_numeric($quota)) continue;
				
				if($quota < 1 || $quota > $data[$i]['size']) {
					print 'database ' . $rec['database_name'] . ' size does not exceed quota: ' . ($quota < 1 ? 'unlimited' : $quota) . ' (quota) > ' . $data[$i]['size'] . " (used)\n";
					if($rec['quota_exceeded'] == 'y') {
						$app->dbmaster->datalogUpdate('web_database', array('quota_exceeded' => 'n'), 'database_id', $rec['database_id']);
					}
				} elseif($rec['quota_exceeded'] == 'n') {
					print 'database ' . $rec['database_name'] . ' size exceeds quota: ' . $quota . ' (quota) < ' . $data[$i]['size'] . " (used)\n";
					$app->dbmaster->datalogUpdate('web_database', array('quota_exceeded' => 'y'), 'database_id', $rec['database_id']);
				}
			}

			$res = array();
			$res['server_id'] = $server_id;
			$res['type'] = $type;
			$res['data'] = $data;
			$res['state'] = $state;

			//* Insert the data into the database
			$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
				'VALUES (?, ?, UNIX_TIMESTAMP(), ?, ?)';
			$app->dbmaster->query($sql, $res['server_id'], $res['type'], serialize($res['data']), $res['state']);

			//* The new data is written, now we can delete the old one
			$this->_tools->delOldRecords($res['type'], $res['server_id']);
		}

		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
