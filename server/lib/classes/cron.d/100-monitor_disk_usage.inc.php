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

class cronjob_monitor_disk_usage extends cronjob {

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


		$type = 'disk_usage';

		/** The state of the disk-usage */
		$state = 'ok';

		/** Fetch the data of ALL devices into a array (needed for monitoring!) */
		//$dfData = shell_exec('df -hT 2>/dev/null');
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		
		$dfData = shell_exec('df -PhT -x simfs -x squashfs | awk \'!x[$1]++\' 2>/dev/null');
		
		// split into array
		$df = explode("\n", $dfData);

		/*
		 * ignore the first line, process the rest
		 */
		for ($i = 1; $i <= sizeof($df); $i++) {
			if ($df[$i] != '') {
				/*
				 * Make an array of the data
				 */
				$s = preg_split('/[\s]+/', $df[$i]);
				$data[$i]['fs'] = $s[0];
				$data[$i]['type'] = $s[1];
				$data[$i]['size'] = $s[2];
				$data[$i]['used'] = $s[3];
				$data[$i]['available'] = $s[4];
				$data[$i]['percent'] = $s[5];
				$data[$i]['mounted'] = $s[6];
				/*
				 * calculate the state
				 */
				$usePercent = floatval($data[$i]['percent']);

				//* get the free memsize
				if(substr($data[$i]['available'], -1) == 'G') {
					$freesize = floatval($data[$i]['available'])*1024;
				} elseif(substr($data[$i]['available'], -1) == 'T') {
					$freesize = floatval($data[$i]['available'])*1024*1024;
				} else {
					$freesize = floatval($data[$i]['available']);
				}

				//* We don't want to check some filesystem which have no sensible filling levels
				switch ($data[$i]['type']) {
				case 'iso9660':
				case 'cramfs':
				case 'udf':
				case 'tmpfs':
				case 'devtmpfs':
				case 'udev':
					break;
				default:
					if ($usePercent > 75 && $freesize < 2000)
						$state = $this->_tools->_setState($state, 'info');
					if ($usePercent > 80 && $freesize < 1000)
						$state = $this->_tools->_setState($state, 'warning');
					if ($usePercent > 90 && $freesize < 500)
						$state = $this->_tools->_setState($state, 'critical');
					if ($usePercent > 95 && $freesize < 100)
						$state = $this->_tools->_setState($state, 'error');
					break;
				}
			}
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
