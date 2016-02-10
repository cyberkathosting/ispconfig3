<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
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

class cronjob_monitor_clamav_log extends cronjob {

	// job schedule
	protected $_schedule = '*/5 * * * *';
	protected $_run_at_new = true;

	private $_tools = null;

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
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


		$type = 'log_clamav';

		/* Get the data of the log */
		$data = $this->_tools->_getLogData($type);

		// Todo: the state should be calculated.
		$state = 'ok';

		$res = array();
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
			'VALUES (?, ?, UNIX_TIMESTAMP(), ?, ?)';
		$app->dbmaster->query($sql, $res['server_id'], $res['type'], serialize($res['data']), $res['state']);

		/* The new data is written, now we can delete the old one */
		$this->_tools->delOldRecords($res['type'], $res['server_id']);


		/** The type of the data */
		$type = 'log_freshclam';

		/* Get the data of the log */
		$data = $this->_tools->_getLogData($type);

		/* Get the data from the LAST log-Entry.
		 * if there can be found:
		 * WARNING: Your ClamAV installation is OUTDATED!
		 * then the clamav is outdated. This is a warning!
		 */
		$state = 'ok';

		$tmp = explode("\n", $data);
		$lastLog = array();
		if ($tmp[sizeof($tmp) - 1] == '') {
			/* the log ends with an empty line remove this */
			array_pop($tmp);
		}
		if (strpos($tmp[sizeof($tmp) - 1], '-------------') !== false) {
			/* the log ends with "-----..." remove this */
			array_pop($tmp);
		}
		for ($i = sizeof($tmp) - 1; $i > 0; $i--) {
			if (strpos($tmp[$i], '---------') === false) {
				/* no delimiter found, so add this to the last-log */
				$lastLog[] = $tmp[$i];
			} else {
				/* delimiter found, so there is no more line left! */
				break;
			}
		}

		/*
		 * Now we have the last log in the array.
		 * Check if the outdated-string is found...
		 */
		$clamav_outdated_warning = false;
		$clamav_bytecode_updated = false;
		foreach ($lastLog as $line) {
			if (stristr($line,"Can't download daily.cvd from")) {
				$clamav_outdated_warning = true;
			}
			if(stristr($line,'main.cld is up to date')) {
				$clamav_bytecode_updated = true;
			}
		}
		
		//* Warn when clamav is outdated and main.cld update failed.
		if($clamav_outdated_warning == true && $clamav_bytecode_updated == false) {
			$state = $this->_tools->_setState($state, 'info');
		}

		$res = array();
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
			'VALUES (?, ?, UNIX_TIMESTAMP(), ?, ?)';
		$app->dbmaster->query($sql, $res['server_id'], $res['type'], serialize($res['data']), $res['state']);

		/* The new data is written, now we can delete the old one */
		$this->_tools->delOldRecords($res['type'], $res['server_id']);


		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
